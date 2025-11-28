<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMapping;

class VentasPeriodoExport implements FromCollection, WithHeadings, WithMapping
{
    protected $fechaInicio;
    protected $fechaFin;
    protected $shop;
    protected $tipoPeriodo;

    public function __construct($fechaInicio, $fechaFin, $shop, $tipoPeriodo)
    {
        $this->fechaInicio = Carbon::parse($fechaInicio)->startOfDay();
        $this->fechaFin = Carbon::parse($fechaFin)->endOfDay();
        $this->shop = $shop;
        $this->tipoPeriodo = $tipoPeriodo;
    }

    public function collection()
    {
        // Formato SQL para agrupación según tipo de período
        $formatoPeriodo = match($this->tipoPeriodo) {
            'semanal' => '%Y-W%u',
            'trimestral' => '%Y-Q',
            default => '%Y-%m',
        };

        // Obtener receipts en el rango de fechas
        $receiptsQuery = Receipt::where('shop_id', $this->shop->id)
            ->whereBetween('date', [$this->fechaInicio, $this->fechaFin])
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->where('quotation', false);

        // Agrupación por período
        if ($this->tipoPeriodo === 'trimestral') {
            // Para trimestral, calculamos manualmente
            $periodos = $receiptsQuery->get()->groupBy(function($receipt) {
                $fecha = Carbon::parse($receipt->date);
                $trimestre = ceil($fecha->month / 3);
                return $fecha->year . '-T' . $trimestre;
            })->map(function($grupo, $periodo) {
                return (object)[
                    'periodo' => $periodo,
                    'num_tickets' => $grupo->count(),
                    'total_ventas' => $grupo->sum('total'),
                    'ticket_promedio' => $grupo->avg('total'),
                    'ticket_maximo' => $grupo->max('total'),
                    'ticket_minimo' => $grupo->min('total'),
                ];
            })->values();
        } else {
            // Para semanal y mensual usamos DATE_FORMAT
            $periodos = \DB::table('receipts')
                ->selectRaw("
                    DATE_FORMAT(date, '{$formatoPeriodo}') as periodo,
                    COUNT(id) as num_tickets,
                    SUM(total) as total_ventas,
                    AVG(total) as ticket_promedio,
                    MAX(total) as ticket_maximo,
                    MIN(total) as ticket_minimo
                ")
                ->where('shop_id', $this->shop->id)
                ->whereBetween('date', [$this->fechaInicio, $this->fechaFin])
                ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
                ->where('quotation', false)
                ->groupBy('periodo')
                ->orderBy('periodo', 'ASC')
                ->get();
        }

        return collect($periodos);
    }

    public function headings(): array
    {
        return [
            'Período',
            'Número de Tickets',
            'Total Ventas',
            'Ticket Promedio',
            'Ticket Máximo',
            'Ticket Mínimo'
        ];
    }

    public function map($item): array
    {
        return [
            $item->periodo,
            $item->num_tickets,
            number_format($item->total_ventas, 2, '.', ''),
            number_format($item->ticket_promedio, 2, '.', ''),
            number_format($item->ticket_maximo, 2, '.', ''),
            number_format($item->ticket_minimo, 2, '.', ''),
        ];
    }
}
