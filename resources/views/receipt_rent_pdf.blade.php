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
                    <h1>COPIGAMA</h1>
                    <p><strong>ING. JUAN CARLOS HERRERA</strong> <br>
                    copigama-aca@hotmail.com <br>
                    442 559 2717</p>
                </td>
                <td width="50%" align="right">
                    <img src="{{ asset('img/logo.jpg') }}" alt="LOGO COPIGAMA" width="70%">
                </td>
            </tr>
            <tr>
                <td>
                    <p>C. SECRETARIA DE GOBERNACIÓN 117 COL. SAN PABLO II<br>
                    SANTIAGO DE QUERETARO, QRO<br>
                    CP. 76125<br>
                    BANCOMER<br>
                    2838071535<br>
                    4152 3134 4248 6580</P>
                </td>
                <td align="right">
                    <H2>CLIENTE</H2>
                    <p>{{$receipt->client->name }}<br>
                    {{$receipt->client->company }}<br>
                    {{$receipt->client->movil }}<br>
                    {{$receipt->client->mail }}<br>
                    {{$receipt->client->address }}</p>
                </td>
            </tr>
        </table>
        <table width="100%">
            <tr>
                <td width="20%">
                    <img src="{{asset('img/copigama_qr.png')}}" alt="QR" width="50%">
                </td>
                <td width="30%">
                    <h2>COTIZACIÓN #{{$receipt->id}}</h2>
                    <h3>Vencimiento: {{$receipt->quotation_expiration}}</h3>
                    <h3>Creación: {{$receipt->created_at}}</h3>
                    @if(!$receipt->quotation)
                    <p>Status: {{$receipt->status}}<br>Forma de pago: {{$receipt->payment}}</p>
                    @endif
                </td>
                <td style="padding: 10px;">
                    <p>{{$receipt->description }}</p>
                    <p><pre>{{$receipt->observation}}</pre></p>
                </td>
            </tr>
        </table>
        <hr>
        <table width="100%">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Qty</th>
                    <th>Costo/Unidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->detail as $data)
                <tr>
                    <td>{{$data->descripcion}}</td>
                    <td>{{$data->qty}}</td>
                    <td>MXN $ {{ number_format($data->price,2) }}</td>
                    <td>MXN $ {{ number_format($data->subtotal,2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <div align="right">

            <p>Subtotal MXN $ {{ number_format($receipt->subtotal,2) }}</p>
            <p>Descuento MXN $ {{ number_format($receipt->discount,2) }}</p>
            @if($receipt->iva)
                <p>IVA 16% MXN $ {{number_format($receipt->iva,2)}}</p>
            @endif
            <p>Total a pagar <strong>MXN $ {{number_format($receipt->total,2)}}</strong></p>
        </div>
        @if(!$receipt->quotation)
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
                @foreach($receipt->partialPayments as $data)
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
                        <th>RECIBIDO</th>
                        <th>MXN $ {{number_format($receipt->received,2)}}</th>
                    </tr>
                    @if($receipt->received < $receipt->total )
                    <tr>
                        <th>ADEUDO</th>
                        <th>MXN $ {{number_format(($receipt->total-$receipt->received),2)}}</th>
                    </tr>
                    @endif

                </tfoot>
            </table>
        @endif

    </body>
</html>