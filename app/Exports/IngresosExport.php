<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMapping;

class IngresosExport implements FromCollection, WithHeadings, WithMapping
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
        return Receipt::with(['partialPayments', 'client'])
            ->where('shop_id', $this->shop->id)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('partialPayments', function ($subquery) {
                        $subquery->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin]);
                    });
                })->orWhere(function ($q) {
                    $q->where('finished', 1)
                      ->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin]);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Cliente',
            'Folio',
            'Fecha',
            'Descripción',
            'Monto',
            'Pagos Parciales'
        ];
    }

     public function map($receipt): array
    {
        $pagosFiltrados = $receipt->partialPayments->filter(function ($pp) {
            return Carbon::parse($pp->created_at)->between($this->fechaInicio, $this->fechaFin);
        });

        $monto = 0;
        if ($receipt->finished && $receipt->created_at->between($this->fechaInicio, $this->fechaFin)) {
            $monto = $receipt->received;
        } else {
            $monto = $pagosFiltrados->sum('amount');
        }

        return [
            $receipt->id,
            $receipt->client->name ?? 'Sin Cliente',
            $receipt->folio,
            $receipt->created_at->format('Y-m-d'),
            ucfirst($receipt->type),
            number_format($monto, 2, '.', ''),
            number_format($pagosFiltrados->sum('amount'), 2, '.', ''),
        ];
    }
}
