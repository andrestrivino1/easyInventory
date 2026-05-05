<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItrDateHistory extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'itr_id',
        'field_name',
        'old_value',
        'new_value',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function itr(): BelongsTo
    {
        return $this->belongsTo(Itr::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFieldLabelAttribute(): string
    {
        $labels = [
            'fecha_retiro_contenedor' => 'Fecha retiro contenedor',
            'fecha_vaciado_contenedor' => 'Fecha vaciado contenedor',
            'fecha_devolucion_contenedor' => 'Fecha devolución contenedor',
        ];
        return $labels[$this->field_name] ?? $this->field_name;
    }
}
