<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyExpense extends Model
{
    protected $table = 'monthly_expenses';

    protected $fillable = [
        'driver_id', 'vehicle_plate', 'anio', 'mes',
        'sueldo_conductor', 'seguridad_social', 'cuota_banco', 'cuota_tercero',
        'satelital', 'seguro_vehiculo', 'otro_valor', 'otro_descripcion',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'anio' => 'integer',
        'mes' => 'integer',
        'sueldo_conductor' => 'integer',
        'seguridad_social' => 'integer',
        'cuota_banco' => 'integer',
        'cuota_tercero' => 'integer',
        'satelital' => 'integer',
        'seguro_vehiculo' => 'integer',
        'otro_valor' => 'integer',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** Suma de los 7 conceptos. */
    public function getTotalAttribute(): int
    {
        return (int) $this->sueldo_conductor
            + (int) $this->seguridad_social
            + (int) $this->cuota_banco
            + (int) $this->cuota_tercero
            + (int) $this->satelital
            + (int) $this->seguro_vehiculo
            + (int) $this->otro_valor;
    }

    public function scopePlaca($query, ?string $placa)
    {
        return $query->where('vehicle_plate', $placa);
    }

    public function scopePeriodo($query, int $anio, int $mes)
    {
        return $query->where('anio', $anio)->where('mes', $mes);
    }
}
