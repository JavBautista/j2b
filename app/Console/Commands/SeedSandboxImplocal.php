<?php

namespace App\Console\Commands;

use App\Models\CfdiEmisor;
use App\Models\ClientFiscalData;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\ReceiptDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedSandboxImplocal extends Command
{
    protected $signature = 'cfdi:seed-sandbox-implocal
                            {--shop=26 : shop_id con emisor CFDI activo}
                            {--client-fiscal-data=6 : client_fiscal_data_id receptor sandbox (RFC validado en padrón SAT TBT)}
                            {--reset : borra receipts previos del seeder antes de recrearlos}';

    protected $description = 'Crea los 4 receipts sandbox del PLAN_IMPLOCAL_LEVANTAR_LIMITACIONES §4 listos para timbrar desde admin.';

    private const TAG = '[SANDBOX-IMPLOCAL]';

    public function handle(): int
    {
        $shopId = (int) $this->option('shop');
        $cfdId = (int) $this->option('client-fiscal-data');

        $cfd = ClientFiscalData::with('client')->find($cfdId);
        if (!$cfd || $cfd->client->shop_id !== $shopId) {
            $this->error("client_fiscal_data_id={$cfdId} no existe o no pertenece a shop={$shopId}");
            return self::FAILURE;
        }
        $emisor = CfdiEmisor::where('shop_id', $shopId)->where('active', 1)->first();
        if (!$emisor) {
            $this->error("Shop {$shopId} no tiene emisor CFDI activo.");
            return self::FAILURE;
        }
        $createdBy = DB::table('users')->where('shop_id', $shopId)->orderBy('id')->value('id');
        if (!$createdBy) {
            $this->error("Shop {$shopId} no tiene usuarios.");
            return self::FAILURE;
        }

        $this->info("Emisor: {$emisor->razon_social} ({$emisor->rfc}) régimen {$emisor->regimen_fiscal}");
        $this->info("Receptor: {$cfd->razon_social} ({$cfd->rfc}) régimen {$cfd->regimen_fiscal} uso {$cfd->uso_cfdi}");

        if ($this->option('reset')) {
            $this->resetReceipts($shopId);
        }

        $arr = $this->ensureProduct($shopId, 'SANDBOX-IMPLOCAL-ARR', 'Arrendamiento (sandbox implocal)', '78101803', 'E48');
        $hosp = $this->ensureProduct($shopId, 'SANDBOX-IMPLOCAL-HOSP', 'Hospedaje (sandbox implocal)', '90111501', 'E48');
        $this->info("Producto arrendamiento: id={$arr->id}");
        $this->info("Producto hospedaje: id={$hosp->id}");

        DB::transaction(function () use ($shopId, $cfd, $createdBy, $arr, $hosp) {
            $this->seedCaso1($shopId, $cfd->client_id, $createdBy, $arr);
            $this->seedCaso2($shopId, $cfd->client_id, $createdBy, $arr);
            $this->seedCaso3($shopId, $cfd->client_id, $createdBy, $hosp);
            $this->seedCaso4($shopId, $cfd->client_id, $createdBy, $arr);
        });

        $this->newLine();
        $this->info('Listo. Receipts creados, entra a admin → Notas y timbra cada uno:');
        $this->table(['Caso', 'Receipt ID', 'Descripción', 'Particularidades del modal CFDI'], [
            [1, $this->receiptId($shopId, 1), 'PUE + cedular + ISR/IVA', 'cfd default Peplitos, marcar ret_isr+ret_iva, agregar implocal: CEDULAR retención 5% base 10000 importe 500'],
            [2, $this->receiptId($shopId, 2), 'PPD + cedular + ISR/IVA + abono', 'igual a Caso 1; tras timbrar registrar abono $4,516.67'],
            [3, $this->receiptId($shopId, 3), 'PUE + ISH + descuento global', 'desmarcar retenciones federales; agregar implocal: ISH traslado 3% base 900 importe 27'],
            [4, $this->receiptId($shopId, 4), 'PUE + cedular + cortesía', 'detalle B es cortesía (no aparece en CFDI); implocal: CEDULAR 5% base 500 importe 25'],
        ]);

        return self::SUCCESS;
    }

    private function ensureProduct(int $shopId, string $sku, string $name, string $satProd, string $satUnit): Product
    {
        $categoryId = DB::table('categories')->where('shop_id', $shopId)->orderBy('id')->value('id');
        if (!$categoryId) {
            throw new \RuntimeException("Shop {$shopId} no tiene categorías; crea al menos una antes de correr el seeder.");
        }
        return Product::firstOrCreate(
            ['shop_id' => $shopId, 'key' => $sku],
            [
                'category_id' => $categoryId,
                'name' => $name,
                'description' => $name,
                'cost' => 0,
                'retail' => 0,
                'wholesale' => 0,
                'wholesale_premium' => 0,
                'stock' => 9999,
                'reserve' => 0,
                'active' => 1,
                'sat_product_code' => $satProd,
                'sat_unit_code' => $satUnit,
                'aplica_retencion_default' => 1,
            ]
        );
    }

    private function resetReceipts(int $shopId): void
    {
        $ids = DB::table('receipts')
            ->where('shop_id', $shopId)
            ->where('description', 'like', self::TAG.'%')
            ->where('is_tax_invoiced', 0)
            ->pluck('id');
        if ($ids->isEmpty()) return;
        DB::table('receipt_details')->whereIn('receipt_id', $ids)->delete();
        DB::table('receipts')->whereIn('id', $ids)->delete();
        $this->warn("Reset: eliminados {$ids->count()} receipts previos del seeder.");
    }

    private function receiptId(int $shopId, int $caso): ?int
    {
        return DB::table('receipts')
            ->where('shop_id', $shopId)
            ->where('description', 'like', self::TAG." CASO {$caso}%")
            ->orderBy('id', 'desc')
            ->value('id');
    }

    /** Caso 1 — PUE + cedular 5% + retención ISR 10% + retención IVA 10.6667% */
    private function seedCaso1(int $shopId, int $clientId, int $userId, Product $arr): void
    {
        $r = Receipt::create([
            'shop_id' => $shopId,
            'client_id' => $clientId,
            'created_by' => $userId,
            'type' => 'venta',
            'description' => self::TAG.' CASO 1 — PUE+cedular+ISR/IVA',
            'observation' => 'subtotal 10,000 + IVA 1,600 = 11,600 pagado; cedular 5% sobre 10,000 + ret ISR 10% + ret IVA 10.6667%',
            'status' => 'PAGADA',
            'payment' => 'TRANSFERENCIA',
            'subtotal' => 11600,
            'discount' => 0,
            'discount_concept' => null,
            'received' => 11600,
            'iva' => 0,
            'total' => 11600,
            'is_tax_invoiced' => 0,
            'origin' => 'ADMIN',
        ]);
        $this->addDetail($r->id, $arr, 1, 11600, 'Arrendamiento mensual oficina (Caso 1 sandbox)');
    }

    /** Caso 2 — PPD + cedular 5% + ISR/IVA + abono parcial */
    private function seedCaso2(int $shopId, int $clientId, int $userId, Product $arr): void
    {
        $r = Receipt::create([
            'shop_id' => $shopId,
            'client_id' => $clientId,
            'created_by' => $userId,
            'type' => 'venta',
            'description' => self::TAG.' CASO 2 — PPD+cedular+ISR/IVA+abono',
            'observation' => 'received=0 → PPD; tras timbrar registrar abono parcial $4,516.67 para forzar complemento de pago',
            'status' => 'POR COBRAR',
            'payment' => 'EFECTIVO',
            'subtotal' => 11600,
            'discount' => 0,
            'discount_concept' => null,
            'received' => 0,
            'iva' => 0,
            'total' => 11600,
            'is_tax_invoiced' => 0,
            'origin' => 'ADMIN',
        ]);
        $this->addDetail($r->id, $arr, 1, 11600, 'Arrendamiento mensual oficina (Caso 2 sandbox PPD)');
    }

    /** Caso 3 — PUE + ISH 3% + descuento global 10% */
    private function seedCaso3(int $shopId, int $clientId, int $userId, Product $hosp): void
    {
        $r = Receipt::create([
            'shop_id' => $shopId,
            'client_id' => $clientId,
            'created_by' => $userId,
            'type' => 'venta',
            'description' => self::TAG.' CASO 3 — PUE+ISH+descuento',
            'observation' => 'hospedaje 1,160 con descuento global $116; ISH traslado 3% sobre base 900 = 27; total 1,071',
            'status' => 'PAGADA',
            'payment' => 'TARJETA',
            'subtotal' => 1160,
            'discount' => 116,
            'discount_concept' => '$',
            'received' => 1044,
            'iva' => 0,
            'total' => 1044,
            'is_tax_invoiced' => 0,
            'origin' => 'ADMIN',
        ]);
        $this->addDetail($r->id, $hosp, 1, 1160, 'Hospedaje 1 noche habitación estándar (Caso 3 sandbox)');
    }

    /** Caso 4 — PUE + 1 facturable + 1 cortesía + cedular 5% */
    private function seedCaso4(int $shopId, int $clientId, int $userId, Product $arr): void
    {
        $r = Receipt::create([
            'shop_id' => $shopId,
            'client_id' => $clientId,
            'created_by' => $userId,
            'type' => 'venta',
            'description' => self::TAG.' CASO 4 — PUE+cedular+cortesía',
            'observation' => 'detalle A facturable $580 + detalle B cortesía $200 (no entra al CFDI); cedular 5% sobre base 500 = 25',
            'status' => 'PAGADA',
            'payment' => 'EFECTIVO',
            'subtotal' => 780,
            'discount' => 0,
            'discount_concept' => null,
            'received' => 780,
            'iva' => 0,
            'total' => 780,
            'is_tax_invoiced' => 0,
            'origin' => 'ADMIN',
        ]);
        $this->addDetail($r->id, $arr, 1, 580, 'Concepto facturable (Caso 4 sandbox)', false);
        $this->addDetail($r->id, $arr, 1, 200, 'Concepto cortesía — no debe entrar al CFDI', true);
    }

    private function addDetail(int $receiptId, Product $product, int $qty, float $price, string $descripcion, bool $cortesia = false): void
    {
        ReceiptDetail::create([
            'receipt_id' => $receiptId,
            'product_id' => $product->id,
            'type' => 'product',
            'descripcion' => $descripcion,
            'qty' => $qty,
            'price' => $price,
            'cost' => 0,
            'subtotal' => $qty * $price,
            'discount_concept' => null,
            'is_complimentary' => $cortesia ? 1 : 0,
            'discount' => 0,
        ]);
    }
}
