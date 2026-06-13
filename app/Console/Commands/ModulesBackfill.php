<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de shop_modules para tiendas existentes (grandfathering).
 * Deja a cada tienda con los módulos vendibles que YA usa hoy, para que el día
 * que se active el gating duro (Fase 4) ninguna tienda pierda acceso.
 *
 * Criterio:
 *  - tasks, gps  → TODAS las tiendas (hoy no están gateados, todas los usan)
 *  - cfdi        → solo tiendas con shops.cfdi_enabled = true
 *
 * Idempotente: re-ejecutable. Verifica existencia + el UNIQUE(shop_id,module_id) blinda.
 */
class ModulesBackfill extends Command
{
    protected $signature = 'modules:backfill {--dry-run : Solo reporta, no escribe nada}';

    protected $description = 'Backfill de shop_modules para tiendas existentes (grandfathering)';

    public function handle()
    {
        $dry = (bool) $this->option('dry-run');
        $pref = $dry ? '[DRY-RUN] ' : '';

        $tasks = Module::where('key', 'tasks')->first();
        $gps   = Module::where('key', 'gps')->first();
        $cfdi  = Module::where('key', 'cfdi')->first();

        if (!$tasks || !$gps || !$cfdi) {
            $this->error('Faltan módulos en el catálogo. Corre primero: php artisan db:seed --class=ModulesSeeder');
            return self::FAILURE;
        }

        $shops = Shop::all();
        $this->info($pref . 'Tiendas a procesar: ' . $shops->count());

        $creados = 0;
        $existentes = 0;

        foreach ($shops as $shop) {
            $asignar = [$tasks->id, $gps->id];           // grandfathering: todas usan tareas + gps hoy
            if ($shop->cfdi_enabled) {
                $asignar[] = $cfdi->id;                   // cfdi solo si ya tenía facturación
            }

            foreach ($asignar as $moduleId) {
                $existe = DB::table('shop_modules')
                    ->where('shop_id', $shop->id)
                    ->where('module_id', $moduleId)
                    ->exists();

                if ($existe) {
                    $existentes++;
                    continue;
                }

                $creados++;
                if (!$dry) {
                    DB::table('shop_modules')->insert([
                        'shop_id'             => $shop->id,
                        'module_id'           => $moduleId,
                        'enabled'             => true,
                        'price'               => null,
                        'contracted_at'       => now(),
                        'expires_at'          => null,
                        'assigned_by_user_id' => null,
                        'notes'               => 'backfill grandfathering',
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ]);
                }
            }
        }

        $this->info($pref . "Asignaciones nuevas: {$creados} | ya existían: {$existentes}");
        if ($dry) {
            $this->warn('Dry-run: no se escribió nada. Quita --dry-run para aplicar.');
        }

        return self::SUCCESS;
    }
}
