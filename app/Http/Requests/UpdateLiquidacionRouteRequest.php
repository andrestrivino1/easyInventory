<?php

namespace App\Http\Requests;

class UpdateLiquidacionRouteRequest extends StoreLiquidacionRouteRequest
{
    public function authorize(): bool
    {
        $route = $this->route('route');
        return $this->user()?->can('update', $route) ?? false;
    }
}
