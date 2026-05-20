<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLiquidacionRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\LiquidacionRoute::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'origen' => ['required', 'string', 'max:100'],
            'destino' => ['required', 'string', 'max:100'],
            'vehicle_type' => ['required', Rule::in(array_keys(\App\Models\LiquidacionRoute::VEHICLE_LABELS))],
            'descripcion' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
            'tolls' => ['nullable', 'array'],
            'tolls.*.name' => ['required_with:tolls', 'string', 'max:100'],
            'tolls.*.suggested_value' => ['nullable', 'integer', 'min:0'],
            'tolls.*.sort_order' => ['required_with:tolls', 'integer', 'min:1'],
            'tolls.*.direction' => ['required_with:tolls', Rule::in(['ida', 'regreso'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active', true),
        ]);
    }
}
