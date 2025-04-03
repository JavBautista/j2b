<?php

namespace App\Exports;

use App\Models\Expense;
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
    protected $egresos;

    public function __construct($fechaInicio, $fechaFin, $shop)
    {
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
        $this->shop = $shop;
        $this->egresos = collect(); 
    }

    public function collection()
    {
        // 1. Purchase Orders
        $purchases = PurchaseOrder::with(['partialPayments', 'supplier'])
            ->where('shop_id', $this->shop->id)
            ->whereHas('partialPayments', function ($query) {
                $query->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin]);
            })
            ->get();

        foreach ($purchases as $purchase) {
            $pagos = $purchase->partialPayments->filter(function ($pp) {
                return Carbon::parse($pp->created_at)->between($this->fechaInicio, $this->fechaFin);
            });

            $this->egresos->push((object)[
                'tipo' => 'Compra',
                'id' => $purchase->id,
                'nombre' => $purchase->supplier->name ?? 'Sin Proveedor',
                'folio' => $purchase->folio,
                'fecha' => $purchase->created_at->format('Y-m-d'),
                'monto' => $pagos->sum('amount'),
                'cantidad_pagos' => $pagos->count()
            ]);
        }

        // 2. Expenses
        $expenses = Expense::where('shop_id', $this->shop->id)
            ->where('status', 'PAGADO')
            ->whereBetween('date', [$this->fechaInicio, $this->fechaFin])
            ->get();

        foreach ($expenses as $expense) {
            $this->egresos->push((object)[
                'tipo' => 'Gasto',
                'id' => $expense->id,
                'nombre' => $expense->name,
                'folio' => null,
                'fecha' => Carbon::parse($expense->date)->format('Y-m-d'),
                'monto' => $expense->total,
                'cantidad_pagos' => 1
            ]);
        }

        return $this->egresos->sortByDesc('fecha')->values();
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'ID',
            'Nombre / Proveedor',
            'Folio',
            'Fecha',
            'Monto',
            'Cantidad de Pagos'
        ];
    }

     public function map($egreso): array
    {
        return [
            $egreso->tipo,
            $egreso->id,
            $egreso->nombre,
            $egreso->folio ?? '-',
            $egreso->fecha,
            number_format($egreso->monto, 2, '.', ''),
            $egreso->cantidad_pagos,
        ];
    }
}
