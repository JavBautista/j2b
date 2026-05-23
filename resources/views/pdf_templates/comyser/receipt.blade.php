{{--
    Plantilla PDF "Comyser" para Cotización / Remisión.
    Selector: shops.pdf_template = 'comyser' → Shop::pdfView('receipt').
    Cambios futuros documentados en xdev/formatos_pdf/PLAN_FORMATOS_PDF.md
--}}
@php
    $esCotizacion = (bool) $receipt->quotation;
    $titulo       = $esCotizacion ? 'COTIZACIÓN' : 'REMISIÓN';
    $shop         = $receipt->shop;
    $client       = $receipt->client;

    // Vendedor (relación nueva Receipt::user — fallback si no hay user_id)
    $vendedor = optional($receipt->user)->name ?? 'VENTAS';

    // Total en letra (helper compartido)
    $totalLetra = \App\Support\NumeroALetras::convertir((float) $receipt->total);

    // Cuenta bancaria default (sólo cotización, sólo si hay)
    $bancoDefault = isset($bankAccounts) ? $bankAccounts->firstWhere('is_default', true) ?? $bankAccounts->first() : null;

    // Mapeo rápido de claves SAT comunes a abreviatura humana (v1 hardcoded — ver P6 en PLAN).
    $mapUnidad = ['H87'=>'PZA','EA'=>'PZA','XPK'=>'PAQ','KGM'=>'KG','GRM'=>'GR','LTR'=>'LT','MLT'=>'ML',
                  'MTR'=>'MT','CMT'=>'CM','HUR'=>'HR','DAY'=>'DIA','MON'=>'MES','E48'=>'SVC','ACT'=>'ACT'];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} {{ $receipt->folio }}</title>
    <style>
        @page { margin: 12mm 10mm 12mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10.5px; color: #1a1a1a; margin: 0; }

        /* ===== HEADER ===== */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .header td { vertical-align: top; padding: 0; }
        .logo-cell { width: 130px; padding-right: 8px; }
        .logo-cell img { max-width: 120px; max-height: 105px; }
        .titulo-cell { text-align: center; padding: 0 6px; }
        .titulo-cell h1 { font-size: 28px; font-weight: bold; margin: 0 0 3px 0; letter-spacing: 1.5px; color: #111; }
        .titulo-cell .shop-name { font-size: 14px; font-weight: bold; color: #333 !important; margin: 0 0 3px 0; }
        .titulo-cell .shop-data { font-size: 10px; color: #333 !important; line-height: 1.4; }
        .titulo-cell .shop-data * { color: #333 !important; }
        .folio-cell { width: 195px; }

        /* Caja folio: borde azul, fondo blanco, labels azules / valores negros (estilo Comyser) */
        .folio-box {
            border: 2px solid #16386b;
            border-radius: 5px;
            background: #fff;
            padding: 4px 2px;
        }
        .folio-box table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .folio-box td { padding: 2px 8px; vertical-align: middle; }
        .folio-box .lbl {
            color: #16386b !important;
            font-weight: bold;
            background: transparent !important;
            width: 50%;
            text-align: left;
        }
        .folio-box .val { color: #1a1a1a !important; text-align: right; font-weight: normal; }
        .folio-box .folio-row .lbl { font-size: 13px; padding: 4px 8px; }
        .folio-box .folio-row .val { font-size: 17px; font-weight: bold; padding: 4px 8px; color: #1a1a1a !important; }

        /* ===== BARRA CLIENTE ===== */
        .cliente-bar {
            background: #16386b;
            color: #fff !important;
            font-size: 12px;
            font-weight: bold;
            padding: 10px 16px;
            margin: 10px 0 6px 0;
            border-radius: 5px;
        }
        .cliente-bar .cli-data { font-weight: normal; font-size: 11px; margin-left: 6px; }

        /* ===== FRASE INTRO ===== */
        .intro { font-size: 10px; color: #444 !important; margin: 6px 2px 8px 2px; }

        /* ===== TABLA CONCEPTOS ===== */
        .conceptos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .conceptos th {
            font-size: 9.5px; font-weight: normal; color: #333 !important; text-align: center;
            padding: 6px 4px; border: 1.2px solid #555; background: #fff;
        }
        .conceptos td {
            font-size: 9.5px; color: #1a1a1a !important; padding: 5px 5px;
            border-left: 1.2px solid #555; border-right: 1.2px solid #555;
            vertical-align: top;
            line-height: 1.3;
        }
        .conceptos td.r { text-align: right; }
        .conceptos td.c { text-align: center; }
        .conceptos tbody tr:first-child td { border-top: 1.2px solid #555; }
        .conceptos tbody tr:last-child td { border-bottom: 1.2px solid #555; }
        .conceptos .col-cant   { width: 52px; }
        .conceptos .col-parte  { width: 78px; }
        .conceptos .col-unidad { width: 68px; }
        .conceptos .col-marca  { width: 78px; }
        .conceptos .col-precio { width: 72px; }
        .conceptos .col-importe{ width: 78px; }
        /* Filas placeholder vacías para que la tabla luzca "pre-impresa" hasta el pie */
        .conceptos tr.empty td { height: 22px; }

        /* ===== TOTAL EN LETRA ===== */
        .total-letra { font-size: 10px; color: #1a1a1a !important; margin: 8px 0 5px 0; }

        /* ===== PIE: TÉRMINOS + TOTALES ===== */
        .pie-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .pie-table td { vertical-align: top; padding: 0; }
        .pie-table .terms-cell { width: 60%; padding-right: 8px; }
        .pie-table .totales-cell { width: 40%; }
        .terms-box {
            border: 1.2px solid #555;
            border-radius: 5px;
            padding: 8px 10px;
            min-height: 80px;
        }
        .terms-box .terms-title { font-weight: bold; font-size: 10px; font-style: italic; color: #222 !important; margin: 0 0 4px 0; }
        .terms-box .terms-line { font-size: 9.5px; color: #444 !important; line-height: 1.4; margin: 0 0 2px 0; }

        /* Wrapper redondea la tabla de totales (border-radius no aplica a <table>) */
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

        /* ===== CUENTA BANCARIA (chips) ===== */
        .banco-wrap { margin-top: 12px; border: 1.2px solid #16386b; border-radius: 5px; padding: 8px 12px; }
        .banco-wrap .banco-title { font-size: 10px; font-style: italic; text-align: center; color: #333 !important; margin-bottom: 6px; }
        .banco-chips { width: 100%; border-collapse: collapse; }
        .banco-chips td { padding: 0 4px; text-align: center; }
        .chip {
            display: inline-block;
            border: 1px solid #16386b;
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 9px;
            color: #1a1a1a !important;
            background: #fff;
        }

        /* ===== FOOTER ===== */
        .footer-firma { text-align: center; margin-top: 22px; font-size: 11px; color: #1a1a1a !important; }
        .footer-firma .linea { border-top: 1px solid #555; width: 240px; margin: 0 auto 3px auto; }
        .footer-firma .dept { font-weight: bold; letter-spacing: 0.8px; }
        .footer-firma .at { font-style: italic; margin-top: 2px; font-size: 10px; color: #555 !important; }
    </style>
</head>
<body>

    {{-- ============== HEADER ============== --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if (($receiptSettings->show_logo ?? true) && trim($shop->logo ?? '') !== '')
                    <img src="{{ public_path('storage/'.$shop->logo) }}" alt="Logo">
                @endif
            </td>
            <td class="titulo-cell">
                <h1>{{ $titulo }}</h1>
                <div class="shop-name">{{ strtoupper($shop->name) }}</div>
                <div class="shop-data">
                    @if($shop->owner_name) {{ $shop->owner_name }}<br> @endif
                    {{ trim(($shop->address ?? '').' '.($shop->number_out ?? '').' '.($shop->number_int ?? '')) }}
                    @if($shop->district) Col. {{ $shop->district }} @endif <br>
                    {{ $shop->city ?? '' }}@if($shop->state), {{ $shop->state }}.@endif <br>
                    @if(!empty($shop->cfdiEmisor?->regimen_fiscal))
                        Régimen Fiscal: {{ $shop->cfdiEmisor->regimen_fiscal }} <br>
                    @endif
                    @if(!empty($shop->cfdiEmisor?->rfc))
                        RFC: {{ $shop->cfdiEmisor->rfc }}
                    @endif
                </div>
            </td>
            <td class="folio-cell">
                <div class="folio-box">
                    <table>
                        <tr class="folio-row">
                            <td class="lbl">FOLIO:</td>
                            <td class="val">{{ $receipt->folio }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">FECHA:</td>
                            <td class="val">{{ \Carbon\Carbon::parse($receipt->created_at)->format('d/m/y') }}</td>
                        </tr>
                        @if(!$esCotizacion)
                        <tr>
                            <td class="lbl">HORA:</td>
                            <td class="val">{{ \Carbon\Carbon::parse($receipt->created_at)->format('H:i:s') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="lbl">VENDEDOR:</td>
                            <td class="val">{{ strtoupper($vendedor) }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">MONEDA:</td>
                            <td class="val">{{ ($shop->currency ?? 'MXN') === 'MXN' ? 'PESOS' : ($shop->currency ?? 'MXN') }}</td>
                        </tr>
                        @if(!$esCotizacion)
                        <tr>
                            <td class="lbl">ESTADO:</td>
                            <td class="val">{{ $receipt->status }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============== CLIENTE ============== --}}
    <div class="cliente-bar">
        Cliente: <span class="cli-data">{{ strtoupper($client->company ?: $client->name ?: 'VENTA AL PÚBLICO') }}</span>
    </div>

    @if($esCotizacion)
        <p class="intro">Por medio de la presente, me permito cotizar lo siguiente</p>
    @endif

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
        @foreach($receipt->detail as $d)
            @php
                $producto = $d->product;
                // # Parte: SKU del producto si existe
                $parte = $producto->key ?? '';
                // Unidad de medida: lookup en mapa rápido SAT → abreviatura humana
                $satUnit = $producto->sat_unit_code ?? 'H87';
                $unidad  = $mapUnidad[$satUnit] ?? $satUnit;
                // Marca: TODO P1 — products.brand pendiente. Mostrar vacío por ahora.
                $marca = $producto->brand ?? '';
                // Precio unitario después de descuento
                $precioUnit = (float)$d->price - (float)($d->discount ?? 0);
            @endphp
            <tr>
                <td class="c">{{ $d->qty }}</td>
                <td class="c">{{ $parte }}</td>
                <td class="c">{{ $unidad }}</td>
                <td>{!! nl2br(e($d->descripcion)) !!}</td>
                <td class="c">{{ $marca }}</td>
                <td class="r">${{ number_format($precioUnit, 2) }}</td>
                <td class="r">${{ number_format($d->subtotal, 2) }}</td>
            </tr>
        @endforeach
        {{-- Filas vacías de relleno para que la tabla luzca "pre-impresa" hasta llegar al pie --}}
        @php
            $minFilas = 15;
            $filasVacias = max(0, $minFilas - $receipt->detail->count());
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
        Total en Letra: <strong>{{ $totalLetra }} M.N.</strong>
    </p>

    {{-- ============== PIE: TÉRMINOS + TOTALES ============== --}}
    <table class="pie-table">
        <tr>
            <td class="terms-cell">
                <div class="terms-box">
                    <p class="terms-title">
                        Términos Generales de {{ $esCotizacion ? 'Cotización' : 'Remisión' }}:
                    </p>
                    {{-- TODO P3-P4: editables por tienda (cotizacion_terms, remision_terms). Hardcoded v1. --}}
                    @if($esCotizacion)
                        @php
                            $horasVigencia = null;
                            if ($receipt->quotation_expiration) {
                                $horasVigencia = \Carbon\Carbon::parse($receipt->created_at)
                                    ->diffInHours(\Carbon\Carbon::parse($receipt->quotation_expiration));
                            }
                        @endphp
                        <p class="terms-line">
                            La presente cotización es válida por un periodo de
                            {{ $horasVigencia ?: 72 }} horas a partir de su fecha de emisión.
                        </p>
                        <p class="terms-line">
                            Los precios de los productos y servicios pueden modificarse sin previo aviso.
                        </p>
                        <p class="terms-line">
                            Antes de realizar cualquier transferencia, es necesario confirmar con el área de
                            ventas la disponibilidad del producto o servicio.
                        </p>
                    @else
                        <p class="terms-line">
                            Es responsabilidad del cliente verificar el estado y cantidad del producto al momento de la entrega.
                        </p>
                        <p class="terms-line">
                            Esta remisión ampara la entrega de productos descritos, quedando sujeta a confirmación de pago.
                        </p>
                    @endif
                </div>
            </td>
            <td class="totales-cell">
                <div class="totales-wrap">
                    <table class="totales-tbl">
                        <tr>
                            <td class="lbl">SUBTOTAL:</td>
                            <td class="val">${{ number_format($receipt->subtotal, 2) }}</td>
                        </tr>
                        @if($receipt->iva)
                        <tr>
                            <td class="lbl">{{ $shop->tax_name ?? 'IVA' }}:</td>
                            <td class="val">${{ number_format($receipt->iva, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-final">
                            <td class="lbl">TOTAL:</td>
                            <td class="val">${{ number_format($receipt->total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============== CUENTA BANCARIA (sólo cotización) ============== --}}
    @if($esCotizacion && $bancoDefault)
        <div class="banco-wrap">
            <p class="banco-title">Cuenta Bancaria:</p>
            <table class="banco-chips">
                <tr>
                    @if(!empty($shop->cfdiEmisor?->rfc))
                    <td><span class="chip">RFC: {{ $shop->cfdiEmisor->rfc }}</span></td>
                    @endif
                    <td><span class="chip">Banco: {{ $bancoDefault->bank_name }}</span></td>
                    @if($bancoDefault->account_number)
                    <td><span class="chip">Cuenta: {{ $bancoDefault->account_number }}</span></td>
                    @endif
                    <td><span class="chip">CLABE: {{ $bancoDefault->clabe }}</span></td>
                </tr>
            </table>
        </div>
    @endif

    {{-- ============== FOOTER ============== --}}
    <div class="footer-firma">
        <div class="linea"></div>
        <div class="dept">DEPARTAMENTO DE VENTAS</div>
        <div class="at">Atentamente</div>
    </div>

</body>
</html>
