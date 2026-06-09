<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US1 — Gestión del usuario con rol "cliente_funcionario".
 * El rol se administra igual que "clientes": asignación múltiple de bodegas.
 */
class ClienteFuncionarioUserManagementTest extends TestCase
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

    private function warehouse(string $nombre): Warehouse
    {
        return Warehouse::create([
            'nombre' => $nombre,
            'ciudad' => 'Buenaventura',
            'direccion' => 'Calle 1',
        ]);
    }

    /** @test */
    public function admin_can_create_cliente_funcionario_with_assigned_warehouses(): void
    {
        $w1 = $this->warehouse('Bodega A');
        $w2 = $this->warehouse('Bodega B');

        $response = $this->actingAs($this->admin())->post(route('users.store'), [
            'nombre_completo' => 'Cliente Funcionario',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'almacenes' => [$w1->id, $w2->id],
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'cf@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('cliente_funcionario', $user->rol);
        $this->assertNull($user->almacen_id);
        $this->assertEqualsCanonicalizing(
            [$w1->id, $w2->id],
            $user->almacenes()->pluck('warehouses.id')->all()
        );
    }

    /** @test */
    public function creating_cliente_funcionario_without_warehouses_fails_validation(): void
    {
        $response = $this->actingAs($this->admin())->post(route('users.store'), [
            'nombre_completo' => 'Cliente Funcionario',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            // sin almacenes
        ]);

        $response->assertSessionHasErrors('almacenes');
        $this->assertDatabaseMissing('users', ['email' => 'cf@test.com']);
    }

    /** @test */
    public function admin_can_update_cliente_funcionario_assigned_warehouses(): void
    {
        $w1 = $this->warehouse('Bodega A');
        $w2 = $this->warehouse('Bodega B');

        $user = User::create([
            'nombre_completo' => 'Cliente Funcionario',
            'name' => 'cf@test.com',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'password' => bcrypt('secret123'),
        ]);
        $user->almacenes()->sync([$w1->id, $w2->id]);

        $response = $this->actingAs($this->admin())->put(route('users.update', $user), [
            'nombre_completo' => 'Cliente Funcionario',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'almacenes' => [$w1->id], // se quita w2
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertEqualsCanonicalizing(
            [$w1->id],
            $user->fresh()->almacenes()->pluck('warehouses.id')->all()
        );
    }

    /** @test */
    public function changing_role_away_from_cliente_funcionario_detaches_warehouses(): void
    {
        $w1 = $this->warehouse('Bodega A');

        $user = User::create([
            'nombre_completo' => 'Cliente Funcionario',
            'name' => 'cf@test.com',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'password' => bcrypt('secret123'),
        ]);
        $user->almacenes()->sync([$w1->id]);

        $this->actingAs($this->admin())->put(route('users.update', $user), [
            'nombre_completo' => 'Cliente Funcionario',
            'email' => 'cf@test.com',
            'rol' => 'admin',
        ]);

        $this->assertCount(0, $user->fresh()->almacenes);
    }
}
