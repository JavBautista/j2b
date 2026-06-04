{{--
    Plantilla PDF "Ticket 80mm" para Nota de venta / Remisión / Cotización.
    Pensada para impresora térmica de tickets (ancho de papel 80mm).
    El tamaño de papel (ancho 80mm ≈ 226.77pt + alto dinámico) lo fija el controlador con setPaper().
    Plan: xdev/facturacion/PLAN_PDF_RECIBO_FISCAL.md (sección Ticket 80mm).

    OBJETIVO: replicar el MISMO contenido que la plantilla carta (receipt_rent_pdf),
    solo adaptado al ancho de 80mm. Variables esperadas (idénticas a las otras plantillas):
      $receipt, $receiptSettings, $qrImage, $pdfPhrase, $pdfPhraseUrl, $bankAccounts
--}}
@php
    $shop    = $receipt->shop;
    $simbolo = $shop->getCurrencySymbol();
    $esCotizacion = (bool) $receipt->quotation;
    $rfcEmisor = $shop->cfdiEmisor->rfc ?? null;

    $cfdi = $receipt->cfdiInvoice ?? null;               // CFDI vigente (ya filtra status)
    $tieneFiscal = $cfdi && $cfdi->tieneDesgloseFiscal(); // retenciones o impuestos locales

    $nombreImp = ['001' => 'ISR', '002' => 'IVA', '003' => 'IEPS'];
    $fmtPct = function ($pct) {
        if ($pct === null) return '';
        return rtrim(rtrim(number_format((float) $pct, 4, '.', ''), '0'), '.');
    };

    if ($tieneFiscal) {
        $retFedGlobales = $cfdi->retenciones->whereNull('concepto_index');
        $retLocales     = $cfdi->retencionesLocales;
        $trasLocales    = $cfdi->trasladosLocales;
        $tasasFed = [];
        foreach ($cfdi->retenciones->whereNotNull('concepto_index') as $rc) {
            $tasasFed[$rc->impuesto] = (float) $rc->tasa;
        }
        $ivaTras = $cfdi->traslados->whereNull('concepto_index')->first();
        $ivaTasaPctF = $ivaTras ? ((float) $ivaTras->tasa) * 100 : null;
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $receipt->folio }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
            padding: 6px 8px;
            line-height: 1.35;
        }
        .c { text-align: center; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .sm { font-size: 8px; }
        .xs { font-size: 7px; }
        .muted { color: #333; }
        .shop-name { font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }
        .logo { max-width: 130px; max-height: 46px; margin-bottom: 3px; }
        hr { border: 0; border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .row td.lbl { text-align: left; }
        .row td.val { text-align: right; white-space: nowrap; padding-left: 6px; }
        .concepto { margin-bottom: 4px; }
        .concepto .desc { word-wrap: break-word; }
        .tot td { padding: 1px 0; }
        .tot .grand td { font-size: 11px; font-weight: bold; padding-top: 2px; }
        .ret { color: #444; }
        .fiscal-title { font-weight: bold; }
        .uuid { font-family: 'DejaVu Sans Mono', monospace; word-break: break-all; }
        .sec-title { font-weight: bold; font-size: 8px; text-transform: uppercase; letter-spacing: 0.3px; }
        .obs { white-space: pre-wrap; word-wrap: break-word; }
        .foot { margin-top: 6px; }
    </style>
</head>
<body>

    {{-- ===== Encabezado tienda ===== --}}
    <div class="c">
        @if(($receiptSettings->show_logo ?? true) && trim($shop->logo ?? '') !== '')
            <img class="logo" src="{{ public_path('storage/'.$shop->logo) }}" alt="logo"><br>
        @endif
        <span class="shop-name">{{ strtoupper($shop->name) }}</span>
        <div class="sm">
            @if($shop->owner_name){{ $shop->owner_name }}<br>@endif
            {{ trim(($shop->address ?? '').' '.($shop->number_out ?? '').' '.($shop->number_int ?? '')) }}
            @if($shop->district)<br>Col. {{ $shop->district }}@endif
            <br>{{ $shop->city ?? '' }}@if($shop->state), {{ $shop->state }}@endif
            @if($shop->zip_code) · CP {{ $shop->zip_code }}@endif
            @if($shop->phone)<br>Tel: {{ $shop->phone }}@endif
            @if($shop->email)<br>{{ $shop->email }}@endif
            @if($rfcEmisor)<br>RFC: {{ $rfcEmisor }}@endif
        </div>
    </div>

    <hr>

    {{-- ===== Cliente ===== --}}
    @if($receipt->client)
        <div class="sm">
            <span class="sec-title">Cliente</span><br>
            {{ $receipt->client->name }}
            @if($receipt->client->company)<br>{{ $receipt->client->company }}@endif
            @if($receipt->client->movil)<br>Tel: {{ $receipt->client->movil }}@endif
            @if($receipt->client->mail)<br>{{ $receipt->client->mail }}@endif
            @if($receipt->client->address)<br>{{ $receipt->client->address }}@endif
        </div>
        <hr>
    @endif

    {{-- ===== Datos de la nota ===== --}}
    <div class="sm">
        <span class="b">{{ $esCotizacion ? 'COTIZACIÓN' : 'NOTA DE VENTA' }} #{{ $receipt->folio }}</span><br>
        Fecha: {{ \Carbon\Carbon::parse($receipt->created_at)->format('d/m/Y H:i') }}
        @if($esCotizacion && $receipt->quotation_expiration)<br>Vence: {{ $receipt->quotation_expiration }}@endif
        @if(!$esCotizacion)<br>Estado: {{ $receipt->status }} · Pago: {{ $receipt->payment }}@endif
        @if($receipt->type == 'renta' && $receipt->rent_periodo)<br>Periodo: {{ $receipt->rent_periodo }}@endif
        @if($receipt->description)<br>{{ $receipt->description }}@endif
    </div>
    @if($receipt->observation)
        <div class="sm obs muted" style="margin-top:3px;">{{ $receipt->observation }}</div>
    @endif

    {{-- ===== Campos extra ===== --}}
    @if($receipt->infoExtra->isNotEmpty())
        <hr>
        <table class="sm">
            @foreach($receipt->infoExtra as $extra)
                <tr><td class="lbl b" style="width:45%;">{{ $extra->field_name }}</td>
                    <td class="lbl">{{ $extra->value }}</td></tr>
            @endforeach
        </table>
    @endif

    <hr>

    {{-- ===== Conceptos ===== --}}
    @foreach($receipt->detail as $d)
        @php $precioUnit = (float) $d->price - (float) ($d->discount ?? 0); @endphp
        <div class="concepto">
            <div class="desc">{!! nl2br(e($d->descripcion)) !!}</div>
            <table class="row">
                <tr>
                    <td class="lbl sm muted">{{ $d->qty }} x {{ $simbolo }}{{ number_format($precioUnit, 2) }}</td>
                    <td class="val">{{ $simbolo }}{{ number_format($d->subtotal, 2) }}</td>
                </tr>
            </table>
        </div>
    @endforeach

    <hr>

    {{-- ===== Totales comerciales ===== --}}
    <table class="tot">
        <tr class="row"><td class="lbl">Subtotal</td><td class="val">{{ $simbolo }}{{ number_format($receipt->subtotal, 2) }}</td></tr>
        @if($receipt->discount > 0)
            @php
                $descMonto = $receipt->discount_concept === '%'
                    ? ($receipt->subtotal * $receipt->discount) / 100
                    : $receipt->discount;
                $descLabel = 'Descuento';
                if ($receipt->discount_concept === '%') {
                    $descLabel .= ' ' . rtrim(rtrim(number_format($receipt->discount, 2), '0'), '.') . '%';
                }
            @endphp
            <tr class="row"><td class="lbl">{{ $descLabel }}</td>
                <td class="val">-{{ $simbolo }}{{ number_format($descMonto, 2) }}</td></tr>
        @endif
        @if((float) $receipt->iva > 0)
            <tr class="row"><td class="lbl">{{ $shop->tax_name ?? 'IVA' }} {{ $shop->tax_rate ?? 16 }}%</td>
                <td class="val">{{ $simbolo }}{{ number_format($receipt->iva, 2) }}</td></tr>
        @endif
        <tr class="row grand"><td class="lbl">TOTAL</td><td class="val">{{ $simbolo }}{{ number_format($receipt->total, 2) }}</td></tr>
    </table>

    {{-- ===== Desglose fiscal (solo si facturada con retenciones / impuestos locales) ===== --}}
    @if($tieneFiscal)
        <hr>
        <div class="c sm fiscal-title">✔ NOTA FACTURADA · CFDI {{ trim(($cfdi->serie ?? '').'-'.($cfdi->folio ?? ''), '-') }}</div>
        <div class="c xs uuid muted">{{ $cfdi->uuid }}</div>
        <table class="tot sm" style="margin-top:3px;">
            <tr class="row"><td class="lbl">Subtotal</td><td class="val">{{ $simbolo }}{{ number_format($cfdi->subtotal, 2) }}</td></tr>
            @if((float) $cfdi->total_impuestos > 0)
                <tr class="row"><td class="lbl">{{ $shop->tax_name ?? 'IVA' }}@if($ivaTasaPctF !== null) {{ $fmtPct($ivaTasaPctF) }}%@endif</td>
                    <td class="val">{{ $simbolo }}{{ number_format($cfdi->total_impuestos, 2) }}</td></tr>
            @endif
            @foreach($retFedGlobales as $rr)
                <tr class="row ret"><td class="lbl">Ret. {{ $nombreImp[$rr->impuesto] ?? $rr->impuesto }}@if(isset($tasasFed[$rr->impuesto])) {{ $fmtPct($tasasFed[$rr->impuesto] * 100) }}%@endif</td>
                    <td class="val">-{{ $simbolo }}{{ number_format($rr->importe, 2) }}</td></tr>
            @endforeach
            @foreach($retLocales as $il)
                <tr class="row ret"><td class="lbl">Ret. Local {{ $il->nombre }} {{ $fmtPct($il->tasa_porcentaje) }}%</td>
                    <td class="val">-{{ $simbolo }}{{ number_format($il->importe, 2) }}</td></tr>
            @endforeach
            @foreach($trasLocales as $il)
                <tr class="row"><td class="lbl">Tras. Local {{ $il->nombre }} {{ $fmtPct($il->tasa_porcentaje) }}%</td>
                    <td class="val">+{{ $simbolo }}{{ number_format($il->importe, 2) }}</td></tr>
            @endforeach
            <tr class="row grand"><td class="lbl">Total facturado</td><td class="val">{{ $simbolo }}{{ number_format($cfdi->total, 2) }}</td></tr>
        </table>
    @endif

    {{-- ===== Detalle de pagos (oculto si hay desglose fiscal, igual que en carta) ===== --}}
    @if(!$esCotizacion && !$tieneFiscal)
        <hr>
        <div class="sec-title">Detalle de pagos</div>
        <table class="tot sm">
            @foreach($receipt->partialPayments as $pago)
                <tr class="row"><td class="lbl muted">{{ $pago->payment_date }}</td>
                    <td class="val">{{ $simbolo }}{{ number_format($pago->amount, 2) }}</td></tr>
            @endforeach
            <tr class="row"><td class="lbl b">RECIBIDO</td><td class="val b">{{ $simbolo }}{{ number_format($receipt->received, 2) }}</td></tr>
            @if($receipt->received < $receipt->total)
                <tr class="row"><td class="lbl b">ADEUDO</td><td class="val b">{{ $simbolo }}{{ number_format($receipt->total - $receipt->received, 2) }}</td></tr>
            @endif
        </table>
    @endif

    {{-- ===== Datos bancarios para depósito (si la nota tiene saldo pendiente) ===== --}}
    @if(isset($bankAccounts) && $bankAccounts->count() > 0)
        <hr>
        <div class="sec-title">Datos para depósito</div>
        @foreach($bankAccounts as $cuenta)
            <div class="sm" style="margin-top:2px;">
                {{ $cuenta->bank_name }}@if($cuenta->is_default) (Principal)@endif<br>
                <span class="muted">{{ $cuenta->holder_name }}</span><br>
                CLABE: {{ $cuenta->clabe }}
                @if($cuenta->account_number)<br>Cuenta: {{ $cuenta->account_number }}@endif
            </div>
        @endforeach
    @endif

    {{-- ===== QR opcional ===== --}}
    @if(($receiptSettings->show_qr ?? true) && !empty($qrImage))
        <div class="c" style="margin-top:6px;"><img src="{{ $qrImage }}" alt="QR" width="90"></div>
    @endif

    {{-- ===== Pie ===== --}}
    <hr>
    <div class="c xs muted foot">
        @if(!$esCotizacion)¡Gracias por su compra!<br>@endif
        {{ $pdfPhrase ?? 'Tu negocio, simplificado.' }}<br>
        J2Biznes.com
    </div>

</body>
</html>
