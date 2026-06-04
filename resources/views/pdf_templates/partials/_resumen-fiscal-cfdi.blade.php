{{--
    Partial: Resumen fiscal del CFDI en el PDF del recibo/remisión.
    Se incluye en las plantillas de recibo (j2b: receipt_rent_pdf · comyser: pdf_templates/comyser/receipt).
    Plan: xdev/facturacion/PLAN_PDF_RECIBO_FISCAL.md

    CONDICIONAL: solo se renderiza si la nota tiene CFDI vigente CON retenciones federales
    o impuestos locales. Notas no facturadas, cotizaciones, o facturas normales (PUE sin
    retención) NO muestran nada → PDF idéntico a hoy. Cero impacto en cobranza.

    Fuente de verdad = el propio CFDI ($receipt->cfdiInvoice ya filtra status='vigente').
    El total mostrado es $cfdi->total tal cual (no se recalcula nada) → cuadra exacto con la factura.

    TEMA: el color se pasa desde cada plantilla vía @include para que combine con su diseño:
      - j2b     → @include(..., ['fiscalColor' => '#1a4d8f', 'fiscalColorSoft' => '#eef4fb'])
      - comyser → @include(..., ['fiscalColor' => '#16386b', 'fiscalColorSoft' => '#eef2f8'])
    Si no se pasan, usa azul neutro por defecto.
--}}
@php
    $cfdi = $receipt->cfdiInvoice ?? null;

    $retFedGlobales = $cfdi ? $cfdi->retenciones->whereNull('concepto_index') : collect();
    $retLocales     = $cfdi ? $cfdi->retencionesLocales : collect();
    $trasLocales    = $cfdi ? $cfdi->trasladosLocales : collect();

    // Mismo criterio que CfdiInvoice::tieneDesgloseFiscal() (fuente única de verdad)
    $mostrarFiscal = $cfdi && $cfdi->tieneDesgloseFiscal();

    // Tema de color (combina con cada plantilla). Default azul neutro.
    $fcColor = $fiscalColor ?? '#1a4d8f';
    $fcSoft  = $fiscalColorSoft ?? '#eef4fb';
@endphp

@if($mostrarFiscal)
    @php
        $simbolo = $receipt->shop->getCurrencySymbol();
        $nombreImp = ['001' => 'ISR', '002' => 'IVA', '003' => 'IEPS'];

        // Las retenciones GLOBALES no guardan tasa (regla SAT); la tomamos de las por-concepto.
        $tasasFed = [];
        if ($cfdi) {
            foreach ($cfdi->retenciones->whereNotNull('concepto_index') as $rc) {
                $tasasFed[$rc->impuesto] = (float) $rc->tasa; // decimal (0.10)
            }
        }

        // IVA trasladado global (para etiqueta "IVA 16%")
        $ivaTras    = $cfdi ? $cfdi->traslados->whereNull('concepto_index')->first() : null;
        $ivaTasaPct = $ivaTras ? ((float) $ivaTras->tasa) * 100 : null;
        $ivaNombre  = $receipt->shop->tax_name ?? 'IVA';

        // Formatea porcentaje limpio: 10 → "10", 10.6667 → "10.6667", 5.00 → "5"
        $fmtPct = function ($pct) {
            if ($pct === null) return '';
            return rtrim(rtrim(number_format((float) $pct, 4, '.', ''), '0'), '.');
        };

        $folioCfdi = trim(($cfdi->serie ?? '') . '-' . ($cfdi->folio ?? ''), '-');
        $fechaCfdi = $cfdi->fecha_timbrado ?? $cfdi->fecha_emision ?? null;

        // Estilos reutilizables (dompdf: solo inline)
        $celda    = 'padding: 3px 9px; border-bottom: 1px solid #e6e6e6;';
        $celdaR   = $celda . ' text-align: right;';
        $colResta = '#b00020'; // retenciones (restan)
        $colSuma  = '#2c7a40'; // traslados locales (suman)
    @endphp

    {{-- Wrapper de 2 celdas: izquierda vacía empuja, derecha fija → alinea a la derecha (robusto en dompdf) --}}
    <table width="100%" style="border-collapse: collapse; margin-top: 12px;">
        <tr>
            <td>&nbsp;</td>
            <td style="width: 320px;">
                <table width="100%" style="border: 1px solid {{ $fcColor }}; border-collapse: collapse; font-size: 10px; font-family: 'DejaVu Sans', sans-serif;">
                    {{-- Encabezado: leyenda de factura --}}
                    <tr>
                        <td colspan="2" style="background-color: {{ $fcColor }}; color: #ffffff; font-weight: bold; padding: 5px 9px; font-size: 10.5px; letter-spacing: 0.3px;">
                            &#10004; NOTA FACTURADA &middot; CFDI {{ $folioCfdi }}
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="background-color: {{ $fcSoft }}; color: #555555; padding: 3px 9px; font-size: 7.5px; word-break: break-all; font-family: 'Courier New', monospace;">
                            UUID: {{ $cfdi->uuid }}
                            @if($fechaCfdi)
                                &middot; {{ \Carbon\Carbon::parse($fechaCfdi)->format('d/m/Y') }}
                            @endif
                        </td>
                    </tr>

                    {{-- Subtotal --}}
                    <tr>
                        <td style="{{ $celda }}">Subtotal</td>
                        <td style="{{ $celdaR }}">{{ $simbolo }}{{ number_format($cfdi->subtotal, 2) }}</td>
                    </tr>

                    {{-- IVA trasladado --}}
                    @if((float) $cfdi->total_impuestos > 0)
                    <tr>
                        <td style="{{ $celda }}">{{ $ivaNombre }}@if($ivaTasaPct !== null) {{ $fmtPct($ivaTasaPct) }}%@endif</td>
                        <td style="{{ $celdaR }}">{{ $simbolo }}{{ number_format($cfdi->total_impuestos, 2) }}</td>
                    </tr>
                    @endif

                    {{-- Retenciones federales (ISR / IVA) --}}
                    @foreach($retFedGlobales as $r)
                    <tr>
                        <td style="{{ $celda }} color: {{ $colResta }};">
                            Retención {{ $nombreImp[$r->impuesto] ?? $r->impuesto }}@if(isset($tasasFed[$r->impuesto])) {{ $fmtPct($tasasFed[$r->impuesto] * 100) }}%@endif
                        </td>
                        <td style="{{ $celdaR }} color: {{ $colResta }};">-{{ $simbolo }}{{ number_format($r->importe, 2) }}</td>
                    </tr>
                    @endforeach

                    {{-- Impuestos locales retenidos (restan) --}}
                    @foreach($retLocales as $il)
                    <tr>
                        <td style="{{ $celda }} color: {{ $colResta }};">Ret. Local {{ $il->nombre }} {{ $fmtPct($il->tasa_porcentaje) }}%</td>
                        <td style="{{ $celdaR }} color: {{ $colResta }};">-{{ $simbolo }}{{ number_format($il->importe, 2) }}</td>
                    </tr>
                    @endforeach

                    {{-- Impuestos locales trasladados (suman) --}}
                    @foreach($trasLocales as $il)
                    <tr>
                        <td style="{{ $celda }} color: {{ $colSuma }};">Tras. Local {{ $il->nombre }} {{ $fmtPct($il->tasa_porcentaje) }}%</td>
                        <td style="{{ $celdaR }} color: {{ $colSuma }};">+{{ $simbolo }}{{ number_format($il->importe, 2) }}</td>
                    </tr>
                    @endforeach

                    {{-- Total facturado (= total del CFDI) --}}
                    <tr>
                        <td style="background-color: {{ $fcColor }}; color: #ffffff; padding: 5px 9px; font-weight: bold; font-size: 11px;">Total facturado</td>
                        <td style="background-color: {{ $fcColor }}; color: #ffffff; padding: 5px 9px; font-weight: bold; font-size: 11px; text-align: right;">{{ $simbolo }}{{ number_format($cfdi->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endif
