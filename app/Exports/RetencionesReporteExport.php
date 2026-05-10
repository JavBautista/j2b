<?php

namespace App\Exports;

use App\Models\CfdiInvoice;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RetencionesReporteExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shop;
    protected $fechaInicio;
    protected $fechaFin;

    public function __construct($shop, $fechaInicio, $fechaFin)
    {
        $this->shop = $shop;
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
    }

    public function collection()
    {
        return CfdiInvoice::where('shop_id', $this->shop->id)
            ->where('status', 'vigente')
            ->where('total_retenciones', '>', 0)
            ->whereBetween('fecha_emision', [$this->fechaInicio, $this->fechaFin])
            ->with('retenciones')
            ->orderBy('fecha_emision', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'UUID', 'Serie-Folio', 'Fecha Emisión', 'Receptor RFC', 'Receptor Nombre',
            'Subtotal', 'IVA Trasladado', 'Ret. ISR', 'Ret. IVA', 'Total Retenciones', 'Total CFDI',
        ];
    }

    public function map($f): array
    {
        $retIsr = (float) $f->retenciones()->whereNull('concepto_index')->where('impuesto', '001')->sum('importe');
        $retIva = (float) $f->retenciones()->whereNull('concepto_index')->where('impuesto', '002')->sum('importe');

        return [
            $f->uuid,
            $f->serie . $f->folio,
            $f->fecha_emision ? $f->fecha_emision->format('d/m/Y H:i') : '',
            $f->receptor_rfc,
            $f->receptor_nombre,
            number_format($f->subtotal, 2, '.', ''),
            number_format($f->total_impuestos, 2, '.', ''),
            number_format($retIsr, 2, '.', ''),
            number_format($retIva, 2, '.', ''),
            number_format($f->total_retenciones, 2, '.', ''),
            number_format($f->total, 2, '.', ''),
        ];
    }
}
