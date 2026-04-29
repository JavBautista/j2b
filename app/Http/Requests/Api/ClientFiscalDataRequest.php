<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
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

        return [
            'rfc'            => [$req, 'string', 'regex:/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/i'],
            'razon_social'   => [$req, 'string', 'max:255'],
            'regimen_fiscal' => [$req, 'string', 'in:601,603,605,606,607,608,610,611,612,614,615,616,620,621,622,623,624,625,626'],
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

            $regimenesFisica = ['605','606','607','608','610','611','612','614','615','621','625','626','616'];
            $regimenesMoral  = ['601','603','620','622','623','624','616'];

            $esFisica = strlen($rfc) === 13;

            if ($esFisica && !in_array($reg, $regimenesFisica, true)) {
                $v->errors()->add('regimen_fiscal', 'Regimen fiscal no valido para persona fisica.');
            }
            if (!$esFisica && !in_array($reg, $regimenesMoral, true)) {
                $v->errors()->add('regimen_fiscal', 'Regimen fiscal no valido para persona moral.');
            }

            $matriz = self::matrizUsosCfdi();
            if (isset($matriz[$reg]) && !in_array($uso, $matriz[$reg], true)) {
                $v->errors()->add('uso_cfdi', "Uso CFDI {$uso} no compatible con regimen {$reg}.");
            }
        });
    }

    /**
     * Matriz oficial SAT CFDI 4.0: usos compatibles por regimen del receptor.
     * Fuente: SAT - https://www.sat.gob.mx/consultas/82066
     * Sincronizar con frontend Ionic si SAT publica cambios.
     */
    public static function matrizUsosCfdi(): array
    {
        $generalEmpresa = ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D10','S01','CP01'];
        $generalFisicaCompleta = ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','S01','CP01'];
        $soloPagosNoEfectos = ['CP01','S01'];

        return [
            '601' => $generalEmpresa,
            '603' => $generalEmpresa,
            '605' => $generalFisicaCompleta,
            '606' => $generalFisicaCompleta,
            '607' => $soloPagosNoEfectos,
            '608' => $generalFisicaCompleta,
            '610' => ['G01','G02','G03','I01','I02','I03','I04','I05','I06','I07','I08','S01','CP01'],
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
