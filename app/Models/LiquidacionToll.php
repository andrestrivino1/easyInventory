<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionToll extends Model
{
    protected $table = 'liquidacion_tolls';

    public const PAGA_EMPRESA = 'empresa';
    public const PAGA_CONDUCTOR = 'conductor';

    protected $fillable = [
        'liquidacion_id', 'route_toll_id',
        'name', 'valor', 'sort_order', 'direction',
        'is_adhoc', 'is_used', 'paid_by',
    ];

    protected $casts = [
        'valor' => 'integer',
        'is_adhoc' => 'boolean',
        'is_used' => 'boolean',
    ];

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function routeToll(): BelongsTo
    {
        return $this->belongsTo(LiquidacionRouteToll::class, 'route_toll_id');
    }

    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }
}
