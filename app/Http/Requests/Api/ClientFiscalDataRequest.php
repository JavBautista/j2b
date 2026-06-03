<?php

namespace App\Http\Requests\Api;

use App\Services\Facturacion\SatCatalogService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ClientFiscalDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $req      = $isUpdate ? 'sometimes' : 'required';

        $regimenesVigentes = app(SatCatalogService::class)->regimenesVigentes();

        return [
            'rfc'            => [$req, 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
            'razon_social'   => [$req, 'string', 'max:255'],
            'regimen_fiscal' => [$req, 'string', Rule::in($regimenesVigentes)],
            'uso_cfdi'       => [$req, 'string', 'max:5'],
            'codigo_postal'  => [$req, 'string', 'size:5', 'regex:/^\d{5}$/'],
            'email'          => ['nullable', 'email', 'max:150'],
            'nickname'       => ['nullable', 'string', 'max:80'],
            'is_default'     => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $rfc = strtoupper((string) $this->input('rfc', ''));
            $reg = (string) $this->input('regimen_fiscal', '');
            $uso = (string) $this->input('uso_cfdi', '');

            if ($rfc === '' || $reg === '' || $uso === '') {
                return;
            }

            $catalogs = app(SatCatalogService::class);
            $esFisica = strlen($rfc) === 13;

            // Régimen válido para el tipo de persona (RFC 13 = física, 12 = moral).
            if (! in_array($reg, $catalogs->regimenesPorTipo($esFisica), true)) {
                $tipo = $esFisica ? 'fisica' : 'moral';
                $v->errors()->add('regimen_fiscal', "Regimen fiscal no valido para persona {$tipo}.");
            }

            // Uso CFDI compatible con el régimen (matriz SAT).
            $usosCompatibles = $catalogs->usosCompatibles($reg);
            if (! empty($usosCompatibles) && ! in_array($uso, $usosCompatibles, true)) {
                $v->errors()->add('uso_cfdi', "Uso CFDI {$uso} no compatible con regimen {$reg}.");
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rfc')) {
            $this->merge(['rfc' => strtoupper(trim((string) $this->input('rfc')))]);
        }
        if ($this->has('codigo_postal')) {
            $this->merge(['codigo_postal' => trim((string) $this->input('codigo_postal'))]);
        }
    }
}
