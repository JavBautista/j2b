<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReceiptDetail;
use App\Models\Product;

class FillCostReceiptDetailsSeeder extends Seeder
{
    /**
     * Seeder idempotente para llenar el campo cost en receipt_details históricos.
     * Solo actualiza registros donde cost sea NULL o 0.
     * Puede ejecutarse múltiples veces sin duplicar datos.
     */
    public function run(): void
    {
        $this->command->info('Iniciando llenado de cost en receipt_details...');

        // Obtener solo registros donde cost es NULL o 0
        $details = ReceiptDetail::whereNull('cost')
            ->orWhere('cost', 0)
            ->get();

        $totalRegistros = $details->count();
        $actualizados = 0;
        $sinProducto = 0;

        $this->command->info("Registros a procesar: {$totalRegistros}");

        foreach ($details as $detail) {
            $cost = 0;

            // Solo buscar costo si es tipo product y tiene product_id
            if ($detail->type === 'product' && $detail->product_id) {
                $product = Product::find($detail->product_id);
                if ($product) {
                    $cost = $product->cost ?? 0;
                } else {
                    $sinProducto++;
                }
            }
            // Para service, equipment u otros tipos: cost = 0

            // Actualizar el registro
            $detail->cost = $cost;
            $detail->save();
            $actualizados++;
        }

        $this->command->info("Proceso completado:");
        $this->command->info("- Registros actualizados: {$actualizados}");
        $this->command->info("- Productos no encontrados: {$sinProducto}");
    }
}
