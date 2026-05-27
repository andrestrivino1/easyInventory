<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveMonthlyExpenseYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('liquidaciones.gastos.access') ?? false;
    }

    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'meses' => ['nullable', 'array'],
            'meses.*.registrar' => ['nullable'],
            'meses.*.sueldo_conductor' => ['nullable', 'integer', 'min:0'],
            'meses.*.seguridad_social' => ['nullable', 'integer', 'min:0'],
            'meses.*.cuota_banco' => ['nullable', 'integer', 'min:0'],
            'meses.*.cuota_tercero' => ['nullable', 'integer', 'min:0'],
            'meses.*.satelital' => ['nullable', 'integer', 'min:0'],
            'meses.*.seguro_vehiculo' => ['nullable', 'integer', 'min:0'],
            'meses.*.otro_valor' => ['nullable', 'integer', 'min:0'],
            'meses.*.otro_descripcion' => ['nullable', 'string', 'max:150'],
        ];
    }
}
