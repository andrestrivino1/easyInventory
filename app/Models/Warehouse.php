<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'direccion', 'ciudad'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'almacen_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')->withTimestamps();
    }

    /**
     * Verifica si esta bodega recibe contenedores
     * (Pablo Rojas y bodegas de Buenaventura)
     */
    public function recibeContenedores()
    {
        $pabloRojasId = 1;
        $nombrePabloRojas = 'Pablo Rojas';
        
        // Pablo Rojas por ID o nombre
        if ($this->id == $pabloRojasId || 
            stripos($this->nombre, $nombrePabloRojas) !== false ||
            stripos($this->nombre, 'Buenaventura') !== false) {
            return true;
        }
        
        // Bodegas de Buenaventura por ciudad
        if ($this->ciudad && stripos($this->ciudad, 'Buenaventura') !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Obtiene los IDs de todas las bodegas que reciben contenedores
     */
    public static function getBodegasQueRecibenContenedores()
    {
        $pabloRojasId = 1;
        
        return static::where(function($query) use ($pabloRojasId) {
                $query->where('id', $pabloRojasId)
                      ->orWhere('nombre', 'LIKE', '%Pablo Rojas%')
                      ->orWhere('nombre', 'LIKE', '%Buenaventura%')
                      ->orWhere('ciudad', 'LIKE', '%Buenaventura%');
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Verifica si un ID de bodega recibe contenedores
     */
    public static function bodegaRecibeContenedores($warehouseId)
    {
        if (!$warehouseId) {
            return false;
        }
        
        $warehouse = static::find($warehouseId);
        if (!$warehouse) {
            return false;
        }
        
        return $warehouse->recibeContenedores();
    }

    /**
     * Obtiene todas las bodegas de Buenaventura
     */
    public static function getBodegasBuenaventura()
    {
        $pabloRojasId = 1;
        
        return static::where(function($query) use ($pabloRojasId) {
                $query->where('id', $pabloRojasId)
                      ->orWhere('nombre', 'LIKE', '%Pablo Rojas%')
                      ->orWhere('nombre', 'LIKE', '%Buenaventura%')
                      ->orWhere('ciudad', 'LIKE', '%Buenaventura%');
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtiene los IDs de todas las bodegas de Buenaventura
     */
    public static function getBodegasBuenaventuraIds()
    {
        return static::getBodegasBuenaventura()->pluck('id')->toArray();
    }
}
