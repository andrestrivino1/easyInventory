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
        'precio',
        'stock',
        'estado',
        'almacen_id',
        'tipo_medida',
        'unidades_por_caja',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $last = self::orderByDesc('id')->first();
            $next = $last ? ((int)substr($last->codigo, 4)) + 1 : 1;
            $model->codigo = 'PRD-' . str_pad($next, 6, '0', STR_PAD_LEFT);
        });
    }

    public function almacen()
    {
        return $this->belongsTo(Warehouse::class, 'almacen_id');
    }

    public function getCajasAttribute()
    {
        if ($this->tipo_medida === 'caja' && $this->unidades_por_caja > 0) {
            return (int) ($this->stock / $this->unidades_por_caja);
        }
        return null;
    }
}
