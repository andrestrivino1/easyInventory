<?php

namespace App\Policies;

use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiquidacionPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability)
    {
        // admin tiene acceso total.
        if ($user->rol === 'admin') {
            return true;
        }
        // Roles distintos de placas no tienen acceso al módulo.
        if ($user->rol !== 'placas') {
            return false;
        }
        // placas: continúa a la evaluación por método (devolver null para no cortocircuitar).
        return null;
    }

    /**
     * ¿La liquidación pertenece a un conductor asignado al usuario placas?
     */
    private function owns(User $user, Liquidacion $liquidacion): bool
    {
        return in_array($liquidacion->driver_id, $user->assignedDriverIds(), true);
    }

    public function viewAny(User $user)
    {
        // placas puede ver el listado (filtrado por sus conductores en el controlador).
        return $user->rol === 'placas';
    }

    public function view(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion);
    }

    public function create(User $user)
    {
        // El conductor seleccionado se valida en el Form Request.
        return $user->rol === 'placas';
    }

    public function update(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion) && $liquidacion->isBorrador();
    }

    public function delete(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion) && $liquidacion->isBorrador();
    }

    public function close(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion) && $liquidacion->isBorrador();
    }

    public function reopen(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion) && $liquidacion->isCerrada();
    }

    public function cancel(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion) && $liquidacion->isCerrada();
    }

    public function downloadPdf(User $user, Liquidacion $liquidacion)
    {
        return $this->owns($user, $liquidacion);
    }
}
