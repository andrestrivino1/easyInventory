<?php

namespace App\Http\Requests;

class UpdateLiquidacionRequest extends StoreLiquidacionRequest
{
    public function authorize(): bool
    {
        $liquidacion = $this->route('liquidacion');
        return $this->user()?->can('update', $liquidacion) ?? false;
    }
}
