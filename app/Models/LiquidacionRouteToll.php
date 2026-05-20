<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionRouteToll extends Model
{
    protected $table = 'liquidacion_route_tolls';

    protected $fillable = ['route_id', 'name', 'suggested_value', 'sort_order', 'direction'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(LiquidacionRoute::class, 'route_id');
    }
}
