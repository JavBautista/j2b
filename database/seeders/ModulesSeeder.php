<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de módulos (decisiones del usuario 2026-06-13).
 * IDEMPOTENTE (updateOrCreate por key): re-ejecutable para agregar módulos nuevos
 * sin duplicar ni pisar precios pactados (esos viven en shop_modules, no aquí).
 *
 * CORE (is_core=true): incluido siempre, no se vende suelto, no se gatea.
 * VENDIBLES (is_core=false): activables/cobrables por tienda vía shop_modules.
 */
class ModulesSeeder extends Seeder
{
    public function run()
    {
        $modulos = [
            // --- CORE (renta base $600 = el conjunto core) ---
            ['key' => 'clients',   'name' => 'Clientes',                  'is_core' => true,  'icon' => 'fa-users',          'sort_order' => 10],
            ['key' => 'sales',     'name' => 'Ventas',                    'is_core' => true,  'icon' => 'fa-file-text-o',    'sort_order' => 20],
            ['key' => 'purchases', 'name' => 'Compras',                   'is_core' => true,  'icon' => 'fa-shopping-cart',  'sort_order' => 30],
            ['key' => 'suppliers', 'name' => 'Proveedores',               'is_core' => true,  'icon' => 'fa-truck',          'sort_order' => 40],
            ['key' => 'catalogs',  'name' => 'Catálogos',                 'is_core' => true,  'icon' => 'fa-cubes',          'sort_order' => 50],
            ['key' => 'reports',   'name' => 'Reportes',                  'is_core' => true,  'icon' => 'fa-bar-chart',      'sort_order' => 60],
            ['key' => 'settings',  'name' => 'Configuraciones',           'is_core' => true,  'icon' => 'fa-cog',            'sort_order' => 70],

            // --- VENDIBLES (cargo adicional sobre el core; precio pactado por tienda) ---
            ['key' => 'cfdi',  'name' => 'Facturación CFDI',              'is_core' => false, 'icon' => 'fa-file-text',      'sort_order' => 100, 'requires' => null],
            ['key' => 'tasks', 'name' => 'Tareas / Órdenes de trabajo',   'is_core' => false, 'icon' => 'fa-tasks',          'sort_order' => 110, 'requires' => null],
            ['key' => 'gps',   'name' => 'Monitoreo GPS',                 'is_core' => false, 'icon' => 'fa-map-marker',     'sort_order' => 120, 'requires' => ['tasks']],
        ];

        foreach ($modulos as $m) {
            Module::updateOrCreate(
                ['key' => $m['key']],
                [
                    'name'         => $m['name'],
                    'description'  => $m['description']  ?? null,
                    'icon'         => $m['icon']         ?? null,
                    'is_core'      => $m['is_core'],
                    'base_price'   => $m['base_price']   ?? 0,
                    'billing_type' => $m['billing_type'] ?? 'flat',
                    'requires'     => $m['requires']     ?? null,
                    'is_external'  => $m['is_external']  ?? false,
                    'active'       => $m['active']       ?? true,
                    'sort_order'   => $m['sort_order'],
                ]
            );
        }
    }
}
