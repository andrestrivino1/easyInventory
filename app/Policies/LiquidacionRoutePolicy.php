<?php

namespace App\Policies;

use App\Models\LiquidacionRoute;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LiquidacionRoutePolicy
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

    public function view(User $user, LiquidacionRoute $route)
    {
        return true;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, LiquidacionRoute $route)
    {
        return true;
    }

    public function delete(User $user, LiquidacionRoute $route)
    {
        return $route->liquidaciones()->count() === 0;
    }

    public function toggleActive(User $user, LiquidacionRoute $route)
    {
        return true;
    }
}
