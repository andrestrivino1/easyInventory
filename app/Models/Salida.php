<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\Product;

class Salida extends Model
{
    use HasFactory;

    protected $fillable = [
        'salida_number',
        'warehouse_id',
        'fecha',
        'a_nombre_de',
        'nit_cedula',
        'note',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generar número correlativo tipo SAL-000001
            if (empty($model->salida_number)) {
                $last = self::orderByDesc('id')->first();
                $next = $last ? ((int)substr($last->salida_number, 4)) + 1 : 1;
                $model->salida_number = 'SAL-' . str_pad($next, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'salida_products')
            ->withPivot('quantity', 'container_id')
            ->withTimestamps();
    }
}
