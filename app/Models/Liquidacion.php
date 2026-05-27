<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Liquidacion extends Model
{
    use SoftDeletes;

    protected $table = 'liquidaciones';

    protected $fillable = [
        'driver_id', 'vehicle_plate', 'route_id',
        'transportadora', 'telefono_empresa',
        'anticipo', 'sobreanticipo',
        'fecha_inicio', 'fecha_fin',
        'numero_mfto', 'valor_flete',
        'estado', 'motivo_anulacion',
        'sumatoria_gastos_operativos', 'sumatoria_peajes', 'sumatoria_peajes_conductor', 'sumatoria_gastos_totales',
        'total_anticipos', 'saldo_viaje', 'ganancia_viaje', 'a_favor_de',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'anticipo' => 'integer',
        'sobreanticipo' => 'integer',
        'valor_flete' => 'integer',
        'sumatoria_gastos_operativos' => 'integer',
        'sumatoria_peajes' => 'integer',
        'sumatoria_peajes_conductor' => 'integer',
        'sumatoria_gastos_totales' => 'integer',
        'total_anticipos' => 'integer',
        'saldo_viaje' => 'integer',
        'ganancia_viaje' => 'integer',
    ];

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_CERRADA = 'cerrada';
    public const ESTADO_ANULADA = 'anulada';

    public const AFAVOR_EMPRESA = 'empresa';
    public const AFAVOR_CONDUCTOR = 'conductor';
    public const AFAVOR_NINGUNO = 'ninguno';

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(LiquidacionRoute::class, 'route_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(LiquidacionExpense::class);
    }

    public function tolls(): HasMany
    {
        return $this->hasMany(LiquidacionToll::class)->orderBy('sort_order');
    }

    public function stateLogs(): HasMany
    {
        return $this->hasMany(LiquidacionStateLog::class)->orderBy('created_at', 'desc');
    }

    public function scopeBorrador($query)
    {
        return $query->where('estado', self::ESTADO_BORRADOR);
    }

    public function scopeCerrada($query)
    {
        return $query->where('estado', self::ESTADO_CERRADA);
    }

    public function scopeAnulada($query)
    {
        return $query->where('estado', self::ESTADO_ANULADA);
    }

    /**
     * Liquidaciones que participan en sumatorias del consolidado:
     * excluye anuladas y soft-deleted.
     */
    public function scopeActivas($query)
    {
        return $query->where('estado', '!=', self::ESTADO_ANULADA)->whereNull('deleted_at');
    }

    public function isBorrador(): bool
    {
        return $this->estado === self::ESTADO_BORRADOR;
    }

    public function isCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }

    public function isAnulada(): bool
    {
        return $this->estado === self::ESTADO_ANULADA;
    }
}
