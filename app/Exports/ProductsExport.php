<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $shop_id;

    public function __construct($shop_id)
    {
        $this->shop_id = $shop_id;
    }

    public function collection()
    {
        return Product::with('category')
            ->where('shop_id', $this->shop_id)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Activo',
            'Código/SKU',
            'Código de Barras',
            'Nombre',
            'Descripción',
            'Categoría',
            'Costo',
            'Precio Venta',
            'Precio Mayoreo',
            'Stock',
            'Imagen URL',
            'Video URL',
            'Fecha Creación',
            'Fecha Actualización'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->active ? 'Sí' : 'No',
            $product->key ?? '',
            $product->barcode ?? '',
            $product->name ?? '',
            $product->description ?? '',
            $product->category->name ?? 'Sin Categoría',
            $product->cost ?? 0,
            $product->retail ?? 0,
            $product->wholesale ?? 0,
            $product->stock ?? 0,
            $product->image ?? '',
            $product->url_video ?? '',
            $product->created_at ? $product->created_at->format('Y-m-d H:i:s') : '',
            $product->updated_at ? $product->updated_at->format('Y-m-d H:i:s') : '',
        ];
    }
}
