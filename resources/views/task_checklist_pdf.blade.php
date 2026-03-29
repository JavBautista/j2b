<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Checklist Tarea #{{ $task->id }}</title>
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
        {{-- Header: Tienda + Logo --}}
        <table width="100%">
            <tr>
                <td width="50%" valign="top">
                    <h1>{{ strtoupper($task->shop->name ?? '') }}</h1>
                    @if($task->shop)
                    <p><strong>{{ $task->shop->owner_name }}</strong><br>
                    {{ $task->shop->email }}<br>
                    {{ $task->shop->phone }}</p>
                    @endif
                </td>
                <td width="50%" align="right" valign="top">
                    @if($task->shop && trim($task->shop->logo) != null)
                        <img src="{{ public_path('storage/'.$task->shop->logo) }}" style="max-height: 60px; max-width: 100%; width: auto;">
                    @endif
                </td>
            </tr>
            <tr>
                <td valign="top">
                    @if($task->shop)
                    <p style="font-size: 10px;">{{ $task->shop->address }} {{ $task->shop->number_out }} {{ $task->shop->number_int }} COL. {{ $task->shop->district }}<br>
                    {{ $task->shop->city }}, {{ $task->shop->state }} CP. {{ $task->shop->zip_code }}</p>
                    @endif
                </td>
                <td align="right" valign="top">
                    @if($task->client)
                    <H2>CLIENTE</H2>
                    <p style="font-size: 10px;">{{ $task->client->name }}<br>
                    {{ $task->client->company }}<br>
                    {{ $task->client->movil }}<br>
                    {{ $task->client->mail }}<br>
                    {{ $task->client->address }}</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Info de la tarea --}}
        <table width="100%">
            <tr>
                <td width="15%" valign="top">
                    <img src="{{ public_path('img/j2b_qr.png') }}" alt="QR" width="60">
                </td>
                <td width="40%" valign="top">
                    <h2>TAREA #{{ $task->id }}</h2>
                    <h3>{{ $task->title }}</h3>
                    <p style="font-size: 10px;">
                        Estatus: {{ $task->status }}<br>
                        Prioridad: P{{ $task->priority }}<br>
                        @if($task->assigned_user)
                            Asignado: {{ $task->assigned_user->name }}<br>
                        @endif
                        @if($task->expiration)
                            Vencimiento: {{ $task->expiration }}<br>
                        @endif
                        Creacion: {{ $task->created_at->format('d/m/Y H:i') }}
                    </p>
                </td>
                <td valign="top" style="padding-left: 8px; font-size: 10px;">
                    @if($task->description)
                        <p>{{ $task->description }}</p>
                    @endif
                    @if($task->solution)
                        <p><pre>{{ $task->solution }}</pre></p>
                    @endif
                </td>
            </tr>
        </table>
        <hr>

        {{-- Checklist --}}
        <h2>Checklist</h2>

        <table style="border-collapse: collapse; width: 100%;" width="100%">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 4px 6px; border-bottom: 2px solid #333;" width="8%">#</th>
                    <th style="text-align: left; padding: 4px 6px; border-bottom: 2px solid #333;">Descripcion</th>
                </tr>
            </thead>
            <tbody>
                @foreach($task->checklistItems as $index => $item)
                <tr style="border-top: 1px solid #ccc !important;">
                    <td style="padding: 4px 6px;">{{ $index + 1 }}</td>
                    <td style="padding: 4px 6px;">{{ $item->text }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <hr>

        <!-- Footer J2Biznes -->
        <div style="position: fixed; bottom: 10px; left: 0; right: 0; text-align: center;">
            <p style="font-size: 9px; color: #999; margin: 0;">
                De <img src="{{ public_path('images/heart-j2b.png') }}" style="width: 12px; height: 12px; vertical-align: middle;"> <a href="{{ $pdfPhraseUrl ?? 'https://j2biznes.com' }}" style="color: #555; text-decoration: none; font-weight: bold;">J2Biznes.com</a> - {{ $pdfPhrase ?? 'Tu negocio, simplificado.' }}
            </p>
        </div>
    </body>
</html>
