<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiquidacionRoute extends Model
{
    protected $table = 'liquidacion_routes';

    protected $fillable = ['origen', 'destino', 'vehicle_type', 'name', 'descripcion', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public const VEHICLE_PATINETA = 'patineta';
    public const VEHICLE_DOBLETROQUE = 'dobletroque';
    public const VEHICLE_MULA = 'mula';

    public const VEHICLE_LABELS = [
        self::VEHICLE_PATINETA => 'Patineta',
        self::VEHICLE_DOBLETROQUE => 'Dobletroque',
        self::VEHICLE_MULA => 'Mula',
    ];

    public function vehicleTypeLabel(): string
    {
        return self::VEHICLE_LABELS[$this->vehicle_type] ?? '';
    }

    protected static function booted()
    {
        static::saving(function (LiquidacionRoute $route) {
            $origen = trim((string) $route->origen);
            $destino = trim((string) $route->destino);
            $base = $origen !== '' && $destino !== ''
                ? "{$origen} → {$destino}"
                : ($origen !== '' ? $origen : $destino);
            $typeLabel = self::VEHICLE_LABELS[$route->vehicle_type] ?? null;
            $route->name = $typeLabel
                ? "{$base} (" . strtoupper($typeLabel) . ")"
                : $base;
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
