<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'direccion'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'almacen_id');
    }
}
