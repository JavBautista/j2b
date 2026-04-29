<?php

namespace App\Services\Rentas;

use App\Models\Product;
use App\Models\Rent;
use App\Models\RentConsignment;
use App\Models\RentConsignmentItem;
use App\Models\Shop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RentConsignmentService
{
    /**
     * Crear una consigna nueva.
     *
     * @param Rent $rent
     * @param array $items     [['product_id' => int, 'qty' => int, 'description' => ?string], ...]
     * @param array $data      ['delivery_date' => 'Y-m-d', 'notes' => ?string, 'received_by_name' => ?string, 'created_by_user_id' => ?int]
     * @return array           ['ok' => bool, 'message' => string, 'consignment' => ?RentConsignment]
     */
    public function crear(Rent $rent, array $items, array $data): array
    {
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Debe incluir al menos un producto.', 'consignment' => null];
        }

        foreach ($items as $i => $it) {
            if (empty($it['product_id']) || empty($it['qty']) || (int) $it['qty'] <= 0) {
                return ['ok' => false, 'message' => 'Item inválido en posición ' . ($i + 1) . ': product_id y qty > 0 son obligatorios.', 'consignment' => null];
            }
        }

        $shop = Shop::find($rent->shop_id);
        if (!$shop) {
            return ['ok' => false, 'message' => 'La renta no tiene tienda asociada.', 'consignment' => null];
        }

        $productIds = array_column($items, 'product_id');
        $productos = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $it) {
            $p = $productos->get($it['product_id']);
            if (!$p) {
                return ['ok' => false, 'message' => "Producto id={$it['product_id']} no encontrado.", 'consignment' => null];
            }
            if ((int) $p->stock < (int) $it['qty']) {
                return ['ok' => false, 'message' => "Stock insuficiente para '{$p->name}'. Disponible: {$p->stock}, requerido: {$it['qty']}.", 'consignment' => null];
            }
        }

        try {
            $consignment = DB::transaction(function () use ($rent, $shop, $items, $data, $productos) {
                $folio = $shop->siguienteFolioConsignacion();

                $cons = RentConsignment::create([
                    'shop_id' => $shop->id,
                    'rent_id' => $rent->id,
                    'folio' => $folio,
                    'delivery_date' => $data['delivery_date'] ?? now()->toDateString(),
                    'notes' => $data['notes'] ?? null,
                    'received_by_name' => $data['received_by_name'] ?? null,
                    'status' => RentConsignment::STATUS_VIGENTE,
                    'created_by_user_id' => $data['created_by_user_id'] ?? null,
                ]);

                foreach ($items as $it) {
                    $p = $productos->get($it['product_id']);
                    RentConsignmentItem::create([
                        'rent_consignment_id' => $cons->id,
                        'product_id' => $p->id,
                        'qty' => (int) $it['qty'],
                        'qty_returned' => 0,
                        'description' => $it['description'] ?? $p->name,
                    ]);

                    $p->stock = (int) $p->stock - (int) $it['qty'];
                    $p->save();
                }

                return $cons->load('items.product');
            });

            try {
                $pdfPath = $this->generarYGuardarPdf($consignment);
                $consignment->pdf_path = $pdfPath;
                $consignment->save();
            } catch (\Throwable $e) {
                Log::warning('Consigna creada pero PDF falló', ['consignment_id' => $consignment->id, 'error' => $e->getMessage()]);
            }

            return ['ok' => true, 'message' => 'Consigna creada.', 'consignment' => $consignment];
        } catch (\Throwable $e) {
            Log::error('Error al crear consigna', ['rent_id' => $rent->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Error al crear consigna: ' . $e->getMessage(), 'consignment' => null];
        }
    }

    /**
     * Cancelar una consigna con devolución parcial al inventario.
     *
     * @param RentConsignment $cons
     * @param array $devoluciones  [item_id => qty_a_devolver, ...]  (puede omitirse para no devolver nada)
     * @param string|null $motivo
     * @return array
     */
    public function cancelar(RentConsignment $cons, array $devoluciones, ?string $motivo): array
    {
        if (!$cons->estaVigente()) {
            return ['ok' => false, 'message' => 'La consigna ya está cancelada.'];
        }

        $cons->load('items');

        foreach ($devoluciones as $itemId => $qtyDev) {
            $qtyDev = (int) $qtyDev;
            if ($qtyDev < 0) {
                return ['ok' => false, 'message' => "Cantidad a devolver no puede ser negativa (item {$itemId})."];
            }
            $item = $cons->items->firstWhere('id', $itemId);
            if (!$item) {
                return ['ok' => false, 'message' => "Item id={$itemId} no pertenece a esta consigna."];
            }
            $maxDev = $item->qtyPendienteDevolucion();
            if ($qtyDev > $maxDev) {
                return ['ok' => false, 'message' => "No se puede devolver {$qtyDev} unidades del item '{$item->description}'. Máximo pendiente: {$maxDev}."];
            }
        }

        try {
            DB::transaction(function () use ($cons, $devoluciones, $motivo) {
                foreach ($devoluciones as $itemId => $qtyDev) {
                    $qtyDev = (int) $qtyDev;
                    if ($qtyDev <= 0) continue;

                    $item = $cons->items->firstWhere('id', $itemId);
                    $producto = Product::lockForUpdate()->find($item->product_id);
                    if ($producto) {
                        $producto->stock = (int) $producto->stock + $qtyDev;
                        $producto->save();
                    }

                    $item->qty_returned = (int) $item->qty_returned + $qtyDev;
                    $item->save();
                }

                $cons->status = RentConsignment::STATUS_CANCELADA;
                $cons->cancellation_reason = $motivo;
                $cons->cancelled_at = now();
                $cons->save();
            });

            return ['ok' => true, 'message' => 'Consigna cancelada.', 'consignment' => $cons->fresh('items.product')];
        } catch (\Throwable $e) {
            Log::error('Error al cancelar consigna', ['consignment_id' => $cons->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Error al cancelar: ' . $e->getMessage()];
        }
    }

    /**
     * Renderiza el PDF y devuelve el contenido binario.
     */
    public function generarPdf(RentConsignment $cons): string
    {
        $cons->loadMissing(['items.product', 'rent.client', 'shop', 'createdBy']);

        $logoBase64 = null;
        if ($cons->shop && $cons->shop->logo) {
            $logoPath = public_path('storage/' . $cons->shop->logo);
            if (file_exists($logoPath)) {
                try {
                    $logoBase64 = base64_encode(file_get_contents($logoPath));
                } catch (\Throwable $e) {
                    Log::warning('No se pudo cargar logo de shop para PDF consigna', ['shop_id' => $cons->shop_id, 'error' => $e->getMessage()]);
                }
            }
        }

        $pdf = Pdf::loadView('rent_consignment_pdf', [
            'consignment' => $cons,
            'shop' => $cons->shop,
            'rent' => $cons->rent,
            'client' => $cons->rent->client ?? null,
            'logoBase64' => $logoBase64,
            'createdByName' => $cons->createdBy->name ?? '',
        ])->setPaper('letter', 'portrait');

        return $pdf->output();
    }

    /**
     * Genera el PDF y lo guarda en el disco `consignments`. Retorna el path relativo.
     */
    public function generarYGuardarPdf(RentConsignment $cons): string
    {
        $content = $this->generarPdf($cons);
        $path = "{$cons->shop_id}/pdf/{$cons->folio}.pdf";
        Storage::disk('consignments')->put($path, $content);
        return $path;
    }

    /**
     * Guarda la foto del vale firmado y actualiza la consigna.
     */
    public function subirFirma(RentConsignment $cons, UploadedFile $foto): array
    {
        if (!$cons->estaVigente()) {
            return ['ok' => false, 'message' => 'No se puede firmar una consigna cancelada.'];
        }

        $ext = strtolower($foto->getClientOriginalExtension() ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
            return ['ok' => false, 'message' => 'Formato no válido. Use JPG o PNG.'];
        }

        try {
            $path = "{$cons->shop_id}/firmas/{$cons->folio}.{$ext}";
            Storage::disk('consignments')->put($path, file_get_contents($foto->getRealPath()));

            $cons->signature_path = $path;
            $cons->signed_at = now();
            $cons->save();

            return ['ok' => true, 'message' => 'Firma registrada.', 'consignment' => $cons->fresh()];
        } catch (\Throwable $e) {
            Log::error('Error al subir firma de consigna', ['consignment_id' => $cons->id, 'error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'Error al guardar firma: ' . $e->getMessage()];
        }
    }
}
