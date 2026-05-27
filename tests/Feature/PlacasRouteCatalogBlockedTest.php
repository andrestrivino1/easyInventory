<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\LiquidacionRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacasRouteCatalogBlockedTest extends TestCase
{
    use RefreshDatabase;

    private function placasUser(): User
    {
        $driver = Driver::create([
            'name' => 'Conductor A',
            'identity' => '10000001',
            'vehicle_plate' => 'ABC123',
            'active' => 1,
        ]);

        $user = User::create([
            'nombre_completo' => 'Usuario Placas',
            'name' => 'placas@test.com',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignedDrivers()->sync([$driver->id]);

        return $user;
    }

    private function makeRoute(): LiquidacionRoute
    {
        return LiquidacionRoute::create([
            'origen' => 'Cali',
            'destino' => 'Buenaventura',
            'vehicle_type' => array_key_first(LiquidacionRoute::VEHICLE_LABELS),
            'active' => 1,
        ]);
    }

    /** @test */
    public function placas_cannot_access_route_catalog_management(): void
    {
        $user = $this->placasUser();
        $route = $this->makeRoute();

        // Listado / creación del catálogo bloqueados por el middleware (redirige al módulo).
        $this->actingAs($user)->get(route('liquidaciones.routes.index'))->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($user)->get(route('liquidaciones.routes.create'))->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($user)->get(route('liquidaciones.routes.edit', $route))->assertRedirect(route('liquidaciones.index'));

        // Mutaciones del catálogo bloqueadas.
        $this->actingAs($user)->post(route('liquidaciones.routes.store'), [
            'origen' => 'X', 'destino' => 'Y',
            'vehicle_type' => array_key_first(LiquidacionRoute::VEHICLE_LABELS),
        ])->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($user)->post(route('liquidaciones.routes.toggle-active', $route))->assertRedirect(route('liquidaciones.index'));
    }

    /** @test */
    public function placas_can_read_route_tolls_for_building_a_liquidacion(): void
    {
        $user = $this->placasUser();
        $route = $this->makeRoute();

        $this->actingAs($user)->get(route('liquidaciones.routes.peajes', $route))->assertOk();
    }

    /** @test */
    public function admin_can_still_manage_route_catalog(): void
    {
        $admin = User::create([
            'nombre_completo' => 'Admin',
            'name' => 'admin@test.com',
            'email' => 'admin@test.com',
            'rol' => 'admin',
            'password' => bcrypt('secret123'),
        ]);

        $this->actingAs($admin)->get(route('liquidaciones.routes.index'))->assertOk();
    }
}
