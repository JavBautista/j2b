<?php

namespace App\Services\Facturacion\Xml;

use DOMDocument;
use DOMElement;

/**
 * Construye un CFDI 4.0 completo en DOMDocument a partir de un array $data
 * con la misma forma que se usa para timbrar por API JSON (más el campo
 * 'impuestos_locales' si aplica).
 *
 * Modo de uso:
 *   $doc = (new CfdiXmlBuilder())->build($data);
 *   $xml = $doc->saveXML();
 *
 * El XML sale SIN sellar: Sello, NoCertificado y Certificado quedan vacíos
 * porque TBT los completa al sellar con el CSD del panel.
 *
 * Estructura del array $data esperado (subset mínimo):
 *   - comprobante: serie, folio, fecha, forma_pago, metodo_pago,
 *                  subtotal, descuento (opt), moneda, total,
 *                  tipo_comprobante, lugar_expedicion, exportacion
 *   - emisor: rfc, nombre, regimen_fiscal
 *   - receptor: rfc, nombre, regimen_fiscal, uso_cfdi, domicilio_fiscal
 *   - conceptos[]: clave_prod_serv, cantidad, clave_unidad, descripcion,
 *                  valor_unitario, importe, objeto_imp, descuento (opt),
 *                  traslados[], retenciones[]
 *   - impuestos: total_impuestos_trasladados, total_impuestos_retenidos,
 *                traslados[], retenciones[]
 *   - impuestos_locales[]: tipo, nombre, tasa_porcentaje, base, importe
 */
class CfdiXmlBuilder
{
    public const NS_CFDI = 'http://www.sat.gob.mx/cfd/4';
    public const NS_IMPLOCAL = 'http://www.sat.gob.mx/implocal';
    public const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function build(array $data): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $comprobante = $this->createComprobante($doc, $data['comprobante'] ?? []);
        $doc->appendChild($comprobante);

        $comprobante->appendChild($this->createEmisor($doc, $data['emisor'] ?? []));
        $comprobante->appendChild($this->createReceptor($doc, $data['receptor'] ?? []));
        $comprobante->appendChild($this->createConceptos($doc, $data['conceptos'] ?? []));

        if (!empty($data['impuestos'])) {
            $comprobante->appendChild($this->createImpuestos($doc, $data['impuestos']));
        }

        if (!empty($data['impuestos_locales'])) {
            $complemento = $doc->createElementNS(self::NS_CFDI, 'cfdi:Complemento');
            $implocal = (new ImplocalNodeBuilder())->build($doc, $data['impuestos_locales']);
            $complemento->appendChild($implocal);
            $comprobante->appendChild($complemento);
        }

        return $doc;
    }

    private function createComprobante(DOMDocument $doc, array $c): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Comprobante');
        $el->setAttribute('xmlns:implocal', self::NS_IMPLOCAL);
        $el->setAttributeNS(self::NS_XSI, 'xsi:schemaLocation',
            'http://www.sat.gob.mx/cfd/4 http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd ' .
            'http://www.sat.gob.mx/implocal http://www.sat.gob.mx/sitio_internet/cfd/implocal/implocal.xsd'
        );

        $el->setAttribute('Version', '4.0');
        if (!empty($c['serie']))  $el->setAttribute('Serie', (string) $c['serie']);
        if (!empty($c['folio']))  $el->setAttribute('Folio', (string) $c['folio']);
        $el->setAttribute('Fecha', (string) ($c['fecha'] ?? ''));
        $el->setAttribute('Sello', '');
        if (!empty($c['forma_pago']))    $el->setAttribute('FormaPago', (string) $c['forma_pago']);
        $el->setAttribute('NoCertificado', '');
        $el->setAttribute('Certificado', '');
        $el->setAttribute('SubTotal', $this->fmt($c['subtotal'] ?? 0));
        if (isset($c['descuento']) && (float) $c['descuento'] > 0) {
            $el->setAttribute('Descuento', $this->fmt($c['descuento']));
        }
        $el->setAttribute('Moneda', (string) ($c['moneda'] ?? 'MXN'));
        $el->setAttribute('Total', $this->fmt($c['total'] ?? 0));
        $el->setAttribute('TipoDeComprobante', (string) ($c['tipo_comprobante'] ?? 'I'));
        if (!empty($c['metodo_pago'])) $el->setAttribute('MetodoPago', (string) $c['metodo_pago']);
        $el->setAttribute('LugarExpedicion', (string) ($c['lugar_expedicion'] ?? ''));
        $el->setAttribute('Exportacion', (string) ($c['exportacion'] ?? '01'));

        return $el;
    }

    private function createEmisor(DOMDocument $doc, array $e): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Emisor');
        $el->setAttribute('Rfc', (string) ($e['rfc'] ?? ''));
        $el->setAttribute('Nombre', (string) ($e['nombre'] ?? ''));
        $el->setAttribute('RegimenFiscal', (string) ($e['regimen_fiscal'] ?? ''));
        return $el;
    }

    private function createReceptor(DOMDocument $doc, array $r): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Receptor');
        $el->setAttribute('Rfc', (string) ($r['rfc'] ?? ''));
        $el->setAttribute('Nombre', (string) ($r['nombre'] ?? ''));
        $el->setAttribute('DomicilioFiscalReceptor', (string) ($r['domicilio_fiscal'] ?? ''));
        $el->setAttribute('RegimenFiscalReceptor', (string) ($r['regimen_fiscal'] ?? ''));
        $el->setAttribute('UsoCFDI', (string) ($r['uso_cfdi'] ?? ''));
        return $el;
    }

    private function createConceptos(DOMDocument $doc, array $conceptos): DOMElement
    {
        $cont = $doc->createElementNS(self::NS_CFDI, 'cfdi:Conceptos');
        foreach ($conceptos as $c) {
            $cont->appendChild($this->createConcepto($doc, $c));
        }
        return $cont;
    }

    private function createConcepto(DOMDocument $doc, array $c): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Concepto');
        $el->setAttribute('ClaveProdServ', (string) ($c['clave_prod_serv'] ?? ''));
        $el->setAttribute('Cantidad', (string) ($c['cantidad'] ?? '1'));
        $el->setAttribute('ClaveUnidad', (string) ($c['clave_unidad'] ?? ''));
        $el->setAttribute('Descripcion', (string) ($c['descripcion'] ?? ''));
        $el->setAttribute('ValorUnitario', $this->fmt($c['valor_unitario'] ?? 0));
        $el->setAttribute('Importe', $this->fmt($c['importe'] ?? 0));
        $el->setAttribute('ObjetoImp', (string) ($c['objeto_imp'] ?? '02'));
        if (isset($c['descuento']) && (float) $c['descuento'] > 0) {
            $el->setAttribute('Descuento', $this->fmt($c['descuento']));
        }

        if (!empty($c['traslados']) || !empty($c['retenciones'])) {
            $imp = $doc->createElementNS(self::NS_CFDI, 'cfdi:Impuestos');
            if (!empty($c['traslados'])) {
                $cont = $doc->createElementNS(self::NS_CFDI, 'cfdi:Traslados');
                foreach ($c['traslados'] as $t) {
                    $cont->appendChild($this->createTrasladoConcepto($doc, $t));
                }
                $imp->appendChild($cont);
            }
            if (!empty($c['retenciones'])) {
                $cont = $doc->createElementNS(self::NS_CFDI, 'cfdi:Retenciones');
                foreach ($c['retenciones'] as $r) {
                    $cont->appendChild($this->createRetencionConcepto($doc, $r));
                }
                $imp->appendChild($cont);
            }
            $el->appendChild($imp);
        }

        return $el;
    }

    private function createTrasladoConcepto(DOMDocument $doc, array $t): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Traslado');
        $el->setAttribute('Base', $this->fmt($t['base'] ?? 0));
        $el->setAttribute('Impuesto', (string) ($t['impuesto'] ?? '002'));
        $el->setAttribute('TipoFactor', (string) ($t['tipo_factor'] ?? 'Tasa'));
        $el->setAttribute('TasaOCuota', (string) ($t['tasa_cuota'] ?? '0.160000'));
        $el->setAttribute('Importe', $this->fmt($t['importe'] ?? 0));
        return $el;
    }

    private function createRetencionConcepto(DOMDocument $doc, array $r): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Retencion');
        $el->setAttribute('Base', $this->fmt($r['base'] ?? 0));
        $el->setAttribute('Impuesto', (string) ($r['impuesto'] ?? '001'));
        $el->setAttribute('TipoFactor', (string) ($r['tipo_factor'] ?? 'Tasa'));
        $el->setAttribute('TasaOCuota', (string) ($r['tasa_cuota'] ?? '0.100000'));
        $el->setAttribute('Importe', $this->fmt($r['importe'] ?? 0));
        return $el;
    }

    private function createImpuestos(DOMDocument $doc, array $imp): DOMElement
    {
        $el = $doc->createElementNS(self::NS_CFDI, 'cfdi:Impuestos');
        if (isset($imp['total_impuestos_retenidos'])) {
            $el->setAttribute('TotalImpuestosRetenidos', $this->fmt($imp['total_impuestos_retenidos']));
        }
        if (isset($imp['total_impuestos_trasladados'])) {
            $el->setAttribute('TotalImpuestosTrasladados', $this->fmt($imp['total_impuestos_trasladados']));
        }

        if (!empty($imp['retenciones'])) {
            $cont = $doc->createElementNS(self::NS_CFDI, 'cfdi:Retenciones');
            foreach ($imp['retenciones'] as $r) {
                $n = $doc->createElementNS(self::NS_CFDI, 'cfdi:Retencion');
                $n->setAttribute('Impuesto', (string) ($r['impuesto'] ?? ''));
                $n->setAttribute('Importe', $this->fmt($r['importe'] ?? 0));
                $cont->appendChild($n);
            }
            $el->appendChild($cont);
        }

        if (!empty($imp['traslados'])) {
            $cont = $doc->createElementNS(self::NS_CFDI, 'cfdi:Traslados');
            foreach ($imp['traslados'] as $t) {
                $cont->appendChild($this->createTrasladoConcepto($doc, $t));
            }
            $el->appendChild($cont);
        }

        return $el;
    }

    private function fmt($n): string
    {
        return number_format((float) $n, 2, '.', '');
    }
}
