<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonthlyExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('liquidaciones.gastos.access') ?? false;
    }

    public function rules(): array
    {
        $gastoId = optional($this->route('gasto'))->id;

        $unique = Rule::unique('monthly_expenses')->where(
            fn ($q) => $q->where('anio', $this->input('anio'))->where('mes', $this->input('mes'))
        );
        if ($gastoId) {
            $unique->ignore($gastoId);
        }

        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id', $unique],
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['required', 'integer', 'min:1', 'max:12'],
            'sueldo_conductor' => ['required', 'integer', 'min:0'],
            'seguridad_social' => ['required', 'integer', 'min:0'],
            'cuota_banco' => ['required', 'integer', 'min:0'],
            'cuota_tercero' => ['required', 'integer', 'min:0'],
            'satelital' => ['required', 'integer', 'min:0'],
            'seguro_vehiculo' => ['required', 'integer', 'min:0'],
            'otro_valor' => ['required', 'integer', 'min:0'],
            'otro_descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.unique' => 'Ya existe un gasto mensual para ese conductor en el período (mes/año) seleccionado.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $money = [
            'sueldo_conductor', 'seguridad_social', 'cuota_banco', 'cuota_tercero',
            'satelital', 'seguro_vehiculo', 'otro_valor',
        ];
        $merge = [];
        foreach ($money as $field) {
            $val = $this->input($field);
            $merge[$field] = ($val === null || $val === '') ? 0 : $val;
        }
        $this->merge($merge);
    }
}
