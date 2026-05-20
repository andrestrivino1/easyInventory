<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionStateLog extends Model
{
    protected $table = 'liquidacion_state_logs';

    public $timestamps = false;

    protected $fillable = [
        'liquidacion_id', 'user_id',
        'from_state', 'to_state',
        'motivo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
