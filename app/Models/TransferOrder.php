<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Driver;

class TransferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'warehouse_from_id',
        'warehouse_to_id',
        'salida',
        'destino',
        'status',
        'date',
        'note',
        'driver_id',
        'external_driver_name',
        'external_driver_identity',
        'external_driver_plate',
        'external_driver_phone',
        'aprobo',
        'ciudad_destino',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Número correlativo tipo TO-000001
            // Buscar el order_number más alto que existe
            $last = self::orderByDesc('order_number')->first();
            $next = $last ? ((int) substr($last->order_number, 3)) + 1 : 1;
            
            // Verificar que el número no exista (por si acaso)
            $attempts = 0;
            while (self::where('order_number', 'TO-' . str_pad($next, 6, '0', STR_PAD_LEFT))->exists() && $attempts < 100) {
                $next++;
                $attempts++;
            }
            
            $model->order_number = 'TO-' . str_pad($next, 6, '0', STR_PAD_LEFT);
        });
    }

    public function from()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_from_id');
    }
    public function to()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'transfer_order_products')
            ->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by', 'sheets_per_box', 'weight_per_box')->withTimestamps();
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    /** Nombre del conductor (registrado o externo) */
    public function getDriverNameAttribute(): ?string
    {
        if ($this->external_driver_name) {
            return $this->external_driver_name;
        }
        return $this->driver ? $this->driver->name : null;
    }

    /** Cédula/identidad del conductor */
    public function getDriverIdentityAttribute(): ?string
    {
        if ($this->external_driver_identity) {
            return $this->external_driver_identity;
        }
        return $this->driver ? $this->driver->identity : null;
    }

    /** Placa del vehículo */
    public function getDriverPlateAttribute(): ?string
    {
        if ($this->external_driver_plate) {
            return $this->external_driver_plate;
        }
        return $this->driver ? $this->driver->vehicle_plate : null;
    }

    /** Teléfono del conductor */
    public function getDriverPhoneAttribute(): ?string
    {
        if ($this->external_driver_phone) {
            return $this->external_driver_phone;
        }
        return $this->driver ? $this->driver->phone : null;
    }

    public function isExternalDriver(): bool
    {
        return !empty($this->external_driver_name);
    }
}
