<?php

namespace App\Exports;

use App\Models\CfdiInvoice;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FacturasEmitidasExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shop;
    protected $fechaInicio;
    protected $fechaFin;
    protected $status;

    public function __construct($shop, $fechaInicio, $fechaFin, $status = 'todos')
    {
        $this->shop = $shop;
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
        $this->status = $status;
    }

    public function collection()
    {
        $query = CfdiInvoice::where('shop_id', $this->shop->id)
            ->whereBetween('fecha_emision', [$this->fechaInicio, $this->fechaFin]);

        if ($this->status && $this->status !== 'todos') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('fecha_emision', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'UUID',
            'Serie-Folio',
            'Fecha Emisión',
            'Fecha Timbrado',
            'Receptor RFC',
            'Receptor Nombre',
            'Subtotal',
            'IVA',
            'Total',
            'Status',
        ];
    }

    public function map($factura): array
    {
        return [
            $factura->uuid,
            $factura->serie . $factura->folio,
            $factura->fecha_emision ? $factura->fecha_emision->format('d/m/Y H:i') : '',
            $factura->fecha_timbrado ? $factura->fecha_timbrado->format('d/m/Y H:i') : '',
            $factura->receptor_rfc,
            $factura->receptor_nombre,
            number_format($factura->subtotal, 2, '.', ''),
            number_format($factura->total_impuestos, 2, '.', ''),
            number_format($factura->total, 2, '.', ''),
            $factura->status,
        ];
    }
}
