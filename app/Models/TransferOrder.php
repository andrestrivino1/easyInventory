<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\Product;

class TransferOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'warehouse_from_id',
        'warehouse_to_id',
        'status',
        'date',
        'note',
        'driver_name', // NUEVO
        'driver_id',   // NUEVO
        'vehicle_plate'// NUEVO
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Número correlativo tipo TO-000001
            $last = self::orderByDesc('id')->first();
            $next = $last ? ((int)substr($last->order_number, 3)) + 1 : 1;
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
           ->withPivot('quantity')->withTimestamps();
    }
}
