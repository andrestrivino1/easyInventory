<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nombre_completo',
        'email',
        'telefono',
        'password',
        'almacen_id',
        'rol',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function almacen()
    {
        return $this->belongsTo(Warehouse::class, 'almacen_id');
    }

    public function almacenes()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse')->withTimestamps();
    }

    /**
     * Conductores asignados a este usuario (relevante para el rol "placas").
     */
    public function assignedDrivers()
    {
        return $this->belongsToMany(Driver::class, 'user_driver')->withTimestamps();
    }

    /**
     * ¿El usuario tiene el rol "placas"?
     */
    public function isPlacas(): bool
    {
        return $this->rol === 'placas';
    }

    /**
     * IDs de los conductores asignados (para whereIn / Rule::in).
     *
     * @return array<int, int>
     */
    public function assignedDriverIds(): array
    {
        return $this->assignedDrivers()->pluck('drivers.id')->all();
    }
}
