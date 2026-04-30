<?php

namespace App\Support\SatCatalogos;

/**
 * Catálogo de bancos mexicanos comunes con su clave del catálogo c_Banco SAT
 * y su RFC oficial. Usado en el alta de cuentas bancarias de tienda y en la
 * captura del banco ordenante (cliente) al registrar abonos en complementos
 * de pago (Pagos 2.0).
 *
 * Solo se incluyen los más usados con RFC verificable de fuente pública.
 * El código '999' es la salida de escape "Otro / Extranjero" — el usuario
 * captura RFC libre. Si banco extranjero, usar XEXX010101000.
 *
 * Espejo en JS: resources/js/catalogos/bancos.js
 *
 * Última verificación: 2026-04-29 contra catalogosat.mx + portal.ucol.mx.
 */
class Bancos
{
    /**
     * @return array<int,array{code:string,name:string,rfc:string}>
     */
    public static function all(): array
    {
        return [
            ['code' => '002', 'name' => 'Banamex (Citibanamex)', 'rfc' => 'BNM840515VB1'],
            ['code' => '012', 'name' => 'BBVA México',           'rfc' => 'BBA830831LJ2'],
            ['code' => '014', 'name' => 'Santander',             'rfc' => 'BSM970519DU8'],
            ['code' => '021', 'name' => 'HSBC',                  'rfc' => 'HMI950125KG8'],
            ['code' => '044', 'name' => 'Scotiabank',            'rfc' => 'SIN9412025I4'],
            ['code' => '072', 'name' => 'Banorte',               'rfc' => 'BMN930209927'],
            ['code' => '999', 'name' => 'Otro / Extranjero',     'rfc' => ''],
        ];
    }

    /**
     * Devuelve el banco por código, o null si no existe.
     */
    public static function find(string $code): ?array
    {
        foreach (self::all() as $banco) {
            if ($banco['code'] === $code) {
                return $banco;
            }
        }
        return null;
    }

    /**
     * Lista de códigos válidos del catálogo.
     *
     * @return array<int,string>
     */
    public static function codes(): array
    {
        return array_column(self::all(), 'code');
    }
}
