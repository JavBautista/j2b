{{--
    Plantilla PDF "Comyser" para Factura CFDI 4.0.
    Selector: shops.pdf_template = 'comyser' → Shop::pdfView('factura').
    Cambios futuros documentados en xdev/formatos_pdf/PLAN_FORMATOS_PDF.md
--}}
@php
    // Datos auxiliares
    $cliFiscal = $invoice->clientFiscalData;
    $cliReceipt = $invoice->receipt?->client;
    // Domicilio receptor (TODO P10 mejor: estructurar columnas en client_fiscal_data)
    $domicilioReceptor = '';
    if ($cliReceipt) {
        $domicilioReceptor = trim(
            ($cliReceipt->address ?? '') . ', ' .
            ($cliReceipt->city ?? '') . ', ' .
            ($cliReceipt->state ?? '')
        );
        $domicilioReceptor = trim($domicilioReceptor, ', ');
    }

    $vendedor = optional($invoice->receipt?->user)->name ?? 'Sistema';

    // Mapeo unidad rápido
    $mapUnidad = ['H87'=>'PZA','EA'=>'PZA','XPK'=>'PAQ','KGM'=>'KG','GRM'=>'GR','LTR'=>'LT','MLT'=>'ML',
                  'MTR'=>'MT','CMT'=>'CM','HUR'=>'HR','DAY'=>'DIA','MON'=>'MES','E48'=>'SVC','ACT'=>'ACT'];

    $monedaSufijo = ($requestData['moneda'] ?? 'MXN') === 'MXN' ? 'M.N.' : ($requestData['moneda'] ?? 'MXN');

    $esPPD = $invoice->metodo_pago === 'PPD';
    $tasaIva = $requestData['impuestos']['traslados'][0]['tasa_cuota'] ?? 0.16;
    $tasaIvaPct = number_format($tasaIva * 100, 0);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->serie }}-{{ $invoice->folio }}</title>
    <style>
        @page { margin: 10mm 10mm 10mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; margin: 0; line-height: 1.3; }

        /* ===== HEADER ===== */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .header td { vertical-align: top; padding: 0; }
        .logo-cell { width: 125px; padding-right: 6px; }
        .logo-cell img { max-width: 115px; max-height: 100px; }
        .titulo-cell { text-align: center; padding: 0 6px; }
        .titulo-cell h1 { font-size: 28px; font-weight: bold; margin: 0 0 3px 0; letter-spacing: 1.5px; color: #111; }
        .titulo-cell .shop-name { font-size: 13px; font-weight: bold; color: #333 !important; margin: 0 0 3px 0; }
        .titulo-cell .shop-data { font-size: 9.5px; color: #333 !important; line-height: 1.4; }
        .titulo-cell .shop-data * { color: #333 !important; }
        .folio-cell { width: 235px; }

        /* Caja folio: borde azul, fondo blanco, labels azules, valores negros (estilo Comyser) */
        .folio-box {
            border: 2px solid #16386b;
            border-radius: 5px;
            background: #fff;
        }
        .folio-box table { width: 100%; border-collapse: collapse; }
        .folio-box .lbl {
            color: #16386b !important;
            font-weight: bold;
            background: transparent !important;
            padding: 3px 8px;
            font-size: 8.5px;
            text-align: center;
            border-top: 1px solid #d6dde7;
        }
        .folio-box .val {
            color: #1a1a1a !important;
            text-align: center;
            padding: 3px 8px;
            font-size: 9.5px;
            word-wrap: break-word;
            word-break: break-all;
        }
        .folio-box tr:first-child .lbl { border-top: none; }
        .folio-box .val.folio { font-size: 15px; font-weight: bold; color: #16386b !important; }

        /* ===== BARRA AZUL ===== */
        .bar {
            background: #16386b;
            color: #fff !important;
            font-size: 11px;
            font-weight: bold;
            padding: 8px 12px;
            margin: 6px 0 3px 0;
            border-radius: 5px;
        }
        .bar .small-data { font-weight: normal; font-size: 10px; color: #fff !important; }

        /* ===== RECEPTOR ===== */
        .receptor-data { font-size: 9.5px; color: #333 !important; padding: 4px 10px 8px 10px; line-height: 1.45; }

        /* ===== CHIPS PAGO ===== */
        .chips-wrap { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .chips-wrap td { padding: 2px; text-align: center; }
        .chip {
            border: 1px solid #16386b;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 9px;
            display: inline-block;
            min-width: 100%;
            color: #1a1a1a !important;
            background: #fff;
        }

        /* ===== CFDI DISCLAIMER ===== */
        .cfdi-disclaimer { font-size: 9.5px; color: #333 !important; margin: 5px 0; font-style: italic; }

        /* ===== TABLA CONCEPTOS ===== */
        .conceptos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .conceptos th {
            font-size: 9px; font-weight: normal; color: #333 !important; text-align: center;
            padding: 5px 4px; border: 1.2px solid #555; background: #fff;
        }
        .conceptos td {
            font-size: 9px; color: #1a1a1a !important; padding: 4px 5px;
            border-left: 1.2px solid #555; border-right: 1.2px solid #555;
            vertical-align: top; line-height: 1.3;
        }
        .conceptos td.r { text-align: right; }
        .conceptos td.c { text-align: center; }
        .conceptos tbody tr:first-child td { border-top: 1.2px solid #555; }
        .conceptos tbody tr:last-child td { border-bottom: 1.2px solid #555; }
        .conceptos .col-cant   { width: 50px; }
        .conceptos .col-parte  { width: 75px; }
        .conceptos .col-unidad { width: 65px; }
        .conceptos .col-marca  { width: 75px; }
        .conceptos .col-precio { width: 72px; }
        .conceptos .col-importe{ width: 78px; }
        .conceptos tr.empty td { height: 20px; }

        /* ===== TOTAL EN LETRA ===== */
        .total-letra { font-size: 9.5px; color: #1a1a1a !important; margin: 6px 0 4px 0; }

        /* ===== PIE: TÉRMINOS + TOTALES ===== */
        .pie-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .pie-table td { vertical-align: top; padding: 0; }
        .pie-table .terms-cell { width: 62%; padding-right: 8px; }
        .pie-table .totales-cell { width: 38%; }
        .terms-box {
            border: 1.2px solid #555;
            border-radius: 5px;
            padding: 8px 10px;
        }
        .terms-box .terms-title { font-weight: bold; font-size: 10px; font-style: italic; color: #222 !important; margin: 0 0 4px 0; }
        .terms-box .terms-body { font-size: 9.5px; color: #444 !important; line-height: 1.45; margin: 0; }

        /* Wrapper redondea esquinas de la tabla de totales */
        .totales-wrap {
            border-radius: 5px;
            overflow: hidden;
            border: 1px solid #16386b;
        }
        .totales-tbl { width: 100%; border-collapse: collapse; }
        .totales-tbl td { padding: 6px 10px; }
        .totales-tbl .lbl { background: #16386b; color: #fff !important; font-weight: bold; font-size: 11px; text-align: left; width: 50%; border-right: 2px solid #fff; }
        .totales-tbl .val { background: #16386b; color: #fff !important; font-weight: bold; font-size: 11px; text-align: right; }
        .totales-tbl .total-final .lbl, .totales-tbl .total-final .val {
            background: #16386b; color: #fff !important; font-size: 14px; padding: 8px 10px;
        }

        /* ===== SELLOS + QR + CERT ===== */
        .sellos-wrap { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .sellos-wrap td { vertical-align: top; padding: 0; }
        .qr-cell { width: 95px; padding-right: 8px; text-align: center; }
        .qr-cell img { width: 90px; height: 90px; }
        .sellos-cell { padding-right: 8px; }
        .cert-cell { width: 185px; }
        .cert-box { border: 1.2px solid #16386b; border-radius: 5px; overflow: hidden; }
        .cert-box table { width: 100%; border-collapse: collapse; }
        .cert-box .lbl { background: #16386b; color: #fff !important; font-weight: bold; padding: 4px 6px; font-size: 8px; text-align: center; }
        .cert-box .val { padding: 3px 6px; font-size: 8.5px; text-align: center; word-wrap: break-word; word-break: break-all; color: #1a1a1a !important; }

        .sello-title { font-weight: bold; font-size: 9px; color: #16386b !important; margin: 5px 0 2px 0; }
        .sello-text { font-size: 7px; color: #333 !important; word-wrap: break-word; word-break: break-all; line-height: 1.3; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>

    {{-- ============== HEADER ============== --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if($logoBase64)
                    <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Logo">
                @endif
            </td>
            <td class="titulo-cell">
                <h1>FACTURA</h1>
                <div class="shop-name">{{ strtoupper($emisor->razon_social) }}</div>
                <div class="shop-data">
                    @if($emisor->codigo_postal) C.P. {{ $emisor->codigo_postal }} <br> @endif
                    Régimen Fiscal: {{ $emisor->regimen_fiscal }} - {{ $catalogos['regimen'][$emisor->regimen_fiscal] ?? '' }} <br>
                    RFC: {{ $emisor->rfc }}
                </div>
            </td>
            <td class="folio-cell">
                <div class="folio-box">
                    <table>
                        <tr>
                            <td class="lbl">FACTURA</td>
                        </tr>
                        <tr>
                            <td class="val folio">{{ $invoice->serie }} {{ $invoice->folio }}</td>
                        </tr>
                        <tr><td class="lbl">FOLIO FISCAL SAT</td></tr>
                        <tr><td class="val">{{ $invoice->uuid }}</td></tr>
                        <tr><td class="lbl">FECHA DE ELABORACIÓN</td></tr>
                        <tr><td class="val">{{ $invoice->fecha_emision->format('d/m/Y - H:i:s') }}</td></tr>
                        <tr><td class="lbl">FECHA DE CERTIFICACIÓN</td></tr>
                        <tr><td class="val">{{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('d/m/Y H:i:s') : '—' }}</td></tr>
                        <tr><td class="lbl">VENDEDOR</td></tr>
                        <tr><td class="val">{{ $vendedor }}</td></tr>
                        <tr><td class="lbl">MONEDA</td></tr>
                        <tr><td class="val">{{ ($requestData['moneda'] ?? 'MXN') === 'MXN' ? 'Pesos' : ($requestData['moneda'] ?? 'MXN') }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============== RECEPTOR ============== --}}
    <div class="bar">
        Facturado a: <span class="small-data">{{ strtoupper($invoice->receptor_nombre) }}</span>
    </div>
    <div class="receptor-data">
        @if($domicilioReceptor) {{ strtoupper($domicilioReceptor) }} <br> @endif
        RFC: {{ $invoice->receptor_rfc }}
        @if($invoice->receptor_cp) &nbsp;|&nbsp; C.P. {{ $invoice->receptor_cp }} @endif <br>
        Uso CFDI: {{ $invoice->receptor_uso_cfdi }} - {{ $catalogos['uso_cfdi'][$invoice->receptor_uso_cfdi] ?? '' }} <br>
        Régimen Fiscal Receptor: {{ $invoice->receptor_regimen }} - {{ $catalogos['regimen'][$invoice->receptor_regimen] ?? '' }}
        @if(isset($requestData['exportacion']))
            <br>Exportación: {{ $requestData['exportacion'] }} - {{ $requestData['exportacion'] === '01' ? 'No aplica' : '' }}
        @endif
    </div>

    {{-- ============== CHIPS PAGO ============== --}}
    <table class="chips-wrap">
        <tr>
            <td><span class="chip">NO CLIENTE: {{ $invoice->receipt?->client_id ?? '—' }}</span></td>
            <td><span class="chip">FORMA DE PAGO: {{ $invoice->forma_pago }} - {{ $catalogos['forma_pago'][$invoice->forma_pago] ?? '' }}</span></td>
            <td><span class="chip">MÉTODO: {{ $invoice->metodo_pago }} - {{ $catalogos['metodo_pago'][$invoice->metodo_pago] ?? '' }}</span></td>
            <td><span class="chip">TIPO: {{ $invoice->tipo_comprobante }} - {{ $catalogos['tipo_comprobante'][$invoice->tipo_comprobante] ?? '' }}</span></td>
        </tr>
    </table>

    <div class="cfdi-disclaimer">Este documento es una representación impresa de un CFDI</div>

    {{-- ============== TABLA CONCEPTOS ============== --}}
    <table class="conceptos">
        <thead>
            <tr>
                <th class="col-cant">Cantidad</th>
                <th class="col-parte"># Parte</th>
                <th class="col-unidad">Unidad de Medida</th>
                <th>Descripción</th>
                <th class="col-marca">Marca</th>
                <th class="col-precio">Precio Unitario</th>
                <th class="col-importe">Importe</th>
            </tr>
        </thead>
        <tbody>
        @foreach($conceptos as $c)
            @php
                $unidad = $mapUnidad[$c['clave_unidad'] ?? 'H87'] ?? ($c['clave_unidad'] ?? 'PZA');
                $parte = $c['no_identificacion'] ?? ($c['clave_prod_serv'] ?? '');
                // Marca: TODO P1 — products.brand pendiente. Vacío v1.
                $marca = $c['marca'] ?? '';
            @endphp
            <tr>
                <td class="c">{{ $c['cantidad'] }}</td>
                <td class="c">{{ $parte }}</td>
                <td class="c">{{ $unidad }}</td>
                <td>{{ $c['descripcion'] }}</td>
                <td class="c">{{ $marca }}</td>
                <td class="r">${{ number_format($c['valor_unitario'], 2) }}</td>
                <td class="r">${{ number_format($c['importe'] ?? $c['subtotal'] ?? 0, 2) }}</td>
            </tr>
        @endforeach
        {{-- Filas vacías de relleno (formato "pre-impreso") --}}
        @php
            $minFilas = 12;
            $filasVacias = max(0, $minFilas - count($conceptos));
        @endphp
        @for($i = 0; $i < $filasVacias; $i++)
            <tr class="empty">
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
        </tbody>
    </table>

    {{-- ============== TOTAL EN LETRA ============== --}}
    <p class="total-letra">
        Importe con Letra: <strong>{{ $importeLetra }}</strong>
    </p>

    {{-- ============== PIE: TÉRMINOS + TOTALES ============== --}}
    <table class="pie-table">
        <tr>
            <td class="terms-cell">
                <div class="terms-box">
                    <p class="terms-title">Términos:</p>
                    {{-- TODO P3-P4 y P7-P8: editables por tienda. Hardcoded v1. --}}
                    @if($esPPD)
                        <p class="terms-body">
                            DEBO (EMOS) Y PAGARÉ (MOS) EN FORMA INCONDICIONAL ESTE PAGARÉ EL DÍA
                            {{ \Carbon\Carbon::parse($invoice->fecha_emision)->addDays(30)->format('d/m/Y') }}
                            A LA ORDEN DE {{ strtoupper($emisor->razon_social) }} LA CANTIDAD DE
                            $ {{ number_format($invoice->total, 2) }}
                            ({{ $importeLetra }}) VALOR DE LAS MERCANCÍAS RECIBIDAS A MI (NUESTRA)
                            ENTERA SATISFACCIÓN EN CASO DE INCUMPLIMIENTO SE ACUMULARÁ UN INTERÉS
                            MORATORIO MENSUAL DEL 8%.
                        </p>
                    @else
                        <p class="terms-body">
                            Pago realizado en una sola exhibición. Esta factura ampara el cobro
                            de los conceptos descritos. Cualquier aclaración deberá realizarse
                            dentro de los 30 días naturales siguientes a su emisión.
                        </p>
                    @endif
                </div>
            </td>
            <td class="totales-cell">
                <div class="totales-wrap">
                    <table class="totales-tbl">
                        <tr>
                            <td class="lbl">SUBTOTAL:</td>
                            <td class="val">${{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->total_impuestos > 0)
                        <tr>
                            <td class="lbl">IVA {{ $tasaIvaPct }}%:</td>
                            <td class="val">${{ number_format($invoice->total_impuestos, 2) }}</td>
                        </tr>
                        @endif
                        @if(($invoice->total_retenciones ?? 0) > 0)
                        <tr>
                            <td class="lbl">RETENCIONES:</td>
                            <td class="val">-${{ number_format($invoice->total_retenciones, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-final">
                            <td class="lbl">TOTAL:</td>
                            <td class="val">${{ number_format($invoice->total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============== SELLOS + QR + CERT ============== --}}
    @if($timbreFiscal)
    <table class="sellos-wrap">
        <tr>
            <td class="qr-cell">
                @if(!empty($qrPath) && file_exists($qrPath))
                    <img src="{{ $qrPath }}" alt="QR">
                @elseif($qrBase64)
                    <img src="data:image/png;base64,{{ $qrBase64 }}" alt="QR">
                @endif
            </td>
            <td class="sellos-cell">
                <div class="sello-title">Sello digital del CFDI:</div>
                <div class="sello-text">{{ $timbreFiscal['sello'] ?? '' }}</div>
                <div class="sello-title">Sello digital del SAT:</div>
                <div class="sello-text">{{ $timbreFiscal['sello_sat'] ?? '' }}</div>
                <div class="sello-title">Cadena original del complemento de certificación digital del SAT:</div>
                <div class="sello-text">||1.1|{{ $timbreFiscal['uuid'] ?? '' }}|{{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('Y-m-d\TH:i:s') : '' }}|{{ $timbreFiscal['rfc_certifico'] ?? '' }}||{{ $timbreFiscal['sello'] ?? '' }}|{{ $timbreFiscal['num_certificado_sat'] ?? '' }}||</div>
            </td>
            <td class="cert-cell">
                <div class="cert-box">
                    <table>
                        <tr><td class="lbl">No. CERTIFICADO SAT</td></tr>
                        <tr><td class="val">{{ $timbreFiscal['num_certificado_sat'] ?? '—' }}</td></tr>
                        @if($noCertificadoEmisor)
                        <tr><td class="lbl">No. CERTIFICADO EMISOR (CSD)</td></tr>
                        <tr><td class="val">{{ $noCertificadoEmisor }}</td></tr>
                        @endif
                        <tr><td class="lbl">FECHA TIMBRADO</td></tr>
                        <tr><td class="val">{{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('d/m/Y H:i:s') : '—' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
    @endif

</body>
</html>
