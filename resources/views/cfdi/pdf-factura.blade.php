<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->serie }}-{{ $invoice->folio }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #222; line-height: 1.3; }
        .page { padding: 15px 25px; }

        /* === HEADER === */
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .header-table td { vertical-align: top; padding: 0; }
        .logo-cell { width: 90px; padding-right: 10px; }
        .logo-cell img { max-width: 85px; max-height: 70px; }
        .emisor-cell { padding-top: 2px; }
        .emisor-name { font-size: 13px; font-weight: bold; color: #111; margin-bottom: 2px; }
        .emisor-dato { font-size: 8px; color: #444; line-height: 1.4; }
        .factura-cell { width: 180px; text-align: right; vertical-align: top; }
        .factura-badge { background-color: #1a4d8f; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 15px; text-align: center; display: inline-block; letter-spacing: 1px; }
        .factura-info { margin-top: 4px; text-align: right; font-size: 7px; color: #444; line-height: 1.3; }
        .factura-info strong { color: #111; }
        .serie-folio { font-size: 14px; font-weight: bold; color: #1a4d8f; text-align: right; margin-top: 2px; }

        /* === BARRA AZUL SEPARADOR === */
        .bar-blue { background-color: #1a4d8f; color: #fff; font-size: 8px; font-weight: bold; padding: 3px 8px; margin: 6px 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px; }

        /* === INFO GRID === */
        .info-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 2px; }
        .info-table td { padding: 1px 4px; vertical-align: top; }
        .info-table .lbl { color: #666; font-weight: bold; width: 140px; white-space: nowrap; }
        .info-table .val { color: #222; }
        .info-table .lbl-r { color: #666; font-weight: bold; width: 110px; white-space: nowrap; }

        /* === TABLA CONCEPTOS === */
        .conceptos { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .conceptos th {
            background-color: #1a4d8f; color: #fff; font-size: 7px;
            padding: 4px 3px; text-align: left; text-transform: uppercase; letter-spacing: 0.3px;
        }
        .conceptos th.r { text-align: right; }
        .conceptos th.c { text-align: center; }
        .conceptos td { font-size: 8px; padding: 3px; border-bottom: 1px solid #ddd; color: #333; }
        .conceptos td.r { text-align: right; }
        .conceptos td.c { text-align: center; }

        /* === IMPUESTOS DESGLOSE === */
        .impuestos-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8px; }
        .impuestos-table th { background-color: #e8edf3; color: #1a4d8f; font-size: 7px; padding: 3px 4px; text-align: left; text-transform: uppercase; }
        .impuestos-table th.r { text-align: right; }
        .impuestos-table td { padding: 2px 4px; border-bottom: 1px solid #eee; }
        .impuestos-table td.r { text-align: right; }

        /* === TOTALES === */
        .totales-row { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .totales-row td { padding: 0; vertical-align: top; }
        .totales-spacer { width: 60%; }
        .totales-box { width: 40%; }
        .tot-table { width: 100%; border-collapse: collapse; }
        .tot-table td { padding: 2px 5px; font-size: 9px; }
        .tot-table .tot-lbl { text-align: right; color: #555; font-weight: bold; border-bottom: 1px solid #eee; }
        .tot-table .tot-val { text-align: right; color: #222; border-bottom: 1px solid #eee; width: 100px; }
        .tot-table .tot-total .tot-lbl,
        .tot-table .tot-total .tot-val { border-top: 2px solid #1a4d8f; border-bottom: 2px solid #1a4d8f; font-size: 11px; font-weight: bold; color: #111; padding: 4px 5px; }

        /* === IMPORTE CON LETRA === */
        .importe-letra { font-size: 8px; color: #333; margin: 5px 0; padding: 3px 6px; background-color: #f5f6f8; border-left: 3px solid #1a4d8f; }
        .importe-letra strong { color: #1a4d8f; }

        /* === PIE INFO === */
        .pie-info { font-size: 8px; color: #444; margin: 4px 0 6px 0; line-height: 1.4; }
        .pie-info strong { color: #222; }

        /* === SELLOS DIGITALES === */
        .sello-bar { background-color: #1a4d8f; color: #fff; font-size: 7px; font-weight: bold; padding: 2px 6px; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.3px; }
        .sello-text { font-size: 6px; color: #333; word-wrap: break-word; word-break: break-all; line-height: 1.3; padding: 2px 4px; background-color: #fafafa; border: 1px solid #e0e0e0; border-top: none; font-family: 'Courier New', Courier, monospace; }

        /* === QR + CADENA ORIGINAL === */
        .qr-cadena-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .qr-cadena-table td { vertical-align: top; padding: 0; }
        .qr-cell { width: 115px; padding-left: 8px; vertical-align: middle; }
        .qr-cell img { width: 105px; height: 105px; }
        .cadena-cell { vertical-align: top; }

        /* === DISCLAIMER === */
        .disclaimer { font-size: 7px; color: #888; text-align: center; margin-top: 8px; padding-top: 4px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="page">

    {{-- =============== HEADER =============== --}}
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                @if($logoBase64)
                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="emisor-cell">
                <div class="emisor-name">{{ $emisor->razon_social }}</div>
                <div class="emisor-dato">R.F.C.: {{ $emisor->rfc }}</div>
                <div class="emisor-dato">R&eacute;gimen Fiscal: {{ $emisor->regimen_fiscal }} - {{ $catalogos['regimen'][$emisor->regimen_fiscal] ?? '' }}</div>
                <div class="emisor-dato">Lugar de Expedici&oacute;n: {{ $emisor->codigo_postal }}</div>
            </td>
            <td class="factura-cell">
                <div class="factura-badge">FACTURA</div>
                <div class="serie-folio">{{ $invoice->serie }}-{{ $invoice->folio }}</div>
                <div class="factura-info">
                    <strong>Folio Fiscal:</strong><br>
                    {{ $invoice->uuid }}<br>
                    @if($noCertificadoEmisor)
                    <strong>No. Certificado del Emisor:</strong><br>
                    {{ $noCertificadoEmisor }}<br>
                    @endif
                    @if($timbreFiscal)
                    <strong>No. Certificado SAT:</strong><br>
                    {{ $timbreFiscal['num_certificado_sat'] ?? '' }}<br>
                    @endif
                    <strong>Fecha y Hora de Certificaci&oacute;n:</strong><br>
                    {{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('Y-m-d H:i:s') : '' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- =============== DATOS DEL COMPROBANTE =============== --}}
    <div class="bar-blue">Datos del Comprobante</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Fecha y Hora de Emisi&oacute;n:</td>
            <td class="val">{{ $invoice->fecha_emision->format('Y-m-d H:i:s') }}</td>
            <td class="lbl-r">Tipo Comprobante:</td>
            <td class="val">{{ $invoice->tipo_comprobante }} - {{ $catalogos['tipo_comprobante'][$invoice->tipo_comprobante] ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Forma de Pago:</td>
            <td class="val">{{ $invoice->forma_pago }} - {{ $catalogos['forma_pago'][$invoice->forma_pago] ?? '' }}</td>
            <td class="lbl-r">M&eacute;todo de Pago:</td>
            <td class="val">{{ $invoice->metodo_pago }} - {{ $catalogos['metodo_pago'][$invoice->metodo_pago] ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Moneda:</td>
            <td class="val">{{ $requestData['moneda'] ?? 'MXN' }}</td>
            <td class="lbl-r">Tipo de Cambio:</td>
            <td class="val">{{ $responseData['tipo_cambio'] ?? '1' }}</td>
        </tr>
    </table>

    {{-- =============== RECEPTOR =============== --}}
    <div class="bar-blue">Receptor</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Raz&oacute;n Social:</td>
            <td class="val" colspan="3">{{ $invoice->receptor_nombre }}</td>
        </tr>
        <tr>
            <td class="lbl">RFC:</td>
            <td class="val">{{ $invoice->receptor_rfc }}</td>
            <td class="lbl-r">Domicilio Fiscal:</td>
            <td class="val">{{ $invoice->receptor_cp }}</td>
        </tr>
        <tr>
            <td class="lbl">Uso de CFDI:</td>
            <td class="val">{{ $invoice->receptor_uso_cfdi }} - {{ $catalogos['uso_cfdi'][$invoice->receptor_uso_cfdi] ?? '' }}</td>
            <td class="lbl-r">R&eacute;gimen Fiscal:</td>
            <td class="val">{{ $invoice->receptor_regimen }} - {{ $catalogos['regimen'][$invoice->receptor_regimen] ?? '' }}</td>
        </tr>
    </table>

    {{-- =============== CONCEPTOS =============== --}}
    <div class="bar-blue">Conceptos</div>
    <table class="conceptos">
        <thead>
            <tr>
                <th class="c" style="width:30px;">Cant.</th>
                <th style="width:50px;">Unidad</th>
                <th style="width:60px;">Clave</th>
                <th class="c" style="width:30px;">Obj. Imp.</th>
                <th>Descripci&oacute;n</th>
                <th class="r" style="width:72px;">P. Unitario</th>
                <th class="r" style="width:72px;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($conceptos as $concepto)
            <tr>
                <td class="c">{{ $concepto['cantidad'] }}</td>
                <td>{{ $concepto['clave_unidad'] }}</td>
                <td>{{ $concepto['clave_prod_serv'] }}</td>
                <td class="c">{{ $concepto['objeto_impuesto'] ?? '02' }}</td>
                <td>{{ $concepto['descripcion'] }}</td>
                <td class="r">${{ number_format($concepto['valor_unitario'], 2) }}</td>
                <td class="r">${{ number_format($concepto['importe'] ?? $concepto['subtotal'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- =============== DESGLOSE DE IMPUESTOS =============== --}}
    @php
        $impuestos = $requestData['impuestos'] ?? null;
    @endphp
    @if($impuestos && isset($impuestos['traslados']))
    <table class="impuestos-table">
        <thead>
            <tr>
                <th colspan="5">Desglose de Impuestos Trasladados</th>
            </tr>
            <tr>
                <th>Impuesto</th>
                <th>Tipo Factor</th>
                <th class="r">Base</th>
                <th class="r">Tasa o Cuota</th>
                <th class="r">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($impuestos['traslados'] as $traslado)
            <tr>
                <td>002 - IVA</td>
                <td>{{ $traslado['tipo_factor'] ?? 'Tasa' }}</td>
                <td class="r">${{ number_format($traslado['base'] ?? 0, 2) }}</td>
                <td class="r">{{ $traslado['tasa_cuota'] ?? '0.160000' }}</td>
                <td class="r">${{ number_format($traslado['importe'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- =============== TOTALES =============== --}}
    <table class="totales-row">
        <tr>
            <td class="totales-spacer"></td>
            <td class="totales-box">
                <table class="tot-table">
                    <tr>
                        <td class="tot-lbl">SUBTOTAL:</td>
                        <td class="tot-val">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->total_impuestos > 0)
                    <tr>
                        <td class="tot-lbl">IVA 16%:</td>
                        <td class="tot-val">${{ number_format($invoice->total_impuestos, 2) }}</td>
                    </tr>
                    @endif
                    <tr class="tot-total">
                        <td class="tot-lbl">TOTAL:</td>
                        <td class="tot-val">${{ number_format($invoice->total, 2) }} MXN</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- =============== IMPORTE CON LETRA =============== --}}
    <div class="importe-letra">
        <strong>IMPORTE CON LETRA:</strong> {{ $importeLetra ?? '' }}
    </div>

    {{-- =============== INFO PIE =============== --}}
    <div class="pie-info">
        <strong>Forma de Pago:</strong> {{ $invoice->forma_pago }} - {{ $catalogos['forma_pago'][$invoice->forma_pago] ?? '' }}<br>
        <strong>M&eacute;todo de Pago:</strong> {{ $invoice->metodo_pago }} - {{ $catalogos['metodo_pago'][$invoice->metodo_pago] ?? '' }}<br>
        <strong>Lugar de Expedici&oacute;n:</strong> {{ $emisor->codigo_postal }}<br>
        <strong>R&eacute;gimen Fiscal Emisor:</strong> {{ $emisor->regimen_fiscal }} - {{ $catalogos['regimen'][$emisor->regimen_fiscal] ?? '' }}
    </div>

    {{-- =============== SELLO DIGITAL DEL EMISOR =============== --}}
    @if($timbreFiscal)
    <div class="sello-bar">Sello Digital del Emisor</div>
    <div class="sello-text">{{ $timbreFiscal['sello'] ?? '' }}</div>

    {{-- =============== SELLO DIGITAL DEL SAT =============== --}}
    <div class="sello-bar">Sello Digital del SAT</div>
    <div class="sello-text">{{ $timbreFiscal['sello_sat'] ?? '' }}</div>

    {{-- =============== QR + CADENA ORIGINAL =============== --}}
    <table class="qr-cadena-table">
        <tr>
            <td class="cadena-cell">
                <div class="sello-bar">Cadena Original del Complemento de Certificaci&oacute;n Digital del SAT</div>
                <div class="sello-text">||1.1|{{ $timbreFiscal['uuid'] ?? '' }}|{{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('Y-m-d\TH:i:s') : '' }}|{{ $timbreFiscal['rfc_certifico'] ?? '' }}||{{ $timbreFiscal['sello'] ?? '' }}|{{ $timbreFiscal['num_certificado_sat'] ?? '' }}||</div>
            </td>
            <td class="qr-cell">
                @if(!empty($qrPath) && file_exists($qrPath))
                    <img src="{{ $qrPath }}" alt="QR">
                @elseif($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR">
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- =============== DISCLAIMER =============== --}}
    <div class="disclaimer">
        Este documento es una representaci&oacute;n impresa de un CFDI v4.0 &mdash; Verificar en: https://verificacfdi.facturaelectronica.sat.gob.mx
    </div>

</div>
</body>
</html>
