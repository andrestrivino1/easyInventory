<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'medidas',
        'calibre',
        'alto',
        'ancho',
        'peso_empaque',
        'weight_per_box',
        'precio',
        'stock',
        'estado',
        'almacen_id',
        'tipo_medida',
        'unidades_por_caja',
    ];

    protected $casts = [
        'calibre' => 'decimal:2',
        'alto' => 'decimal:2',
        'ancho' => 'decimal:2',
        'peso_empaque' => 'decimal:2',
        'weight_per_box' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->codigo)) {
                $last = self::orderByDesc('id')->first();
                $next = $last ? ((int) substr($last->codigo, 4)) + 1 : 1;
                $model->codigo = 'PRD-' . str_pad($next, 6, '0', STR_PAD_LEFT);
            }
            $model->recalculateWeightPerBox();
        });
        static::updating(function ($model) {
            $model->recalculateWeightPerBox();
        });
    }

    /**
     * Peso por caja (automático): Calibre * (alto/100) * (ancho/100) * peso_empaque * cantidad_láminas * cantidad_cajas.
     * Alto y ancho se guardan en cm (ej. 183, 330) y se convierten a metros (1.83, 3.30) en la fórmula.
     */
    public function recalculateWeightPerBox(): void
    {
        $c = $this->calibre !== null && $this->calibre > 0;
        $a = $this->alto !== null && $this->alto > 0;
        $an = $this->ancho !== null && $this->ancho > 0;
        $e = $this->peso_empaque !== null && $this->peso_empaque > 0;
        $lam = $this->unidades_por_caja !== null && $this->unidades_por_caja > 0 ? (float) $this->unidades_por_caja : 1;
        $cajas = 1;

        if ($c && $a && $an && $e) {
            $altoMetros = (float) $this->alto / 100;
            $anchoMetros = (float) $this->ancho / 100;
            $this->weight_per_box = round((float) $this->calibre * $altoMetros * $anchoMetros * (float) $this->peso_empaque * $lam * $cajas, 2);
        } else {
            $this->weight_per_box = null;
        }
    }

    public function almacen()
    {
        return $this->belongsTo(Warehouse::class, 'almacen_id');
    }

    public function containers()
    {
        return $this->belongsToMany(Container::class, 'container_product')
            ->withPivot('boxes', 'sheets_per_box')
            ->withTimestamps();
    }

    public function getCajasAttribute()
    {
        if ($this->tipo_medida === 'caja' && $this->unidades_por_caja > 0) {
            return (int) ($this->stock / $this->unidades_por_caja);
        }
        return null;
    }
}
