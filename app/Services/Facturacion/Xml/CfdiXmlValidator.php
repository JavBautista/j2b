<?php

namespace App\Services\Facturacion\Xml;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Validador local del XML CFDI antes de enviarlo a TBT.
 *
 * Filosofía: defensa en profundidad. TBT hace la validación oficial vs Anexo 20;
 * nosotros solo cazamos errores groseros antes de quemar timbres (atributos
 * faltantes, sumas que no cuadran, tasas fuera de rango, etc).
 *
 * Retorna ['valid' => bool, 'errors' => string[]].
 */
class CfdiXmlValidator
{
    public const NS_CFDI = 'http://www.sat.gob.mx/cfd/4';
    public const NS_IMPLOCAL = 'http://www.sat.gob.mx/implocal';

    public function validate(DOMDocument $doc): array
    {
        $errors = [];

        $root = $doc->documentElement;
        if (!$root || $root->namespaceURI !== self::NS_CFDI || $root->localName !== 'Comprobante') {
            return ['valid' => false, 'errors' => ['Documento raíz no es {cfdi:Comprobante}']];
        }

        $this->validarComprobanteBasico($root, $errors);
        $this->validarImpuestosLocales($doc, $errors);

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function validarComprobanteBasico(DOMElement $root, array &$errors): void
    {
        foreach (['Version', 'Fecha', 'SubTotal', 'Total', 'TipoDeComprobante', 'Moneda', 'LugarExpedicion', 'Exportacion'] as $attr) {
            if (!$root->hasAttribute($attr)) {
                $errors[] = "Atributo requerido faltante en Comprobante: {$attr}";
            }
        }
        if ($root->getAttribute('Version') !== '4.0') {
            $errors[] = "Version del CFDI debe ser '4.0', recibido '{$root->getAttribute('Version')}'";
        }
    }

    private function validarImpuestosLocales(DOMDocument $doc, array &$errors): void
    {
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('cfdi', self::NS_CFDI);
        $xpath->registerNamespace('implocal', self::NS_IMPLOCAL);

        $nodes = $xpath->query('//cfdi:Complemento/implocal:ImpuestosLocales');
        if ($nodes === false || $nodes->length === 0) {
            return; // no hay implocal, no hay nada que validar
        }
        if ($nodes->length > 1) {
            $errors[] = 'Solo puede haber un nodo implocal:ImpuestosLocales';
            return;
        }

        $impl = $nodes->item(0);
        if (!$impl instanceof DOMElement) {
            return;
        }

        if ($impl->getAttribute('version') !== '1.0') {
            $errors[] = "implocal:ImpuestosLocales version debe ser '1.0'";
        }

        $totalRet = (float) $impl->getAttribute('TotaldeRetenciones');
        $totalTras = (float) $impl->getAttribute('TotaldeTraslados');

        $sumaRet = 0.0;
        $sumaTras = 0.0;

        $retenciones = $xpath->query('implocal:RetencionesLocales', $impl);
        $traslados = $xpath->query('implocal:TrasladosLocales', $impl);

        if (($retenciones->length + $traslados->length) === 0) {
            $errors[] = 'implocal:ImpuestosLocales debe tener al menos un nodo Retencion o Traslado';
        }

        foreach ($retenciones as $r) {
            $this->validarLineaImpuestoLocal($r, 'retencion', $errors);
            $sumaRet += (float) $r->getAttribute('Importe');
        }
        foreach ($traslados as $t) {
            $this->validarLineaImpuestoLocal($t, 'traslado', $errors);
            $sumaTras += (float) $t->getAttribute('Importe');
        }

        if (abs($totalRet - $sumaRet) > 0.01) {
            $errors[] = sprintf(
                'TotaldeRetenciones (%.2f) no coincide con suma de Importes (%.2f)',
                $totalRet, $sumaRet
            );
        }
        if (abs($totalTras - $sumaTras) > 0.01) {
            $errors[] = sprintf(
                'TotaldeTraslados (%.2f) no coincide con suma de Importes (%.2f)',
                $totalTras, $sumaTras
            );
        }
    }

    private function validarLineaImpuestoLocal(DOMElement $node, string $tipo, array &$errors): void
    {
        $nombreAttr = $tipo === 'retencion' ? 'ImpLocRetenido' : 'ImpLocTrasladado';
        $tasaAttr = $tipo === 'retencion' ? 'TasadeRetencion' : 'TasadeTraslado';

        foreach ([$nombreAttr, $tasaAttr, 'Importe'] as $attr) {
            if (!$node->hasAttribute($attr)) {
                $errors[] = "implocal {$tipo}: atributo {$attr} requerido";
                return;
            }
        }

        $nombre = $node->getAttribute($nombreAttr);
        $len = mb_strlen($nombre);
        if ($len < 3 || $len > 100) {
            $errors[] = "implocal {$tipo}: {$nombreAttr} debe tener 3-100 caracteres (actual: {$len})";
        }

        $tasa = (float) $node->getAttribute($tasaAttr);
        if ($tasa < 0 || $tasa > 100) {
            $errors[] = "implocal {$tipo}: {$tasaAttr} fuera de rango 0-100 (actual: {$tasa})";
        }

        $importe = (float) $node->getAttribute('Importe');
        if ($importe < 0) {
            $errors[] = "implocal {$tipo}: Importe no puede ser negativo (actual: {$importe})";
        }
    }
}
