<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Corte de Caja {{ $fecha }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #2a2a2a; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 2px 0; }
        h2 { font-size: 12px; margin: 14px 0 4px 0; color: #20a8d8; border-bottom: 1px solid #ddd; padding-bottom: 2px; }
        .muted { color: #777; }
        .right { text-align: right; }
        .center { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        th, td { padding: 4px 6px; }
        thead th { background: #f0f3f5; border-bottom: 1px solid #c8ced3; font-size: 10px; text-align: left; }
        tbody td { border-bottom: 1px solid #eee; }
        .tot td { font-weight: bold; border-top: 1px solid #c8ced3; }
        .badge { font-size: 9px; padding: 1px 5px; border-radius: 3px; color: #fff; }
        .b-efectivo { background: #4dbd74; }
        .b-electronico { background: #63c2de; }
        .b-otros { background: #8f9ba6; }
        .box { border: 1px solid #c8ced3; padding: 6px 10px; margin-top: 4px; }
        .ok { color: #4dbd74; }
        .bad { color: #f86c6b; }
        .big { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>

    <h1>{{ $shop->name ?? 'Corte de Caja' }}</h1>
    <div class="muted">Corte de Caja &mdash; {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</div>

    {{-- ===================== ARQUEO DE EFECTIVO ===================== --}}
    <h2>Arqueo de efectivo (cajón)</h2>
    <table>
        <tbody>
            <tr><td>(+) Fondo inicial</td><td class="right">${{ number_format($efectivo['fondo_inicial'], 2) }}</td></tr>
            <tr><td>(+) Cobros en efectivo del día</td><td class="right">${{ number_format($efectivo['cobros'], 2) }}</td></tr>
            <tr><td>(&minus;) Retiros en efectivo</td><td class="right">${{ number_format($efectivo['retiros'], 2) }}</td></tr>
            <tr class="tot"><td>(=) Efectivo esperado en cajón</td><td class="right">${{ number_format($efectivo['esperado'], 2) }}</td></tr>
            @if(!is_null($contado))
                <tr><td>Efectivo contado (físico)</td><td class="right">${{ number_format($contado, 2) }}</td></tr>
                <tr class="tot">
                    <td>Diferencia
                        @if($diferencia > 0) (sobrante) @elseif($diferencia < 0) (faltante) @else (cuadrado) @endif
                    </td>
                    <td class="right {{ $diferencia > 0 ? 'ok' : ($diferencia < 0 ? 'bad' : '') }}">
                        ${{ number_format($diferencia, 2) }}
                    </td>
                </tr>
            @else
                <tr><td colspan="2" class="muted">Sin conteo físico capturado.</td></tr>
            @endif
        </tbody>
    </table>

    {{-- ===================== DESGLOSE FORMAS DE PAGO ===================== --}}
    <h2>Ingresos por forma de pago</h2>
    <table>
        <thead>
            <tr><th>Forma de pago</th><th>Plano</th><th class="right">Total</th></tr>
        </thead>
        <tbody>
            @forelse($formas_pago as $f)
                <tr>
                    <td>{{ $f['nombre'] }} <span class="muted">({{ $f['code'] }})</span></td>
                    <td>
                        @if($f['plano'] === 'efectivo')
                            <span class="badge b-efectivo">Efectivo</span>
                        @elseif($f['plano'] === 'electronico')
                            <span class="badge b-electronico">Banco</span>
                        @else
                            <span class="badge b-otros">Otros</span>
                        @endif
                    </td>
                    <td class="right">${{ number_format($f['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="center muted">Sin cobros este día.</td></tr>
            @endforelse
            <tr class="tot">
                <td colspan="2">Total ingresos del día</td>
                <td class="right">${{ number_format($resumen['ingresos_total'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ===================== TRANSFERENCIAS POR CUENTA ===================== --}}
    @if(count($transferencias_por_cuenta) > 0)
        <h2>Cobros electrónicos por cuenta bancaria (conciliación)</h2>
        <table>
            <thead>
                <tr><th>Cuenta</th><th>Banco</th><th class="right">Total</th></tr>
            </thead>
            <tbody>
                @foreach($transferencias_por_cuenta as $c)
                    <tr>
                        <td>{{ $c['alias'] }}</td>
                        <td>{{ $c['banco'] }}</td>
                        <td class="right">${{ number_format($c['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ===================== RESUMEN DEL DÍA ===================== --}}
    <h2>Resumen del día</h2>
    <table>
        <tbody>
            <tr><td>Total ingresos (todas las formas)</td><td class="right">${{ number_format($resumen['ingresos_total'] ?? 0, 2) }}</td></tr>
            <tr><td>(&minus;) Egresos &mdash; compras a proveedores</td><td class="right">${{ number_format($resumen['egresos_compras'] ?? 0, 2) }}</td></tr>
            <tr><td>(&minus;) Egresos &mdash; gastos operativos</td><td class="right">${{ number_format($resumen['egresos_gastos'] ?? 0, 2) }}</td></tr>
            <tr class="tot">
                <td>Resultado del día</td>
                <td class="right {{ ($resumen['resultado'] ?? 0) >= 0 ? 'ok' : 'bad' }}">
                    ${{ number_format($resumen['resultado'] ?? 0, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- ===================== DETALLE DE COBROS ===================== --}}
    <h2>Detalle de cobros del día</h2>
    <table>
        <thead>
            <tr><th>Hora</th><th>Folio</th><th>Cliente</th><th>Forma</th><th class="right">Monto</th></tr>
        </thead>
        <tbody>
            @forelse($detalle as $d)
                <tr>
                    <td>{{ $d['hora'] }}</td>
                    <td>{{ $d['folio'] }}</td>
                    <td>{{ $d['cliente'] }}</td>
                    <td>{{ $d['forma'] }}</td>
                    <td class="right">${{ number_format($d['monto'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center muted">Sin cobros este día.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="muted" style="margin-top:18px; font-size:9px;">
        Nota: el arqueo (sobrante/faltante) aplica solo al efectivo. Los cobros electrónicos
        (transferencia/tarjeta) se concilian contra el estado de cuenta del banco, no contra el cajón.
    </p>

</body>
</html>
