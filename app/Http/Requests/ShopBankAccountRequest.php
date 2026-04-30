<?php

namespace App\Http\Requests;

use App\Support\SatCatalogos\Bancos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShopBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $req      = $isUpdate ? 'sometimes' : 'required';

        return [
            'alias'          => [$req, 'string', 'max:80'],
            'bank_code'      => [$req, 'string', 'in:' . implode(',', Bancos::codes())],
            'bank_name'      => [$req, 'string', 'max:100'],
            'bank_rfc'       => [$req, 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
            'clabe'          => [$req, 'string', 'size:18', 'regex:/^\d{18}$/'],
            'account_number' => ['nullable', 'string', 'max:20'],
            'holder_name'    => [$req, 'string', 'max:150'],
            'is_default'     => ['sometimes', 'boolean'],
            'is_active'      => ['sometimes', 'boolean'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $clabe = (string) $this->input('clabe', '');
            if ($clabe !== '' && preg_match('/^\d{18}$/', $clabe) && !self::clabeValida($clabe)) {
                $v->errors()->add('clabe', 'La CLABE no es válida (dígito de control incorrecto).');
            }
        });
    }

    /**
     * Valida una CLABE interbancaria de 18 dígitos según el algoritmo oficial:
     * - Multiplicar cada dígito por su peso (3,7,1,3,7,1,...) para los primeros 17.
     * - Sumar los productos módulo 10.
     * - El dígito de control es (10 - (suma mod 10)) mod 10.
     * - Comparar contra el dígito 18.
     *
     * Referencia: norma BIM (Banca Información Mexicana) — algoritmo público.
     */
    public static function clabeValida(string $clabe): bool
    {
        if (!preg_match('/^\d{18}$/', $clabe)) {
            return false;
        }
        $pesos = [3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7, 1, 3, 7];
        $suma = 0;
        for ($i = 0; $i < 17; $i++) {
            $suma += ((int) $clabe[$i] * $pesos[$i]) % 10;
        }
        $controlCalculado = (10 - ($suma % 10)) % 10;
        $controlEsperado  = (int) $clabe[17];
        return $controlCalculado === $controlEsperado;
    }

    public function messages(): array
    {
        return [
            'clabe.size'      => 'La CLABE debe tener exactamente 18 dígitos.',
            'clabe.regex'     => 'La CLABE solo puede contener dígitos.',
            'bank_code.in'    => 'Banco no válido. Selecciona uno del catálogo.',
            'bank_rfc.regex'  => 'El RFC del banco no tiene el formato correcto.',
        ];
    }
}
