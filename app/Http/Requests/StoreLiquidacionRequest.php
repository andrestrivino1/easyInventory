<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLiquidacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Liquidacion::class) ?? false;
    }

    public function rules(): array
    {
        // El rol placas solo puede operar sobre sus conductores asignados.
        $user = $this->user();
        $driverRules = ['required', 'integer', Rule::exists('drivers', 'id')->where('active', 1)];
        if ($user && $user->isPlacas()) {
            $driverRules[] = Rule::in($user->assignedDriverIds());
        }

        return [
            'driver_id' => $driverRules,
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'route_id' => ['nullable', 'integer', Rule::exists('liquidacion_routes', 'id')->where('active', 1)],
            'transportadora' => ['required', 'string', 'max:150'],
            'telefono_empresa' => ['nullable', 'string', 'max:40'],
            'anticipo' => ['required', 'integer', 'min:0'],
            'sobreanticipo' => ['nullable', 'integer', 'min:0'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'numero_mfto' => ['nullable', 'string', 'max:60'],
            'valor_flete' => ['required', 'integer', 'min:0'],

            'expenses' => ['nullable', 'array'],
            'expenses.*.expense_category_id' => ['required_with:expenses', 'integer', 'exists:expense_categories,id'],
            'expenses.*.valor' => ['nullable', 'integer', 'min:0'],
            'expenses.*.galones' => ['nullable', 'numeric', 'min:0'],

            'tolls' => ['nullable', 'array'],
            'tolls.*.name' => ['required_with:tolls', 'string', 'max:100'],
            'tolls.*.valor' => ['nullable', 'integer', 'min:0'],
            'tolls.*.sort_order' => ['required_with:tolls', 'integer', 'min:1'],
            'tolls.*.direction' => ['required_with:tolls', Rule::in(['ida', 'regreso'])],
            'tolls.*.route_toll_id' => ['nullable', 'integer', 'exists:liquidacion_route_tolls,id'],
            'tolls.*.is_adhoc' => ['nullable', 'boolean'],
            'tolls.*.is_used' => ['nullable', 'boolean'],
            'tolls.*.paid_by' => ['nullable', Rule::in(['empresa', 'conductor'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sobreanticipo' => $this->input('sobreanticipo', 0),
        ]);
    }
}
