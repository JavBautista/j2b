<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consigna {{ $consignment->folioCompleto() }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #222; line-height: 1.4; }
        .page { padding: 25px 30px; }

        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .header-table td { vertical-align: top; padding: 0; }
        .logo-cell { width: 110px; padding-right: 12px; }
        .logo-cell img { max-width: 100px; max-height: 80px; }
        .shop-name { font-size: 15px; font-weight: bold; color: #111; margin-bottom: 3px; }
        .shop-dato { font-size: 9px; color: #444; line-height: 1.4; }
        .doc-cell { width: 220px; text-align: right; vertical-align: top; }
        .doc-badge { background-color: #1e5fa8; color: #fff; font-size: 11px; font-weight: bold; padding: 7px 18px; text-align: center; display: inline-block; letter-spacing: 1px; }
        .folio { font-size: 18px; font-weight: bold; color: #1e5fa8; text-align: right; margin-top: 4px; }
        .doc-fecha { font-size: 9px; color: #444; text-align: right; margin-top: 2px; }

        .bar { background-color: #1e5fa8; color: #fff; font-size: 9px; font-weight: bold; padding: 4px 10px; margin: 10px 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px; }

        .info-table { width: 100%; border-collapse: collapse; font-size: 9px; margin-bottom: 4px; }
        .info-table td { padding: 2px 6px; vertical-align: top; }
        .info-table .lbl { color: #666; font-weight: bold; width: 130px; white-space: nowrap; }
        .info-table .val { color: #222; }

        .items { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .items th { background-color: #1e5fa8; color: #fff; font-size: 8px; padding: 6px 5px; text-align: left; text-transform: uppercase; letter-spacing: 0.3px; }
        .items th.c { text-align: center; }
        .items th.r { text-align: right; }
        .items td { font-size: 9px; padding: 5px 5px; border-bottom: 1px solid #ddd; color: #333; }
        .items td.c { text-align: center; }
        .items td.r { text-align: right; }
        .items tr:last-child td { border-bottom: 2px solid #1e5fa8; }

        .total-row { background-color: #f0f6ff; font-weight: bold; }

        .legal { font-size: 8.5px; color: #333; margin-top: 14px; padding: 10px; background-color: #f8f9fa; border-left: 3px solid #1e5fa8; line-height: 1.5; text-align: justify; }
        .legal strong { color: #1e5fa8; }

        .signatures { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; vertical-align: bottom; padding: 0 30px; font-size: 9px; }
        .sig-line { border-top: 1px solid #333; margin-bottom: 4px; height: 1px; }
        .sig-label { color: #666; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; font-size: 8px; }
        .sig-name { color: #111; font-weight: bold; margin-top: 2px; min-height: 12px; }

        .notes { font-size: 9px; color: #555; margin-top: 8px; padding: 6px 10px; background-color: #fafafa; border: 1px solid #eee; }
        .notes strong { color: #333; }

        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 7px; color: #888; text-align: center; }
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
            <td>
                <div class="shop-name">{{ $shop->name }}</div>
                @if($shop->address)
                    <div class="shop-dato">{{ $shop->address }}{{ $shop->number_out ? ' #'.$shop->number_out : '' }}{{ $shop->district ? ', '.$shop->district : '' }}</div>
                @endif
                @if($shop->city || $shop->state)
                    <div class="shop-dato">{{ $shop->city }}{{ $shop->state ? ', '.$shop->state : '' }}{{ $shop->zip_code ? ' CP '.$shop->zip_code : '' }}</div>
                @endif
                @if($shop->phone)
                    <div class="shop-dato">Tel: {{ $shop->phone }}{{ $shop->email ? ' · '.$shop->email : '' }}</div>
                @endif
            </td>
            <td class="doc-cell">
                <div class="doc-badge">VALE DE CONSIGNA</div>
                <div class="folio">{{ $consignment->folioCompleto() }}</div>
                <div class="doc-fecha">Fecha de entrega: {{ $consignment->delivery_date->format('d/m/Y') }}</div>
                @if($consignment->status === 'cancelada')
                    <div style="color:#c0392b; font-weight:bold; font-size:11px; margin-top:4px;">CANCELADA</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- DATOS RENTA / CLIENTE --}}
    <div class="bar">Datos del Cliente y Renta</div>
    <table class="info-table">
        <tr>
            <td class="lbl">Cliente:</td>
            <td class="val">{{ $client->name ?? 'N/D' }}</td>
        </tr>
        @if(!empty($client->phone))
        <tr>
            <td class="lbl">Tel&eacute;fono:</td>
            <td class="val">{{ $client->phone }}</td>
        </tr>
        @endif
        @if(!empty($client->address))
        <tr>
            <td class="lbl">Domicilio:</td>
            <td class="val">{{ $client->address }}</td>
        </tr>
        @endif
        <tr>
            <td class="lbl">Renta #:</td>
            <td class="val">{{ $rent->folio ?? $rent->id }}</td>
        </tr>
        @if($consignment->received_by_name)
        <tr>
            <td class="lbl">Recibe (nombre):</td>
            <td class="val">{{ $consignment->received_by_name }}</td>
        </tr>
        @endif
    </table>

    {{-- ITEMS --}}
    <div class="bar">Material Entregado en Consigna</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:55%;">Descripci&oacute;n</th>
                <th class="c" style="width:15%;">Cantidad</th>
                <th class="c" style="width:15%;">Devuelto</th>
                <th class="c" style="width:15%;">En consigna</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consignment->items as $item)
            <tr>
                <td>{{ $item->description ?? ($item->product->name ?? 'Producto') }}</td>
                <td class="c">{{ $item->qty }}</td>
                <td class="c">{{ $item->qty_returned }}</td>
                <td class="c"><strong>{{ $item->qty - $item->qty_returned }}</strong></td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>Total de unidades</strong></td>
                <td class="c"><strong>{{ $consignment->items->sum('qty') }}</strong></td>
                <td class="c"><strong>{{ $consignment->items->sum('qty_returned') }}</strong></td>
                <td class="c"><strong>{{ $consignment->items->sum('qty') - $consignment->items->sum('qty_returned') }}</strong></td>
            </tr>
        </tbody>
    </table>

    @if($consignment->notes)
    <div class="notes"><strong>Observaciones:</strong> {{ $consignment->notes }}</div>
    @endif

    {{-- TEXTO LEGAL --}}
    <div class="legal">
        <strong>El cliente recibe en CONSIGNA</strong> el material descrito en este documento. Este material no constituye una venta ni genera obligaci&oacute;n de pago. El cliente lo conserva en sus instalaciones para uso operativo de la renta vigente. En caso de cancelaci&oacute;n de la renta o por solicitud del proveedor, el material no consumido deber&aacute; ser reintegrado en las mismas condiciones en que fue entregado. La firma de este vale acredita la recepci&oacute;n del material listado.
    </div>

    {{-- FIRMAS --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">Entreg&oacute;</div>
                <div class="sig-name">{{ $createdByName }}</div>
            </td>
            <td>
                <div class="sig-line"></div>
                <div class="sig-label">Recibi&oacute; de Conformidad</div>
                <div class="sig-name">{{ $consignment->received_by_name ?? '' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · {{ $shop->name }}
    </div>

</div>
</body>
</html>
