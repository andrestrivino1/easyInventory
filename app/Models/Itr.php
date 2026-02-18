<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Itr extends Model
{
    protected $fillable = [
        'import_id',
        'do_code',
        'bl_number',
        'fecha_llegada',
        'dias_libres',
        'fecha_vencimiento',
        'fecha_retiro_contenedor',
        'fecha_vaciado_contenedor',
        'fecha_devolucion_contenedor',
        'evidencia_tiquete_retiro_pdf',
        'evidencia_tiquete_devolucion_pdf',
        'evidencia_fotos_pdf',
    ];

    protected $casts = [
        'fecha_llegada' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_retiro_contenedor' => 'date',
        'fecha_vaciado_contenedor' => 'date',
        'fecha_devolucion_contenedor' => 'date',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function dateHistories(): HasMany
    {
        return $this->hasMany(ItrDateHistory::class)->orderByDesc('created_at');
    }

    /** True si faltan 2 días o menos para la fecha de vencimiento */
    public function isApproachingDueDate(): bool
    {
        if (!$this->fecha_vencimiento) {
            return false;
        }
        $vencimiento = $this->fecha_vencimiento->startOfDay();
        $today = now()->startOfDay();
        $daysLeft = $today->diffInDays($vencimiento, false);
        return $daysLeft >= 0 && $daysLeft <= 2;
    }

    /** True si la fecha de vencimiento ya pasó */
    public function isOverdue(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }
}
