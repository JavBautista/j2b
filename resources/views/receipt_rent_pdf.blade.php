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
                    <h1>  {{ strtoupper($receipt->shop->name) }}</h1>
                    <p><strong>{{$receipt->shop->owner_name }}</strong> <br>
                    {{ $receipt->shop->email }} <br>
                    {{ $receipt->shop->phone }}</p>
                </td>
                <td width="50%" align="right">
                    @if (trim($receipt->shop->logo)!=null)
                        <img src="{{ asset('/storage/'.$receipt->shop->logo) }}" width="50%">
                    @else
                        <p style="font-weight: bold;">Logo</p>
                    @endif
                </td>
            </tr>
            <tr>
                <td>
                    <p>{{ $receipt->shop->address }} {{ $receipt->shop->number_out }} {{ $receipt->shop->number_int }} COL. {{ $receipt->shop->district }}<br>
                    {{ $receipt->shop->city }}, {{ $receipt->shop->state }}<br>
                    CP. {{ $receipt->shop->zip_code }}<br>
                    {{ $receipt->shop->bank_name }}<br>
                    {{ $receipt->shop->bank_number }}<br>
                    {{ $receipt->shop->bank_number_secondary }}</P>
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
                    <img src="{{asset('img/j2b_qr.png')}}" alt="QR" width="50%">
                </td>
                <td width="40%">
                    <h2>{{$receipt->quotation?'COTIZACIÓN':'FOLIO'}} #{{$receipt->folio}}</h2>
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
            @if ($receipt->type=='renta')
                <tr>
                    <td colspan="3">
                            <h3>Periodo: {{$receipt->rent_periodo}}</h3>
                    </td>
                </tr>
            @endif
        </table>

        <!--NUEVOS CAMPOS EXTRA-->
        @if ($receipt->infoExtra->isNotEmpty())
            <hr>
            <table width="100%">
                <tbody>
                    @php $extras = $receipt->infoExtra->chunk(2); @endphp
                    @foreach ($extras as $pair)
                        <tr>
                            @foreach ($pair as $extra)
                                <td style="width: 30%; padding-right: 10px;"> <strong> {{ $extra->field_name }} </strong></td>
                                <td style="width: 70%;">{{ $extra->value }}</td>
                            @endforeach
                            @if ($loop->count < 2) {{-- Agregar una celda vacía si solo hay un campo en la última fila --}}
                                <td></td>
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!--./NUEVOS CAMPOS EXTRA-->
        <hr>
        <table style="border-collapse: collapse; width: 100%;" width="100%">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Costo/Unidad</th>
                    <th>Qty</th>

                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->detail as $data)
                <tr style="border-top: 1px solid #ccc !important;">

                    <td style="max-width: 50%; word-wrap: break-word;">{!! nl2br(e($data->descripcion)) !!}</td>

                    @if($data->discount_concept=='')
                        <td>MXN $ {{ number_format($data->price,2) }}</td>
                    @else
                        <td>MXN $ {{ number_format($data->price,2) }}
                            - {{ ($data->discount_concept=='$')?('$'.$data->discount):$data->discount_concept }}
                        </td>
                    @endif

                    @if($receipt->type=='renta')
                        <td> {{$data->qty}} </td>
                    @else
                        <td> MXN ${{ number_format($data->price - $data->discount) }} x{{$data->qty}}</td>
                    @endif

                    <td> MXN ${{$data->subtotal}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <hr>
        <div align="right">

            <p>Subtotal MXN $ {{ number_format($receipt->subtotal,2) }}</p>

            @if($receipt->discount_concept=='$')
                <p>Descuento MXN $ {{ number_format($receipt->discount,2) }}</p>
            @else
                <p>Descuento % {{ $receipt->discount }}</p>
            @endif

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