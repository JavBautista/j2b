<?php

namespace App\Services\Facturacion;

use App\Models\CfdiInvoice;

/**
 * Cancela CFDIs enrutando al endpoint correcto según el pipeline
 * con el que se timbraron originalmente:
 *
 *   pipeline_timbrado='json'        -> DELETE /v1/facturacion/cancelar/{uuid}
 *   pipeline_timbrado='xml_compat'  -> POST /v1/compatibilidad/{CLIENT_ID}/CancelaCFDI
 *
 * Mantener este servicio único asegura que los CFDIs con implocal se cancelan
 * por el endpoint que TBT espera (ver respuesta TBT 2026-05-18 §6).
 */
class CfdiCancelacionService
{
    public function cancelar(CfdiInvoice $invoice, string $motivo, ?string $folioSustitucion = null): array
    {
        $hub = app(HubCfdiService::class);

        if ($invoice->pipeline_timbrado === 'xml_compat') {
            $rfc = $invoice->emisor->rfc ?? null;
            if (!$rfc) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => 'No se pudo determinar el RFC del emisor para cancelación XML compat.',
                ];
            }
            return $hub->cancelarCompat($rfc, $invoice->uuid, $motivo, $folioSustitucion);
        }

        // Default: pipeline JSON (incluye CFDIs antiguos sin marca explícita)
        return $hub->cancelar($invoice->uuid, $motivo, $folioSustitucion);
    }

    /**
     * Interpreta la respuesta del HUB/SAT tras una solicitud de cancelación para
     * distinguir una cancelación FINAL (aceptada) de una que quedó EN PROCESO
     * (esperando aceptación del receptor) o RECHAZADA.
     *
     * Parser DEFENSIVO: el shape exacto del HUB no está confirmado (ver
     * PLAN_CANCELACION_SAT.md §0.6). Solo "protege" (final=false) cuando detecta
     * señal POSITIVA con los strings oficiales del SAT (catálogo EstatusCancelacion).
     * Si la respuesta es ambigua/no reconocible → final=true (comportamiento previo:
     * el controller marca 'cancelada'). Nunca introduce regresión; el caller loguea
     * la respuesta cruda para afinar con el primer caso real.
     *
     * @param  mixed $data  $result['data'] devuelto por cancelar()/cancelarCompat()
     * @return array{reconocido:bool, cancelacion_status:?string, final:bool, estatus_uuid_sat:?string, estatus_cancelacion_sat:?string}
     */
    public static function interpretarRespuesta($data): array
    {
        $out = [
            'reconocido' => false,
            'cancelacion_status' => null, // en_proceso|aceptada|rechazada_receptor|rechazada_sat
            'final' => true,              // true → status puede pasar a 'cancelada'
            'estatus_uuid_sat' => null,
            'estatus_cancelacion_sat' => null,
        ];

        if (empty($data)) {
            return $out; // ambiguo → default conservador (comportamiento previo)
        }

        // 1. Recolectar pares (clave normalizada => valores) y un blob de texto, recursivo.
        $pares = [];
        $blob = [];
        $walk = function ($node) use (&$walk, &$pares, &$blob) {
            if (is_array($node)) {
                foreach ($node as $k => $v) {
                    if (is_scalar($v) || $v === null) {
                        $key = self::normalizar((string) $k);
                        $val = is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                        $pares[$key][] = $val;
                        $blob[] = self::normalizar($val);
                    } else {
                        $walk($v);
                    }
                }
            } elseif (is_scalar($node)) {
                $blob[] = self::normalizar((string) $node);
            }
        };
        $walk(is_array($data) ? $data : ['_' => $data]);
        $blobText = implode(' | ', $blob);

        // 2. Campos crudos si vienen con clave reconocible.
        $estatusCancelacion = self::primerValorPorClaves($pares, ['estatuscancelacion', 'estatus_cancelacion']);
        $estatusUuid = self::primerValorPorClaves($pares, ['estatusuuid', 'estatus_uuid', 'codestatus', 'codigo']);
        $estado = self::primerValorPorClaves($pares, ['estado']);

        if ($estatusCancelacion !== null) {
            $out['estatus_cancelacion_sat'] = $estatusCancelacion;
        }
        if ($estatusUuid !== null && preg_match('/\b(20[1-5])\b/', $estatusUuid, $m)) {
            $out['estatus_uuid_sat'] = $m[1];
        }

        // 3. Texto donde buscar señales: el campo específico si existe, si no el blob completo.
        $hay = fn (string $needle) => self::contiene($estatusCancelacion ?? $blobText, $needle);

        // 4. Mapeo por prioridad (strings oficiales del catálogo SAT).
        if ($hay('en proceso')) {
            $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'en_proceso', 'final' => false]);
        } elseif ($hay('rechaz')) {
            $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'rechazada_receptor', 'final' => false]);
        } elseif ($hay('plazo vencido')) {
            $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'rechazada_sat', 'final' => false]);
        } elseif ($hay('cancelado con aceptacion') || $hay('cancelado sin aceptacion')) {
            $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'aceptada', 'final' => true]);
        } elseif ($out['estatus_uuid_sat'] !== null) {
            if (in_array($out['estatus_uuid_sat'], ['201', '202'], true)) {
                $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'aceptada', 'final' => true]);
            } else { // 203/204/205 → no se canceló
                $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'rechazada_sat', 'final' => false]);
            }
        } elseif ($estado !== null && self::contiene($estado, 'cancelado')) {
            $out = array_merge($out, ['reconocido' => true, 'cancelacion_status' => 'aceptada', 'final' => true]);
        }
        // else: no reconocido → default conservador (final=true, status='cancelada' en el caller).

        return $out;
    }

    private static function normalizar(string $s): string
    {
        return \Illuminate\Support\Str::ascii(mb_strtolower(trim($s)));
    }

    private static function contiene(?string $haystack, string $needle): bool
    {
        if ($haystack === null || $haystack === '') {
            return false;
        }
        return str_contains(self::normalizar($haystack), self::normalizar($needle));
    }

    private static function primerValorPorClaves(array $pares, array $claves): ?string
    {
        foreach ($claves as $c) {
            if (!empty($pares[$c])) {
                return $pares[$c][0];
            }
        }
        return null;
    }
}
