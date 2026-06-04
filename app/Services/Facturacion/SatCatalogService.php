<?php

namespace App\Services\Facturacion;

use App\Models\SatFormaPago;
use App\Models\SatMetodoPago;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Fuente única de los catálogos SAT fiscales (CFDI 4.0).
 * Lo consumen: el endpoint web/API (bundle) y la validación de perfiles fiscales.
 * Los catálogos viven en BD (sat_regimenes_fiscales, sat_usos_cfdi, sat_regimen_uso);
 * el seeder es la fuente inicial y el superadmin los administra (Fase 3).
 */
class SatCatalogService
{
    public const CACHE_KEY = 'sat.fiscal_catalogs.v2';

    /** Bundle completo para web/Ionic: { regimenes, usos, matriz, formas_pago, metodos_pago }. Cacheado 24h. */
    public function bundle(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(24), function () {
            $regimenes = SatRegimenFiscal::where('vigente', true)
                ->orderBy('code')
                ->get()
                ->map(fn ($r) => [
                    'clave'         => $r->code,
                    'nombre'        => $r->description,
                    'aplica_fisica' => $r->aplica_fisica,
                    'aplica_moral'  => $r->aplica_moral,
                    'aplica_emisor' => $r->aplica_emisor,
                ])->values();

            $usos = SatUsoCfdi::where('vigente', true)
                ->orderBy('code')
                ->get()
                ->map(fn ($u) => [
                    'clave'         => $u->code,
                    'nombre'        => $u->description,
                    'aplica_fisica' => $u->aplica_fisica,
                    'aplica_moral'  => $u->aplica_moral,
                ])->values();

            $matriz = DB::table('sat_regimen_uso')
                ->orderBy('regimen_code')
                ->orderBy('uso_code')
                ->get()
                ->groupBy('regimen_code')
                ->map(fn ($rows) => $rows->pluck('uso_code')->values());

            $formasPago = SatFormaPago::where('vigente', true)
                ->orderBy('code')
                ->get()
                ->map(fn ($f) => ['clave' => $f->code, 'nombre' => $f->description])
                ->values();

            $metodosPago = SatMetodoPago::where('vigente', true)
                ->orderBy('code')
                ->get()
                ->map(fn ($m) => ['clave' => $m->code, 'nombre' => $m->description])
                ->values();

            return [
                'regimenes'    => $regimenes,
                'usos'         => $usos,
                'matriz'       => $matriz,
                'formas_pago'  => $formasPago,
                'metodos_pago' => $metodosPago,
            ];
        });
    }

    /** Todos los códigos de régimen vigentes (para la regla in:). */
    public function regimenesVigentes(): array
    {
        return SatRegimenFiscal::where('vigente', true)->pluck('code')->all();
    }

    /** Códigos de régimen vigentes que aplican al tipo de persona. */
    public function regimenesPorTipo(bool $esFisica): array
    {
        $col = $esFisica ? 'aplica_fisica' : 'aplica_moral';

        return SatRegimenFiscal::where('vigente', true)->where($col, true)->pluck('code')->all();
    }

    /** Códigos de régimen vigentes que un emisor (la tienda que factura) puede tener. */
    public function regimenesEmisor(): array
    {
        return SatRegimenFiscal::where('vigente', true)->where('aplica_emisor', true)->pluck('code')->all();
    }

    /** Usos CFDI compatibles con un régimen según la matriz SAT. */
    public function usosCompatibles(string $regimen): array
    {
        return DB::table('sat_regimen_uso')->where('regimen_code', $regimen)->pluck('uso_code')->all();
    }

    /** Nombre oficial del régimen (o el código si no se encuentra). */
    public function nombreRegimen(string $code): string
    {
        return SatRegimenFiscal::where('code', $code)->value('description') ?? $code;
    }

    /** Invalida el bundle cacheado (tras re-seed o edición superadmin). */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
