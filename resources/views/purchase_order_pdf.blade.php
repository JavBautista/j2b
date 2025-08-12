<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Document</title>
    <style>
        body{
            /*font-family: "Lucida Console", Monaco, monospace;*/
            font-family: Verdana, Arial, sans-serif;
            font-size: 12px;
        }

        pre{
            font-family: Verdana, Arial, sans-serif !important;
            font-size: 9px;
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
                    <img src="{{ public_path('storage/'.$purchase_order->shop->logo)  }}"  width="50%">
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
                    <img src="{{public_path('img/j2b_qr.png')}}" alt="QR" width="50%">
                </td>
                <td width="30%">
                    <h2>Orden de compra #{{$purchase_order->id}}</h2>
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
                    <td>MXN $ {{ number_format($data->price,2) }}</td>
                    <td> MXN ${{ number_format($data->price) }} x {{$data->qty}}</td>
                    <td> MXN ${{$data->subtotal}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <div align="right">
            <p>Total a pagar <strong>MXN $ {{number_format($purchase_order->total,2)}}</strong></p>
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
                            MXN $ {{ number_format($data->amount,2)}}
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>PAGADO</th>
                        <th>MXN $ {{number_format($total_payments ,2)}}</th>
                    </tr>
                    @if($total_payments  < $purchase_order->total )
                    <tr>
                        <th>ADEUDO</th>
                        <th>MXN $ {{number_format(($purchase_order->total-$total_payments ),2)}}</th>
                    </tr>
                    @endif

                </tfoot>
            </table>

    </body>
</html>