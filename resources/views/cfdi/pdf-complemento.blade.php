<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Complemento de Pago {{ $complemento->serie }}-{{ $complemento->folio }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 9px; color: #222; line-height: 1.3; }
        .page { padding: 15px 25px; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .header-table td { vertical-align: top; padding: 0; }
        .logo-cell { width: 90px; padding-right: 10px; }
        .logo-cell img { max-width: 85px; max-height: 70px; }
        .emisor-cell { padding-top: 2px; }
        .emisor-name { font-size: 13px; font-weight: bold; color: #111; margin-bottom: 2px; }
        .emisor-dato { font-size: 8px; color: #444; line-height: 1.4; }
        .factura-cell { width: 200px; text-align: right; vertical-align: top; }
        .factura-badge { background-color: #2c7a40; color: #fff; font-size: 10px; font-weight: bold; padding: 6px 15px; text-align: center; display: inline-block; letter-spacing: 1px; }
        .factura-info { margin-top: 4px; text-align: right; font-size: 7px; color: #444; line-height: 1.3; }
        .factura-info strong { color: #111; }
        .serie-folio { font-size: 14px; font-weight: bold; color: #2c7a40; text-align: right; margin-top: 2px; }

        .bar { background-color: #2c7a40; color: #fff; font-size: 8px; font-weight: bold; padding: 3px 8px; margin: 6px 0 4px 0; text-transform: uppercase; letter-spacing: 0.5px; }

        .info-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 2px; }
        .info-table td { padding: 1px 4px; vertical-align: top; }
        .info-table .lbl { color: #666; font-weight: bold; width: 140px; white-space: nowrap; }
        .info-table .val { color: #222; }
        .info-table .lbl-r { color: #666; font-weight: bold; width: 110px; white-space: nowrap; }

        .conceptos { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .conceptos th { background-color: #2c7a40; color: #fff; font-size: 7px; padding: 4px 3px; text-align: left; text-transform: uppercase; letter-spacing: 0.3px; }
        .conceptos th.r { text-align: right; }
        .conceptos th.c { text-align: center; }
        .conceptos td { font-size: 8px; padding: 3px; border-bottom: 1px solid #ddd; color: #333; }
        .conceptos td.r { text-align: right; }
        .conceptos td.c { text-align: center; }

        .pago-table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 8px; }
        .pago-table th { background-color: #e6f1ea; color: #2c7a40; font-size: 7px; padding: 3px 4px; text-align: left; text-transform: uppercase; }
        .pago-table th.r { text-align: right; }
        .pago-table th.c { text-align: center; }
        .pago-table td { padding: 2px 4px; border-bottom: 1px solid #eee; }
        .pago-table td.r { text-align: right; }
        .pago-table td.c { text-align: center; }
        .pago-table td.b { font-weight: bold; }

        .summary-row { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .summary-row td { padding: 0; vertical-align: top; }
        .summary-spacer { width: 50%; }
        .summary-box { width: 50%; background-color: #f5fbf7; padding: 6px 8px; border-left: 3px solid #2c7a40; font-size: 9px; }
        .summary-box .lbl { color: #555; font-weight: bold; }
        .summary-box .val { color: #111; font-weight: bold; }

        .sello-bar { background-color: #2c7a40; color: #fff; font-size: 7px; font-weight: bold; padding: 2px 6px; margin-top: 5px; text-transform: uppercase; letter-spacing: 0.3px; }
        .sello-text { font-size: 6px; color: #333; word-wrap: break-word; word-break: break-all; line-height: 1.3; padding: 2px 4px; background-color: #fafafa; border: 1px solid #e0e0e0; border-top: none; font-family: 'Courier New', Courier, monospace; }

        .qr-cadena-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .qr-cadena-table td { vertical-align: top; padding: 0; }
        .qr-cell { width: 115px; padding-left: 8px; vertical-align: middle; }
        .qr-cell img { width: 105px; height: 105px; }
        .cadena-cell { vertical-align: top; }

        .disclaimer { font-size: 7px; color: #888; text-align: center; margin-top: 8px; padding-top: 4px; border-top: 1px solid #ddd; }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
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
                <div class="factura-badge">COMPLEMENTO DE PAGO</div>
                <div class="serie-folio">{{ $complemento->serie }}-{{ $complemento->folio }}</div>
                <div class="factura-info">
                    <strong>Folio Fiscal:</strong><br>
                    <span style="font-size: 6.5px;">{{ $complemento->uuid }}</span><br>
                    @if($noCertificadoEmisor)
                        <strong>No. Cert. Emisor:</strong> {{ $noCertificadoEmisor }}<br>
                    @endif
                    @if($timbreFiscal && isset($timbreFiscal['num_certificado_sat']))
                        <strong>No. Cert. SAT:</strong> {{ $timbreFiscal['num_certificado_sat'] }}<br>
                    @endif
                    @if($complemento->fecha_timbrado)
                        <strong>Fecha de Certificaci&oacute;n:</strong><br>
                        {{ \Carbon\Carbon::parse($complemento->fecha_timbrado)->format('Y-m-d H:i:s') }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- DATOS DEL COMPROBANTE --}}
    <div class="bar">Datos del Comprobante</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Fecha y Hora de Emisi&oacute;n:</td>
            <td class="val">{{ \Carbon\Carbon::parse($complemento->fecha_emision)->format('Y-m-d H:i:s') }}</td>
            <td class="lbl-r">Tipo Comprobante:</td>
            <td class="val">P - Pago</td>
        </tr>
        <tr>
            <td class="lbl">Moneda:</td>
            <td class="val">XXX</td>
            <td class="lbl-r">Lugar de Expedici&oacute;n:</td>
            <td class="val">{{ $emisor->codigo_postal }}</td>
        </tr>
    </table>

    {{-- RECEPTOR --}}
    <div class="bar">Receptor</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Raz&oacute;n Social:</td>
            <td class="val">{{ $invoice->receptor_nombre }}</td>
            <td class="lbl-r">Domicilio Fiscal:</td>
            <td class="val">{{ $invoice->receptor_cp }}</td>
        </tr>
        <tr>
            <td class="lbl">RFC:</td>
            <td class="val">{{ $invoice->receptor_rfc }}</td>
            <td class="lbl-r">R&eacute;gimen Fiscal:</td>
            <td class="val">{{ $invoice->receptor_regimen }} - {{ $catalogos['regimen'][$invoice->receptor_regimen] ?? '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Uso del CFDI:</td>
            <td class="val" colspan="3">CP01 - Pagos</td>
        </tr>
    </table>

    {{-- CONCEPTOS (uno simbólico, monto cero) --}}
    <div class="bar">Conceptos</div>
    <table class="conceptos">
        <thead>
            <tr>
                <th class="c" style="width: 35px;">CANT.</th>
                <th style="width: 50px;">UNIDAD</th>
                <th style="width: 70px;">CLAVE</th>
                <th style="width: 50px;">OBJ. IMP.</th>
                <th>DESCRIPCI&Oacute;N</th>
                <th class="r" style="width: 80px;">P. UNITARIO</th>
                <th class="r" style="width: 80px;">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="c">1</td>
                <td>ACT</td>
                <td>84111506</td>
                <td>01</td>
                <td>Pago</td>
                <td class="r">$0.00</td>
                <td class="r">$0.00</td>
            </tr>
        </tbody>
    </table>

    {{-- COMPLEMENTO DE PAGO --}}
    <div class="bar">Complemento de Pago 2.0</div>
    <table class="pago-table">
        <tr>
            <th>Fecha del Pago:</th>
            <td>{{ $fechaPagoFmt }}</td>
            <th>Forma de Pago:</th>
            <td>{{ $complemento->forma_pago }} - {{ $catalogos['forma_pago'][$complemento->forma_pago] ?? '' }}</td>
            <th>Moneda:</th>
            <td>MXN</td>
            <th class="r">Monto del Pago:</th>
            <td class="r b">${{ number_format($complemento->monto, 2) }}</td>
        </tr>
    </table>

    <div class="bar" style="margin-top: 6px;">Documento Relacionado</div>
    <table class="pago-table" style="border: 1px solid #d4e6dc;">
        <thead>
            <tr>
                <th>Folio Fiscal (UUID)</th>
                <th class="c">Serie-Folio</th>
                <th class="c">Met. Pago</th>
                <th class="c">Moneda</th>
                <th class="c">T. Cam.</th>
                <th class="c">Parcialidad</th>
                <th class="r">Saldo Anterior</th>
                <th class="r">Importe Pagado</th>
                <th class="r">Saldo Insoluto</th>
                <th class="c">Obj. Imp.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-size: 6.5px;">{{ $invoice->uuid }}</td>
                <td class="c">{{ $invoice->serie }}-{{ $invoice->folio }}</td>
                <td class="c">{{ $invoice->metodo_pago ?? 'PPD' }}</td>
                <td class="c">{{ $monedaDr ?? 'MXN' }}</td>
                <td class="c">{{ number_format((float) ($equivalenciaDr ?? 1), 4) }}</td>
                <td class="c">{{ $complemento->num_parcialidad }}</td>
                <td class="r">${{ number_format($complemento->imp_saldo_ant, 2) }}</td>
                <td class="r b">${{ number_format($complemento->imp_pagado, 2) }}</td>
                <td class="r">${{ number_format($complemento->imp_saldo_insoluto, 2) }}</td>
                <td class="c">{{ $tieneIva ? '02' : '01' }}</td>
            </tr>
        </tbody>
    </table>

    @if($tieneInfoBancaria)
        <div class="bar" style="margin-top: 6px;">Informaci&oacute;n Bancaria del Pago</div>
        <table class="pago-table" style="border: 1px solid #d4e6dc;">
            <tbody>
                @if($infoBancaria['num_operacion'])
                <tr>
                    <th style="width: 220px;">N&uacute;mero de Operaci&oacute;n:</th>
                    <td colspan="3">{{ $infoBancaria['num_operacion'] }}</td>
                </tr>
                @endif
                @if($infoBancaria['rfc_emisor_cta_ord'] || $infoBancaria['cta_ordenante'] || $infoBancaria['nom_banco_ord_ext'])
                <tr>
                    <th>RFC Banco Ordenante (Cliente):</th>
                    <td style="width: 220px;">{{ $infoBancaria['rfc_emisor_cta_ord'] ?? '—' }}</td>
                    <th style="width: 130px;">Cuenta Ordenante:</th>
                    <td>{{ $infoBancaria['cta_ordenante'] ?? '—' }}</td>
                </tr>
                @if($infoBancaria['nom_banco_ord_ext'])
                <tr>
                    <th>Banco Ordenante (Extranjero):</th>
                    <td colspan="3">{{ $infoBancaria['nom_banco_ord_ext'] }}</td>
                </tr>
                @endif
                @endif
                @if($infoBancaria['rfc_emisor_cta_ben'] || $infoBancaria['cta_beneficiario'])
                <tr>
                    <th>RFC Banco Beneficiario:</th>
                    <td>{{ $infoBancaria['rfc_emisor_cta_ben'] ?? '—' }}</td>
                    <th>Cuenta Beneficiaria:</th>
                    <td>{{ $infoBancaria['cta_beneficiario'] ?? '—' }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if($tieneIva)
        <div class="bar" style="margin-top: 6px;">Impuestos del Pago (Traslados)</div>
        <table class="pago-table" style="border: 1px solid #d4e6dc;">
            <thead>
                <tr>
                    <th>Impuesto</th>
                    <th>Tipo Factor</th>
                    <th class="r">Tasa o Cuota</th>
                    <th class="r">Base</th>
                    <th class="r">Importe</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>002 - IVA</td>
                    <td>Tasa</td>
                    <td class="r">{{ number_format($taxRate * 100, 2) }}%</td>
                    <td class="r">${{ number_format($baseDr, 2) }}</td>
                    <td class="r b">${{ number_format($importeDr, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if($tieneRetenciones)
        <div class="bar" style="margin-top: 6px;">Impuestos del Pago (Retenciones)</div>
        <table class="pago-table" style="border: 1px solid #f3d4c4;">
            <thead>
                <tr>
                    <th>Impuesto</th>
                    <th>Tipo Factor</th>
                    <th class="r">Tasa o Cuota</th>
                    <th class="r">Base</th>
                    <th class="r">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retencionesDr as $ret)
                <tr>
                    <td>{{ $ret['codigo'] }} - {{ $ret['nombre'] }}</td>
                    <td>Tasa</td>
                    <td class="r">{{ $ret['tasa'] }}</td>
                    <td class="r">${{ number_format($ret['base'], 2) }}</td>
                    <td class="r b" style="color:#b34700;">${{ number_format($ret['importe'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- RESUMEN MONTO TOTAL DEL PAGO --}}
    <table class="summary-row">
        <tr>
            <td class="summary-spacer"></td>
            <td class="summary-box">
                <table style="width:100%;">
                    <tr>
                        <td class="lbl">Monto Total Pagos:</td>
                        <td class="val r">${{ number_format($complemento->monto, 2) }}</td>
                    </tr>
                    @if($tieneIva)
                    <tr>
                        <td class="lbl">Total Traslados Base IVA 16%:</td>
                        <td class="val r">${{ number_format($baseDr, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Total Traslados Imp. IVA 16%:</td>
                        <td class="val r">${{ number_format($importeDr, 2) }}</td>
                    </tr>
                    @endif
                    @if($totalRetIsr > 0)
                    <tr>
                        <td class="lbl" style="color:#b34700;">Total Retenciones ISR:</td>
                        <td class="val r" style="color:#b34700;">-${{ number_format($totalRetIsr, 2) }}</td>
                    </tr>
                    @endif
                    @if($totalRetIva > 0)
                    <tr>
                        <td class="lbl" style="color:#b34700;">Total Retenciones IVA:</td>
                        <td class="val r" style="color:#b34700;">-${{ number_format($totalRetIva, 2) }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- SELLOS Y QR --}}
    @if($timbreFiscal)
        <div class="sello-bar">Sello Digital del Emisor</div>
        <div class="sello-text">{{ $timbreFiscal['sello'] ?? '' }}</div>
        <div class="sello-bar" style="margin-top: 3px;">Sello Digital del SAT</div>
        <div class="sello-text">{{ $timbreFiscal['sello_sat'] ?? '' }}</div>
        <div class="sello-bar" style="margin-top: 3px;">Cadena Original del Complemento de Certificaci&oacute;n Digital del SAT</div>
        <div class="sello-text">||1.1|{{ $complemento->uuid }}|{{ $complemento->fecha_timbrado ? \Carbon\Carbon::parse($complemento->fecha_timbrado)->format('Y-m-d\TH:i:s') : '' }}|{{ $timbreFiscal['rfc_certifico'] ?? '' }}|{{ $timbreFiscal['sello'] ?? '' }}|{{ $timbreFiscal['num_certificado_sat'] ?? '' }}||</div>
    @endif

    @if($qrBase64)
        <table class="qr-cadena-table">
            <tr>
                <td class="cadena-cell">
                    <div style="font-size: 7px; color: #666; margin-top: 6px;">
                        Este documento es una representaci&oacute;n impresa de un CFDI tipo Pago (P).
                    </div>
                </td>
                <td class="qr-cell">
                    <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR SAT">
                </td>
            </tr>
        </table>
    @endif

    <div class="disclaimer">
        Documento de cortes&iacute;a. El comprobante fiscal v&aacute;lido es el archivo XML.
    </div>
</div>
</body>
</html>
