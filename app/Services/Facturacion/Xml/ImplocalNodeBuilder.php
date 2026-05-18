<?php

namespace App\Services\Facturacion\Xml;

use DOMDocument;
use DOMElement;

/**
 * Construye el nodo <implocal:ImpuestosLocales> v1.0 del complemento SAT.
 *
 * Input esperado:
 *   [
 *     ['tipo' => 'retencion'|'traslado', 'nombre' => 'CEDULAR', 'tasa_porcentaje' => 5.00, 'base' => 2500.00, 'importe' => 125.00],
 *     ...
 *   ]
 *
 * Output: DOMElement listo para appendChild dentro de <cfdi:Complemento>.
 */
class ImplocalNodeBuilder
{
    public const NS = 'http://www.sat.gob.mx/implocal';

    public function build(DOMDocument $doc, array $impuestos): DOMElement
    {
        $totalRet = 0.0;
        $totalTras = 0.0;
        foreach ($impuestos as $imp) {
            $importe = (float) $imp['importe'];
            if (($imp['tipo'] ?? '') === 'retencion') {
                $totalRet += $importe;
            } else {
                $totalTras += $importe;
            }
        }

        $root = $doc->createElementNS(self::NS, 'implocal:ImpuestosLocales');
        $root->setAttribute('version', '1.0');
        $root->setAttribute('TotaldeRetenciones', $this->fmt($totalRet));
        $root->setAttribute('TotaldeTraslados', $this->fmt($totalTras));

        foreach ($impuestos as $imp) {
            $isRet = ($imp['tipo'] ?? '') === 'retencion';
            $tagName = $isRet ? 'implocal:RetencionesLocales' : 'implocal:TrasladosLocales';
            $nodo = $doc->createElementNS(self::NS, $tagName);

            $nombreAttr = $isRet ? 'ImpLocRetenido' : 'ImpLocTrasladado';
            $tasaAttr = $isRet ? 'TasadeRetencion' : 'TasadeTraslado';

            $nodo->setAttribute($nombreAttr, (string) $imp['nombre']);
            $nodo->setAttribute($tasaAttr, $this->fmt((float) $imp['tasa_porcentaje']));
            $nodo->setAttribute('Importe', $this->fmt((float) $imp['importe']));

            $root->appendChild($nodo);
        }

        return $root;
    }

    private function fmt(float $n): string
    {
        return number_format($n, 2, '.', '');
    }
}
