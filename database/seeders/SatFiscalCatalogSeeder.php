<?php

namespace Database\Seeders;

use App\Models\SatRegimenFiscal;
use App\Models\SatUsoCfdi;
use App\Services\Facturacion\SatCatalogService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos SAT fiscales (CFDI 4.0) — fuente única de verdad.
 *
 * Fuente: Anexo 20 v4.0 — c_RegimenFiscal, c_UsoCFDI y matriz régimen→uso.
 * Idempotente: seguro de correr múltiples veces (updateOrCreate / updateOrInsert).
 *
 * La matriz régimen→uso es idéntica a la que estaba vigente en producción
 * (App\Http\Requests\Api\ClientFiscalDataRequest::matrizUsosCfdi()).
 */
class SatFiscalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedRegimenes();
        $this->seedUsos();
        $this->seedMatriz();

        // Invalidar el bundle cacheado para que el endpoint sirva los datos frescos.
        Cache::forget(SatCatalogService::CACHE_KEY);
    }

    /** c_RegimenFiscal: [code, description, aplica_fisica, aplica_moral] */
    private function seedRegimenes(): void
    {
        $regimenes = [
            ['601', 'General de Ley Personas Morales', false, true],
            ['603', 'Personas Morales con Fines no Lucrativos', false, true],
            ['605', 'Sueldos y Salarios e Ingresos Asimilados a Salarios', true, false],
            ['606', 'Arrendamiento', true, false],
            ['607', 'Régimen de Enajenación o Adquisición de Bienes', true, false],
            ['608', 'Demás ingresos', true, false],
            ['610', 'Residentes en el Extranjero sin Establecimiento Permanente en México', true, true],
            ['611', 'Ingresos por Dividendos (socios y accionistas)', true, false],
            ['612', 'Personas Físicas con Actividades Empresariales y Profesionales', true, false],
            ['614', 'Ingresos por intereses', true, false],
            ['615', 'Régimen de los ingresos por obtención de premios', true, false],
            ['616', 'Sin obligaciones fiscales', true, true],
            ['620', 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', false, true],
            ['621', 'Incorporación Fiscal', true, false],
            ['622', 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', false, true],
            ['623', 'Opcional para Grupos de Sociedades', false, true],
            ['624', 'Coordinados', false, true],
            ['625', 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', true, false],
            ['626', 'Régimen Simplificado de Confianza', true, true],
        ];

        foreach ($regimenes as [$code, $desc, $pf, $pm]) {
            SatRegimenFiscal::updateOrCreate(
                ['code' => $code],
                ['description' => $desc, 'aplica_fisica' => $pf, 'aplica_moral' => $pm, 'vigente' => true]
            );
        }
    }

    /** c_UsoCFDI: [code, description, aplica_fisica, aplica_moral]. Las deducciones personales D01–D10 son solo persona física. */
    private function seedUsos(): void
    {
        $usos = [
            ['G01', 'Adquisición de mercancías', true, true],
            ['G02', 'Devoluciones, descuentos o bonificaciones', true, true],
            ['G03', 'Gastos en general', true, true],
            ['I01', 'Construcciones', true, true],
            ['I02', 'Mobiliario y equipo de oficina por inversiones', true, true],
            ['I03', 'Equipo de transporte', true, true],
            ['I04', 'Equipo de cómputo y accesorios', true, true],
            ['I05', 'Dados, troqueles, moldes, matrices y herramental', true, true],
            ['I06', 'Comunicaciones telefónicas', true, true],
            ['I07', 'Comunicaciones satelitales', true, true],
            ['I08', 'Otra maquinaria y equipo', true, true],
            ['D01', 'Honorarios médicos, dentales y gastos hospitalarios', true, false],
            ['D02', 'Gastos médicos por incapacidad o discapacidad', true, false],
            ['D03', 'Gastos funerales', true, false],
            ['D04', 'Donativos', true, false],
            ['D05', 'Intereses reales pagados por créditos hipotecarios (casa habitación)', true, false],
            ['D06', 'Aportaciones voluntarias al SAR', true, false],
            ['D07', 'Primas por seguros de gastos médicos', true, false],
            ['D08', 'Gastos de transportación escolar obligatoria', true, false],
            ['D09', 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones', true, false],
            ['D10', 'Pagos por servicios educativos (colegiaturas)', true, false],
            ['S01', 'Sin efectos fiscales', true, true],
            ['CP01', 'Pagos', true, true],
        ];

        foreach ($usos as [$code, $desc, $pf, $pm]) {
            SatUsoCfdi::updateOrCreate(
                ['code' => $code],
                ['description' => $desc, 'aplica_fisica' => $pf, 'aplica_moral' => $pm, 'vigente' => true]
            );
        }
    }

    /** Matriz régimen→uso (vigente en producción). */
    private function seedMatriz(): void
    {
        $generalEmpresa = ['G01', 'G02', 'G03', 'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08', 'D10', 'S01', 'CP01'];
        $generalFisicaCompleta = ['G01', 'G02', 'G03', 'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08', 'D01', 'D02', 'D03', 'D04', 'D05', 'D06', 'D07', 'D08', 'D09', 'D10', 'S01', 'CP01'];
        $residenteExtranjero = ['G01', 'G02', 'G03', 'I01', 'I02', 'I03', 'I04', 'I05', 'I06', 'I07', 'I08', 'S01', 'CP01'];
        $soloPagosNoEfectos = ['CP01', 'S01'];

        $matriz = [
            '601' => $generalEmpresa,
            '603' => $generalEmpresa,
            '605' => $generalFisicaCompleta,
            '606' => $generalFisicaCompleta,
            '607' => $soloPagosNoEfectos,
            '608' => $generalFisicaCompleta,
            '610' => $residenteExtranjero,
            '611' => $soloPagosNoEfectos,
            '612' => $generalFisicaCompleta,
            '614' => $soloPagosNoEfectos,
            '615' => $soloPagosNoEfectos,
            '616' => $soloPagosNoEfectos,
            '620' => $generalEmpresa,
            '621' => $generalFisicaCompleta,
            '622' => $generalEmpresa,
            '623' => $generalEmpresa,
            '624' => $soloPagosNoEfectos,
            '625' => $generalFisicaCompleta,
            '626' => $generalFisicaCompleta,
        ];

        foreach ($matriz as $regimen => $usos) {
            foreach ($usos as $uso) {
                DB::table('sat_regimen_uso')->updateOrInsert(
                    ['regimen_code' => $regimen, 'uso_code' => $uso],
                    []
                );
            }
        }
    }
}
