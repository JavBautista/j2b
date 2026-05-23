<?php

namespace App\Support;

class NumeroALetras
{
    public static function convertir(float $numero, string $moneda = 'PESOS'): string
    {
        $entero = (int) floor($numero);
        $centavos = (int) round(($numero - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = [11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
                       16 => 'DIECISEIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
                     'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convertirGrupo = function (int $n) use ($unidades, $decenas, $especiales, $centenas): string {
            if ($n === 0) return '';
            if ($n === 100) return 'CIEN';

            $resultado = '';
            if ($n >= 100) {
                $resultado .= $centenas[(int) floor($n / 100)] . ' ';
                $n %= 100;
            }
            if ($n >= 11 && $n <= 19) {
                $resultado .= $especiales[$n];
                return trim($resultado);
            }
            if ($n >= 21 && $n <= 29) {
                $resultado .= 'VEINTI' . $unidades[$n - 20];
                return trim($resultado);
            }
            if ($n >= 10) {
                $resultado .= $decenas[(int) floor($n / 10)];
                $n %= 10;
                if ($n > 0) $resultado .= ' Y ';
            }
            if ($n > 0) {
                $resultado .= $unidades[$n];
            }
            return trim($resultado);
        };

        if ($entero === 0) {
            $texto = 'CERO';
        } elseif ($entero === 1) {
            $texto = 'UN';
        } else {
            $texto = '';
            if ($entero >= 1000000) {
                $millones = (int) floor($entero / 1000000);
                $texto .= ($millones === 1 ? 'UN MILLON' : $convertirGrupo($millones) . ' MILLONES') . ' ';
                $entero %= 1000000;
            }
            if ($entero >= 1000) {
                $miles = (int) floor($entero / 1000);
                $texto .= ($miles === 1 ? 'MIL' : $convertirGrupo($miles) . ' MIL') . ' ';
                $entero %= 1000;
            }
            if ($entero > 0) {
                $texto .= $convertirGrupo($entero);
            }
            $texto = trim($texto);
        }

        return $texto . ' ' . $moneda . ' ' . str_pad($centavos, 2, '0', STR_PAD_LEFT) . '/100';
    }
}
