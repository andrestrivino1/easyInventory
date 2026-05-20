<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiquidacionRoute extends Model
{
    protected $table = 'liquidacion_routes';

    protected $fillable = ['origen', 'destino', 'name', 'descripcion', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function (LiquidacionRoute $route) {
            $origen = trim((string) $route->origen);
            $destino = trim((string) $route->destino);
            $route->name = $origen !== '' && $destino !== ''
                ? "{$origen} → {$destino}"
                : ($origen !== '' ? $origen : $destino);
        });
    }

    public function tolls(): HasMany
    {
        return $this->hasMany(LiquidacionRouteToll::class, 'route_id')->orderBy('sort_order');
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class, 'route_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
