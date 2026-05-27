<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacasAccessIsolationTest extends TestCase
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

    /** @test */
    public function placas_user_lands_on_liquidaciones_after_login(): void
    {
        $response = $this->actingAs($this->placasUser())->get(route('home'));
        $response->assertRedirect(route('liquidaciones.index'));
    }

    /** @test */
    public function placas_user_can_access_the_liquidaciones_module(): void
    {
        $response = $this->actingAs($this->placasUser())->get(route('liquidaciones.index'));
        $response->assertOk();
    }

    /** @test */
    public function placas_user_is_blocked_from_other_modules(): void
    {
        $user = $this->placasUser();

        foreach ([
            route('products.index'),
            route('users.index'),
            route('drivers.index'),
            route('stock.index'),
            route('liquidaciones.routes.index'),
        ] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertRedirect(route('liquidaciones.index'));
        }
    }

    /** @test */
    public function placas_user_can_use_the_route_tolls_ajax_helper(): void
    {
        // El endpoint AJAX de peajes está permitido (necesario para armar la liquidación).
        $user = $this->placasUser();
        $route = \App\Models\LiquidacionRoute::create([
            'origen' => 'A', 'destino' => 'B', 'vehicle_type' => array_key_first(\App\Models\LiquidacionRoute::VEHICLE_LABELS), 'active' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('liquidaciones.routes.peajes', $route));
        $response->assertOk();
    }
}
