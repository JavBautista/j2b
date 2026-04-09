<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Comprobante de Recepcion #{{ $task->folio ?: $task->id }}</title>
    <style>
        body{
            font-family: Verdana, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
        }
        h1 { font-size: 16px; margin: 0 0 3px 0; }
        h2 { font-size: 13px; margin: 0 0 3px 0; color: #16213e; }
        h3 { font-size: 11px; margin: 0 0 2px 0; }
        p { margin: 2px 0; line-height: 1.4; }
        .section-title {
            background: #16213e;
            color: #fff;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: bold;
            margin: 10px 0 6px 0;
            letter-spacing: 0.5px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table td {
            padding: 3px 6px;
            vertical-align: top;
            font-size: 10px;
            border-bottom: 1px solid #eee;
        }
        .data-table .label {
            font-weight: bold;
            width: 30%;
            color: #555;
        }
        .tracking-step {
            display: inline-block;
            padding: 2px 8px;
            margin: 2px 3px 2px 0;
            border-radius: 10px;
            font-size: 9px;
            color: #fff;
        }
        .tracking-current {
            border: 2px solid #333;
            font-weight: bold;
        }
        .disclaimer {
            background: #f5f5f5;
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 9px;
            color: #555;
            margin-top: 12px;
            line-height: 1.4;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 200px;
            margin-top: 50px;
            padding-top: 4px;
            text-align: center;
            font-size: 10px;
            color: #555;
        }
    </style>
    </head>
    <body>
        {{-- HEADER: Tienda + Logo --}}
        <table width="100%">
            <tr>
                <td width="60%" valign="top">
                    <h1>{{ strtoupper($task->shop->name ?? '') }}</h1>
                    @if($task->shop->slogan)
                        <p style="font-size: 9px; color: #888; margin-bottom: 4px;">{{ $task->shop->slogan }}</p>
                    @endif
                    @if($task->shop)
                    <p style="font-size: 10px;">
                        {{ $task->shop->address }} {{ $task->shop->number_out }} {{ $task->shop->number_int }}
                        @if($task->shop->district) COL. {{ $task->shop->district }} @endif
                        <br>
                        {{ $task->shop->city }}, {{ $task->shop->state }}
                        @if($task->shop->zip_code) CP. {{ $task->shop->zip_code }} @endif
                        <br>
                        @if($task->shop->phone) Tel: {{ $task->shop->phone }} @endif
                        @if($task->shop->whatsapp) | WhatsApp: {{ $task->shop->whatsapp }} @endif
                    </p>
                    @endif
                </td>
                <td width="40%" align="right" valign="top">
                    @if($task->shop && trim($task->shop->logo) != null)
                        <img src="{{ public_path('storage/'.$task->shop->logo) }}" style="max-height: 65px; max-width: 100%; width: auto;">
                    @endif
                    <br>
                    <p style="font-size: 18px; font-weight: bold; margin-top: 5px; color: #16213e;">
                        COMPROBANTE DE<br>RECEPCION
                    </p>
                </td>
            </tr>
        </table>

        {{-- FOLIO Y FECHA --}}
        <table width="100%" style="margin-top: 5px;">
            <tr>
                <td width="50%">
                    <p style="font-size: 10px;">
                        <strong>FECHA:</strong> {{ $task->created_at->format('d / M / Y') }}
                        &nbsp;&nbsp;<strong>HORA:</strong> {{ $task->created_at->format('H:i') }}
                    </p>
                </td>
                <td width="50%" align="right">
                    <p style="font-size: 10px;">
                        <strong>FOLIO:</strong> {{ str_pad($task->folio ?: $task->id, 3, '0', STR_PAD_LEFT) }}
                    </p>
                </td>
            </tr>
        </table>

        <hr style="border: 1px solid #16213e; margin: 5px 0;">

        {{-- DATOS DEL CLIENTE --}}
        <div class="section-title">DATOS DEL CLIENTE</div>
        <table class="data-table">
            <tr>
                <td class="label">Cliente:</td>
                <td>{{ $task->client->name ?? 'Sin cliente asignado' }}</td>
                <td class="label" style="width: 20%;">Telefono:</td>
                <td>{{ $task->client->movil ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Domicilio:</td>
                <td colspan="3">
                    @if($task->client && $task->client->addresses && $task->client->addresses->count() > 0)
                        {{ $task->client->addresses->first()->street ?? '' }}
                        {{ $task->client->addresses->first()->number ?? '' }},
                        {{ $task->client->addresses->first()->district ?? '' }}
                        {{ $task->client->addresses->first()->city ?? '' }}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @if($task->client && $task->client->email)
            <tr>
                <td class="label">Email:</td>
                <td colspan="3">{{ $task->client->email }}</td>
            </tr>
            @endif
        </table>

        {{-- DATOS DEL SERVICIO --}}
        <div class="section-title">DATOS DEL SERVICIO</div>
        <table class="data-table">
            <tr>
                <td class="label">Servicio / Equipo:</td>
                <td colspan="3">{{ $task->title }}</td>
            </tr>
            <tr>
                <td class="label">Descripcion:</td>
                <td colspan="3">{{ $task->description ?: '-' }}</td>
            </tr>
            @if($task->solution)
            <tr>
                <td class="label">Observaciones:</td>
                <td colspan="3">{{ $task->solution }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Prioridad:</td>
                <td>P{{ $task->priority }}</td>
                <td class="label" style="width: 25%;">Fecha estimada:</td>
                <td>{{ $task->expiration ? \Carbon\Carbon::parse($task->expiration)->format('d/m/Y') : 'Por definir' }}</td>
            </tr>
            @if($task->assignedUser)
            <tr>
                <td class="label">Recibido por:</td>
                <td colspan="3">{{ $task->assignedUser->name }}</td>
            </tr>
            @endif
        </table>

        {{-- INFORMACION ADICIONAL (campos extra) --}}
        @if($task->infoExtra && $task->infoExtra->count() > 0)
        <div class="section-title">INFORMACION ADICIONAL</div>
        <table class="data-table">
            @foreach($task->infoExtra->chunk(2) as $chunk)
            <tr>
                @foreach($chunk as $extra)
                <td class="label" style="width: 20%;">{{ $extra->field_name }}:</td>
                <td>{{ $extra->value }}</td>
                @endforeach
                @if($chunk->count() == 1)
                <td></td><td></td>
                @endif
            </tr>
            @endforeach
        </table>
        @endif

        {{-- MATERIALES / REFACCIONES --}}
        @if($task->products && $task->products->count() > 0)
        <div class="section-title">MATERIALES / REFACCIONES</div>
        <table class="data-table">
            <tr style="background: #f0f0f0;">
                <td class="label" style="width: 15%; text-align: center;">Cantidad</td>
                <td class="label">Producto</td>
                <td class="label" style="width: 30%;">Notas</td>
            </tr>
            @foreach($task->products as $tp)
            <tr>
                <td style="text-align: center;">{{ $tp->qty_delivered }}</td>
                <td>{{ $tp->product->name ?? 'Producto' }}</td>
                <td>{{ $tp->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </table>
        @endif

        {{-- SEGUIMIENTO (solo si tiene tracking) --}}
        @if($task->tracking_code && $steps && $steps->count() > 0)
        <div class="section-title">SEGUIMIENTO DE SERVICIO</div>
        <table width="100%">
            <tr>
                <td width="70%" valign="top" style="padding: 6px;">
                    <p style="font-size: 10px; margin-bottom: 6px;">
                        <strong>Codigo:</strong> {{ $task->tracking_code }}
                    </p>
                    <p style="font-size: 9px; color: #555; margin-bottom: 4px;">Pasos del servicio:</p>
                    @foreach($steps as $index => $step)
                        @php
                            $currentIndex = $steps->search(fn($s) => $s->id === $task->current_service_step_id);
                            $isCompleted = $index < $currentIndex;
                            $isCurrent = $index == $currentIndex;
                        @endphp
                        <span class="tracking-step {{ $isCurrent ? 'tracking-current' : '' }}"
                              style="background: {{ $isCurrent || $isCompleted ? ($step->color ?? '#0d6efd') : '#ccc' }};">
                            {{ $isCurrent ? '> ' : '' }}{{ $step->name }}
                        </span>
                    @endforeach
                    <p style="font-size: 9px; color: #888; margin-top: 6px;">
                        Escanea el codigo QR para consultar el avance de tu servicio en tiempo real.
                    </p>
                </td>
                <td width="30%" align="center" valign="top" style="padding: 6px;">
                    @if(!empty($qrImage))
                        @php $trackingUrl = url('/service-tracking/' . $task->tracking_code); @endphp
                        <img src="{{ $qrImage }}" alt="QR" width="120">
                        <br>
                        <a href="{{ $trackingUrl }}" style="font-size: 8px; color: #0d6efd; word-break: break-all;">{{ $trackingUrl }}</a>
                    @endif
                </td>
            </tr>
        </table>
        @endif

        {{-- DISCLAIMER --}}
        @php
            $disclaimer = $task->shop->receipt_disclaimer ?? 'Este comprobante es un documento informativo. Conservelo para cualquier aclaracion sobre su servicio.';
        @endphp
        <div class="disclaimer">
            {{ $disclaimer }}
        </div>

        {{-- FIRMA --}}
        <table width="100%" style="margin-top: 15px;">
            <tr>
                <td width="50%" align="center" valign="bottom">
                    @if($task->signature_path)
                        <img src="{{ public_path('storage/'.$task->signature_path) }}" style="max-height: 60px; max-width: 180px;">
                        <div class="signature-line" style="margin: 5px auto 0;">Firma del cliente</div>
                    @else
                        <div class="signature-line" style="margin: 0 auto;">Firma del cliente</div>
                    @endif
                </td>
                <td width="50%" align="center" valign="bottom">
                    @if($task->shop->legal_representative_signature_path)
                        <img src="{{ public_path('storage/'.$task->shop->legal_representative_signature_path) }}" style="max-height: 60px; max-width: 180px;">
                    @endif
                    <div class="signature-line" style="margin: {{ $task->shop->legal_representative_signature_path ? '5px' : '0' }} auto 0;">Firma del negocio</div>
                </td>
            </tr>
        </table>

        {{-- FOOTER --}}
        <div style="position: fixed; bottom: 10px; left: 0; right: 0; text-align: center;">
            <p style="font-size: 9px; color: #999; margin: 0;">
                De <img src="{{ public_path('images/heart-j2b.png') }}" style="width: 12px; height: 12px; vertical-align: middle;"> <a href="{{ $pdfPhraseUrl ?? 'https://j2biznes.com' }}" style="color: #555; text-decoration: none; font-weight: bold;">J2Biznes.com</a> - {{ $pdfPhrase ?? 'Tu negocio, simplificado.' }}
            </p>
        </div>
    </body>
</html>
