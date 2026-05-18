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
}
