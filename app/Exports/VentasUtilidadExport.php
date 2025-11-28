<?php

namespace App\Exports;

use App\Models\Receipt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\WithMapping;

class VentasUtilidadExport implements FromCollection, WithHeadings, WithMapping
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
        // Obtener receipts con detalles en el rango de fechas
        $receipts = Receipt::with(['detail.product.category', 'client'])
            ->where('shop_id', $this->shop->id)
            ->where('quotation', 0)
            ->whereNotIn('status', ['CANCELADA', 'DEVOLUCION'])
            ->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin])
            ->get();

        // Agrupar por producto/servicio
        $ventasAgrupadas = [];

        foreach ($receipts as $receipt) {
            foreach ($receipt->detail as $detail) {
                // Clave única por producto o servicio
                if ($detail->type === 'product' && $detail->product) {
                    $key = 'product_' . $detail->product->id;
                    $nombre = $detail->product->name;
                    $categoria = $detail->product->category->name ?? 'Sin categoría';
                    $costo = $detail->product->cost ?? 0;
                } else {
                    // Es servicio
                    $key = 'service_' . md5($detail->descripcion);
                    $nombre = $detail->descripcion;
                    $categoria = 'Servicios';
                    $costo = 0;
                }

                if (!isset($ventasAgrupadas[$key])) {
                    $ventasAgrupadas[$key] = [
                        'nombre' => $nombre,
                        'categoria' => $categoria,
                        'type' => $detail->type,
                        'cantidad_vendida' => 0,
                        'total_ventas' => 0,
                        'costo_total' => 0,
                    ];
                }

                $ventasAgrupadas[$key]['cantidad_vendida'] += $detail->qty;
                $ventasAgrupadas[$key]['total_ventas'] += ($detail->qty * $detail->price);
                $ventasAgrupadas[$key]['costo_total'] += ($detail->qty * $costo);
            }
        }

        // Convertir a collection con cálculos
        $ventas = collect();
        foreach ($ventasAgrupadas as $item) {
            $utilidad_bruta = $item['total_ventas'] - $item['costo_total'];
            $margen_porcentaje = $item['total_ventas'] > 0
                ? round(($utilidad_bruta / $item['total_ventas']) * 100, 2)
                : 0;

            $ventas->push((object)[
                'nombre' => $item['nombre'],
                'categoria' => $item['categoria'],
                'type' => $item['type'],
                'cantidad_vendida' => $item['cantidad_vendida'],
                'total_ventas' => $item['total_ventas'],
                'costo_total' => $item['costo_total'],
                'utilidad_bruta' => $utilidad_bruta,
                'margen_porcentaje' => $margen_porcentaje
            ]);
        }

        // Ordenar por utilidad bruta descendente
        return $ventas->sortByDesc('utilidad_bruta')->values();
    }

    public function headings(): array
    {
        return [
            'Producto/Servicio',
            'Categoría',
            'Tipo',
            'Cantidad',
            'Ventas',
            'Costo',
            'Utilidad',
            'Margen %'
        ];
    }

    public function map($item): array
    {
        return [
            $item->nombre,
            $item->categoria,
            $item->type === 'product' ? 'Producto' : 'Servicio',
            $item->cantidad_vendida,
            number_format($item->total_ventas, 2, '.', ''),
            number_format($item->costo_total, 2, '.', ''),
            number_format($item->utilidad_bruta, 2, '.', ''),
            number_format($item->margen_porcentaje, 2, '.', '') . '%'
        ];
    }
}
