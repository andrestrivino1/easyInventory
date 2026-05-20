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
        if ($user->rol !== 'admin') {
            return false;
        }
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Liquidacion $liquidacion)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Liquidacion $liquidacion)
    {
        return $liquidacion->isBorrador();
    }

    public function delete(User $user, Liquidacion $liquidacion)
    {
        return $liquidacion->isBorrador();
    }

    public function close(User $user, Liquidacion $liquidacion)
    {
        return $liquidacion->isBorrador();
    }

    public function reopen(User $user, Liquidacion $liquidacion)
    {
        return $liquidacion->isCerrada();
    }

    public function cancel(User $user, Liquidacion $liquidacion)
    {
        return $liquidacion->isCerrada();
    }

    public function downloadPdf(User $user, Liquidacion $liquidacion)
    {
        return true;
    }
}
