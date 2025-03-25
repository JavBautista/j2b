<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMapping;

class EgresosExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $shop;

    public function __construct($fechaInicio, $fechaFin, $shop)
    {
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
        $this->shop = $shop;
    }

    public function collection()
    {
        return PurchaseOrder::with(['partialPayments', 'supplier'])
            ->where('shop_id', $this->shop->id)
            ->whereHas('partialPayments', function ($query) {
                $query->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin]);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Proveedor',
            'Folio',
            'Fecha',
            'Pagos Parciales',
            'Cantidad de Pagos'
        ];
    }

    public function map($purchase): array
    {
        $pagosFiltrados = $purchase->partialPayments->filter(function ($pp) {
            return Carbon::parse($pp->created_at)->between($this->fechaInicio, $this->fechaFin);
        });

        $totalPagos = $pagosFiltrados->sum('amount');
        return [
            $purchase->id,
            $purchase->supplier->name ?? 'Sin Proveedor',
            $purchase->folio,
            $purchase->created_at->format('Y-m-d'),
            number_format($totalPagos, 2, '.', ''),
            $pagosFiltrados->count(),
        ];
    }
}
