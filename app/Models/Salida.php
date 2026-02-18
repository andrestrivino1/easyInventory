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
        'user_id',
        'driver_id',
        'external_driver_name',
        'external_driver_identity',
        'external_driver_plate',
        'external_driver_phone',
        'fecha',
        'a_nombre_de',
        'nit_cedula',
        'note',
        'aprobo',
        'ciudad_destino',
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

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\Driver::class, 'driver_id');
    }

    public function getDriverNameAttribute(): ?string
    {
        if (!empty($this->external_driver_name)) {
            return $this->external_driver_name;
        }
        return $this->driver ? $this->driver->name : null;
    }

    public function getDriverIdentityAttribute(): ?string
    {
        if (!empty($this->external_driver_identity)) {
            return $this->external_driver_identity;
        }
        return $this->driver ? $this->driver->identity : null;
    }

    public function getDriverPlateAttribute(): ?string
    {
        if (!empty($this->external_driver_plate)) {
            return $this->external_driver_plate;
        }
        return $this->driver ? $this->driver->vehicle_plate : null;
    }

    public function getDriverPhoneAttribute(): ?string
    {
        if (!empty($this->external_driver_phone)) {
            return $this->external_driver_phone;
        }
        return $this->driver ? $this->driver->phone : null;
    }

    public function isExternalDriver(): bool
    {
        return !empty($this->external_driver_name);
    }
}
