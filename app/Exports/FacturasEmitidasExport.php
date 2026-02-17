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
    protected $shopId;

    public function __construct($shop, $fechaInicio, $fechaFin, $status = 'todos', $shopId = null)
    {
        $this->shop = $shop;
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
        $this->status = $status;
        $this->shopId = $shopId;
    }

    public function collection()
    {
        $query = CfdiInvoice::with('shop:id,name')
            ->whereBetween('fecha_emision', [$this->fechaInicio, $this->fechaFin]);

        // Si hay shop (admin), filtrar por su id
        if ($this->shop) {
            $query->where('shop_id', $this->shop->id);
        }

        // Si hay shopId explícito (superadmin filtrando por tienda)
        if ($this->shopId) {
            $query->where('shop_id', $this->shopId);
        }

        if ($this->status && $this->status !== 'todos') {
            $query->where('status', $this->status);
        }

        return $query->orderBy('fecha_emision', 'desc')->get();
    }

    public function headings(): array
    {
        $headers = ['UUID', 'Serie-Folio', 'Fecha Emisión', 'Fecha Timbrado', 'Receptor RFC', 'Receptor Nombre', 'Subtotal', 'IVA', 'Total', 'Status'];

        // Si es superadmin (sin shop), agregar columna Tienda
        if (!$this->shop) {
            array_splice($headers, 1, 0, 'Tienda');
        }

        return $headers;
    }

    public function map($factura): array
    {
        $row = [
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

        if (!$this->shop) {
            array_splice($row, 1, 0, $factura->shop ? $factura->shop->name : '-');
        }

        return $row;
    }
}
