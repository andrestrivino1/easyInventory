<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $table = 'containers';
    public $timestamps = false;
    protected $fillable = [
        'reference',
        'note',
        'warehouse_id'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'container_product')
            ->withPivot('boxes', 'sheets_per_box')
            ->withTimestamps();
    }
}
