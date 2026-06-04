<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\SatFormaPago;
use App\Models\SatMetodoPago;
use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Services\Facturacion\SatCatalogService;
use Illuminate\Http\Request;

/**
 * Administración de catálogos SAT fiscales por superadmin.
 * Permite ver/activar-desactivar/editar y dar de alta claves SIN deploy.
 * Cada cambio invalida el cache del bundle.
 *
 * La matriz régimen→uso NO se edita aquí (cambia rarísimo) → vive en el seeder.
 */
class SatCatalogsController extends Controller
{
    private const TIPOS = [
        'regimenes'    => SatRegimenFiscal::class,
        'usos'         => SatUsoCfdi::class,
        'formas-pago'  => SatFormaPago::class,
        'metodos-pago' => SatMetodoPago::class,
    ];

    /** Todos los catálogos completos (incluye no-vigentes) para administrar. */
    public function data()
    {
        return response()->json([
            'regimenes'    => SatRegimenFiscal::orderBy('code')->get(),
            'usos'         => SatUsoCfdi::orderBy('code')->get(),
            'formas_pago'  => SatFormaPago::orderBy('code')->get(),
            'metodos_pago' => SatMetodoPago::orderBy('code')->get(),
        ]);
    }

    /** Actualiza un item existente. */
    public function update(Request $request, string $tipo, int $id)
    {
        $model = $this->resolveModel($tipo);
        $item  = $model::findOrFail($id);

        $request->validate(['description' => 'required|string|max:255']);
        $item->fill($this->camposEditables($request, $tipo));
        $item->save();

        app(SatCatalogService::class)->forget();

        return response()->json(['ok' => true, 'item' => $item]);
    }

    /** Alta de una clave nueva (o actualiza si el code ya existe). */
    public function store(Request $request, string $tipo)
    {
        $model = $this->resolveModel($tipo);

        $request->validate([
            'code'        => 'required|string|max:5',
            'description' => 'required|string|max:255',
        ]);

        $code = strtoupper(trim($request->input('code')));
        $item = $model::firstOrNew(['code' => $code]);
        $item->fill($this->camposEditables($request, $tipo));
        $item->save();

        app(SatCatalogService::class)->forget();

        return response()->json(['ok' => true, 'item' => $item]);
    }

    private function resolveModel(string $tipo): string
    {
        abort_unless(isset(self::TIPOS[$tipo]), 404, 'Catálogo no válido.');

        return self::TIPOS[$tipo];
    }

    /** Campos editables según el tipo de catálogo. */
    private function camposEditables(Request $r, string $tipo): array
    {
        $data = [
            'description' => $r->input('description'),
            'vigente'     => $r->boolean('vigente'),
        ];

        if (in_array($tipo, ['regimenes', 'usos'], true)) {
            $data['aplica_fisica'] = $r->boolean('aplica_fisica');
            $data['aplica_moral']  = $r->boolean('aplica_moral');
        }

        if ($tipo === 'regimenes') {
            $data['aplica_emisor'] = $r->boolean('aplica_emisor');
        }

        return $data;
    }
}
