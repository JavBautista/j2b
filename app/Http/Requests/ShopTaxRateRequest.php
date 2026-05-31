<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShopTaxRateRequest extends FormRequest
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
            'name'       => [$req, 'string', 'max:30'],
            'rate'       => [$req, 'numeric', 'min:0', 'max:99.99'],
            'is_default' => ['sometimes', 'boolean'],
            'active'     => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la tasa es obligatorio.',
            'name.max'      => 'El nombre no puede superar 30 caracteres.',
            'rate.required' => 'La tasa es obligatoria.',
            'rate.numeric'  => 'La tasa debe ser un número.',
            'rate.min'      => 'La tasa no puede ser negativa.',
            'rate.max'      => 'La tasa no puede superar 99.99%.',
        ];
    }
}
