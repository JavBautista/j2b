<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Document</title>
    <style>
        body{
            font-family: Verdana, Arial, sans-serif;
            font-size: 11px;
        }
        h1 { font-size: 16px; margin: 0 0 3px 0; }
        h2 { font-size: 13px; margin: 0 0 3px 0; }
        h3 { font-size: 11px; margin: 0 0 2px 0; }
        p { margin: 2px 0; line-height: 1.3; }
        pre{
            font-family: Verdana, Arial, sans-serif !important;
            font-size: 9px;
            margin: 0;
        }
    </style>
    </head>
    <body>
        <table width="100%">
            <tr>
                <td width="50%">
                    <h1>  {{ strtoupper($purchase_order->shop->name) }}</h1>
                    <p><strong>{{$purchase_order->shop->owner_name }}</strong> <br>
                    {{ $purchase_order->shop->email }} <br>
                    {{ $purchase_order->shop->phone }}</p>
                </td>
                <td width="50%" align="right">
                    @if (($receiptSettings->show_logo ?? true) && trim($purchase_order->shop->logo) != null)
                        <img src="{{ public_path('storage/'.$purchase_order->shop->logo) }}" style="max-height: 60px; max-width: 100%; width: auto;">
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <p>{{ $purchase_order->shop->address }} {{ $purchase_order->shop->number_out }} {{ $purchase_order->shop->number_int }} COL. {{ $purchase_order->shop->district }}<br>
                    {{ $purchase_order->shop->city }}, {{ $purchase_order->shop->state }}<br>
                    CP. {{ $purchase_order->shop->zip_code }}<br>
                    {{ $purchase_order->shop->bank_name }}<br>
                    {{ $purchase_order->shop->bank_number }}<br>
                    {{ $purchase_order->shop->bank_number_secondary }}</P>
                </td>
                <td align="right">
                    <H2>CLIENTE</H2>
                    <p>{{$purchase_order->supplier->name }}<br>
                    {{$purchase_order->supplier->company }}<br>
                    {{$purchase_order->supplier->movil }}<br>
                    {{$purchase_order->supplier->mail }}<br>
                    {{$purchase_order->supplier->address }}</p>
                </td>
            </tr>
        </table>
        <table width="100%">
            <tr>
                <td width="20%">
                    @if (($receiptSettings->show_qr ?? true) && !empty($qrImage))
                        <img src="{{ $qrImage }}" alt="QR" width="80">
                    @endif
                </td>
                <td width="30%">
                    <h2>Orden de compra #{{$purchase_order->folio}}</h2>
                    <h3>Vencimiento: {{$purchase_order->expiration}}</h3>
                    <h3>Creación: {{$purchase_order->created_at}}</h3>
                    <p>Status: {{$purchase_order->status}}</p>

                </td>
                <td style="padding: 10px;">
                    <p><pre>{{$purchase_order->observation}}</pre></p>
                </td>
            </tr>
        </table>
        <hr>
        <table width="100%">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Costo/Unidad</th>
                    <th>Qty</th>

                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase_order->detail as $data)
                <tr>

                    <td>{{$data->description}}</td>
                    <td>{{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($data->price,2) }}</td>
                    <td> {{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($data->price) }} x {{$data->qty}}</td>
                    <td> {{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($data->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <div align="right">

            <p>Subtotal {{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($purchase_order->subtotal ?? 0,2) }}</p>

            @if($purchase_order->discount_concept=='%')
                @php $descuento_monto = (($purchase_order->subtotal ?? 0) * ($purchase_order->discount ?? 0)) / 100; @endphp
                <p>Descuento {{ $purchase_order->discount ?? 0 }}% ({{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($descuento_monto, 2) }})</p>
            @else
                <p>Descuento {{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($purchase_order->discount ?? 0, 2) }}</p>
            @endif

            @if($purchase_order->iva)
                <p>{{ $purchase_order->shop->tax_name ?? 'IVA' }} {{ $purchase_order->shop->tax_rate ?? 16 }}% {{ $purchase_order->shop->getCurrencySymbol() }}{{number_format($purchase_order->iva,2)}}</p>
            @endif

            <p>Total a pagar <strong>{{ $purchase_order->shop->getCurrencySymbol() }}{{number_format($purchase_order->total,2)}}</strong></p>
        </div>

            <hr>
            <h2>Detalle de pagos</h2>
            <table width="50%">
                <thead>
                    <tr>
                        <th width="25%">Fecha</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                @php
                    $total_payments=0;
                @endphp
                @foreach($purchase_order->partialPayments as $data)
                    @php
                        $total_payments += $data->amount;
                    @endphp
                    <tr>
                        <td>
                            {{$data->payment_date}}
                        </td>
                        <td align="center">
                            {{ $purchase_order->shop->getCurrencySymbol() }}{{ number_format($data->amount,2)}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>PAGADO</th>
                        <th>{{ $purchase_order->shop->getCurrencySymbol() }}{{number_format($total_payments ,2)}}</th>
                    </tr>
                    @if($total_payments  < $purchase_order->total )
                    <tr>
                        <th>ADEUDO</th>
                        <th>{{ $purchase_order->shop->getCurrencySymbol() }}{{number_format(($purchase_order->total-$total_payments ),2)}}</th>
                    </tr>
                    @endif

                </tfoot>
            </table>

        <!-- Footer J2Biznes -->
        <div style="position: fixed; bottom: 10px; left: 0; right: 0; text-align: center;">
            <p style="font-size: 9px; color: #999; margin: 0;">
                De <img src="{{ public_path('images/heart-j2b.png') }}" style="width: 12px; height: 12px; vertical-align: middle;"> <a href="{{ $pdfPhraseUrl ?? 'https://j2biznes.com' }}" style="color: #555; text-decoration: none; font-weight: bold;">J2Biznes.com</a> - {{ $pdfPhrase ?? 'Tu negocio, simplificado.' }}
            </p>
        </div>

    </body>
</html>