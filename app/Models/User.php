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
     * ¿El usuario pertenece al "alcance cliente"? Cubre tanto el rol "clientes"
     * como "cliente_funcionario" (que hereda íntegramente el alcance de clientes).
     * Usar en los chequeos de scoping por bodegas asignadas.
     */
    public function isCliente(): bool
    {
        return in_array($this->rol, ['clientes', 'cliente_funcionario'], true);
    }

    /**
     * ¿El usuario tiene el rol "cliente_funcionario"? Gobierna la diferencia
     * respecto a "clientes": acceso operativo al módulo de Contenedores.
     */
    public function isClienteFuncionario(): bool
    {
        return $this->rol === 'cliente_funcionario';
    }

    /**
     * IDs de las bodegas asignadas (para whereIn / Rule::in).
     *
     * @return array<int, int>
     */
    public function assignedWarehouseIds(): array
    {
        // Forzar enteros por consistencia con assignedDriverIds() (PDO emulated
        // prepares puede devolver IDs como strings y romper comparaciones estrictas).
        return $this->almacenes()->pluck('warehouses.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * IDs de los conductores asignados (para whereIn / Rule::in).
     *
     * @return array<int, int>
     */
    public function assignedDriverIds(): array
    {
        // Forzar enteros: en algunos entornos (PDO emulated prepares) pluck() devuelve
        // los IDs como strings, lo que rompe comparaciones estrictas in_array(..., true).
        return $this->assignedDrivers()->pluck('drivers.id')->map(fn ($id) => (int) $id)->all();
    }
}
