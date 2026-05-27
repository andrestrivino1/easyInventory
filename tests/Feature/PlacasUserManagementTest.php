<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacasUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Administrador',
            'name' => 'admin@test.com',
            'email' => 'admin@test.com',
            'rol' => 'admin',
            'password' => bcrypt('secret123'),
        ]);
    }

    private function makeDriver(string $name, string $plate): Driver
    {
        return Driver::create([
            'name' => $name,
            'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate,
            'active' => 1,
        ]);
    }

    /** @test */
    public function admin_can_create_placas_user_with_assigned_drivers(): void
    {
        $d1 = $this->makeDriver('Conductor A', 'ABC123');
        $d2 = $this->makeDriver('Conductor B', 'DEF456');

        $response = $this->actingAs($this->admin())->post(route('users.store'), [
            'nombre_completo' => 'Usuario Placas',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'drivers' => [$d1->id, $d2->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'placas@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('placas', $user->rol);
        $this->assertNull($user->almacen_id);
        $this->assertEqualsCanonicalizing(
            [$d1->id, $d2->id],
            $user->assignedDrivers()->pluck('drivers.id')->all()
        );
    }

    /** @test */
    public function creating_placas_user_without_drivers_fails_validation(): void
    {
        $response = $this->actingAs($this->admin())->post(route('users.store'), [
            'nombre_completo' => 'Usuario Placas',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            // sin drivers
        ]);

        $response->assertSessionHasErrors('drivers');
        $this->assertDatabaseMissing('users', ['email' => 'placas@test.com']);
    }

    /** @test */
    public function admin_can_update_placas_assigned_drivers(): void
    {
        $d1 = $this->makeDriver('Conductor A', 'ABC123');
        $d2 = $this->makeDriver('Conductor B', 'DEF456');

        $user = User::create([
            'nombre_completo' => 'Usuario Placas',
            'name' => 'placas@test.com',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignedDrivers()->sync([$d1->id, $d2->id]);

        $response = $this->actingAs($this->admin())->put(route('users.update', $user), [
            'nombre_completo' => 'Usuario Placas',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'drivers' => [$d1->id], // se quita d2
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertEqualsCanonicalizing(
            [$d1->id],
            $user->fresh()->assignedDrivers()->pluck('drivers.id')->all()
        );
    }

    /** @test */
    public function a_driver_can_be_shared_between_two_placas_users(): void
    {
        $shared = $this->makeDriver('Conductor Compartido', 'XYZ999');

        $u1 = User::create(['nombre_completo' => 'P1', 'name' => 'p1@test.com', 'email' => 'p1@test.com', 'rol' => 'placas', 'password' => bcrypt('x')]);
        $u2 = User::create(['nombre_completo' => 'P2', 'name' => 'p2@test.com', 'email' => 'p2@test.com', 'rol' => 'placas', 'password' => bcrypt('x')]);

        $u1->assignedDrivers()->sync([$shared->id]);
        $u2->assignedDrivers()->sync([$shared->id]);

        $this->assertTrue($u1->assignedDrivers()->whereKey($shared->id)->exists());
        $this->assertTrue($u2->assignedDrivers()->whereKey($shared->id)->exists());
        $this->assertSame(2, $shared->placasUsers()->count());
    }

    /** @test */
    public function changing_role_away_from_placas_detaches_drivers(): void
    {
        $d1 = $this->makeDriver('Conductor A', 'ABC123');

        $user = User::create(['nombre_completo' => 'Usuario', 'name' => 'u@test.com', 'email' => 'u@test.com', 'rol' => 'placas', 'password' => bcrypt('x')]);
        $user->assignedDrivers()->sync([$d1->id]);

        $this->actingAs($this->admin())->put(route('users.update', $user), [
            'nombre_completo' => 'Usuario',
            'email' => 'u@test.com',
            'rol' => 'admin',
        ]);

        $this->assertCount(0, $user->fresh()->assignedDrivers);
    }
}
