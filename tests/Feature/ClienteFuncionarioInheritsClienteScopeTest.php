<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US2 — "cliente_funcionario" hereda el alcance de "clientes".
 * Se compara contra un usuario "clientes" de control con las mismas bodegas:
 * el conjunto de módulos permitidos/denegados debe coincidir.
 */
class ClienteFuncionarioInheritsClienteScopeTest extends TestCase
{
    use RefreshDatabase;

    private function userWithWarehouses(string $rol, string $email, array $warehouseIds): User
    {
        $u = User::create([
            'nombre_completo' => ucfirst($rol),
            'name' => $email,
            'email' => $email,
            'rol' => $rol,
            'password' => bcrypt('secret123'),
        ]);
        $u->almacenes()->sync($warehouseIds);

        return $u;
    }

    private function warehouse(string $nombre): Warehouse
    {
        return Warehouse::create([
            'nombre' => $nombre,
            'ciudad' => 'Buenaventura',
            'direccion' => 'Calle 1',
        ]);
    }

    /**
     * El predicado de modelo trata ambos roles como "alcance cliente".
     *
     * @test
     */
    public function is_cliente_helper_covers_both_roles(): void
    {
        $cf = $this->userWithWarehouses('cliente_funcionario', 'cf@test.com', []);
        $cli = $this->userWithWarehouses('clientes', 'cli@test.com', []);

        $this->assertTrue($cf->isCliente());
        $this->assertTrue($cli->isCliente());
        $this->assertTrue($cf->isClienteFuncionario());
        $this->assertFalse($cli->isClienteFuncionario());
    }

    /**
     * El conjunto de accesos (permitidos y denegados) del cliente_funcionario
     * coincide con el del rol clientes en los módulos heredados.
     *
     * @test
     */
    public function access_surface_matches_clientes_role(): void
    {
        $w = $this->warehouse('Bodega A');
        $cf = $this->userWithWarehouses('cliente_funcionario', 'cf@test.com', [$w->id]);
        $cli = $this->userWithWarehouses('clientes', 'cli@test.com', [$w->id]);

        // Módulos compartidos: ambos deben poder acceder (200)
        $sharedRoutes = [
            'home',
            'stock.index',
            'traceability.index',
            'products.index',
            'salidas.index',
            'transfer-orders.index',
        ];
        foreach ($sharedRoutes as $name) {
            $cfStatus = $this->actingAs($cf)->get(route($name))->getStatusCode();
            $cliStatus = $this->actingAs($cli)->get(route($name))->getStatusCode();
            $this->assertSame($cliStatus, $cfStatus, "Status divergente en {$name}");
            $this->assertSame(200, $cfStatus, "cliente_funcionario no pudo acceder a {$name}");
        }

        // Módulos reservados a administradores: denegados para ambos por igual
        $this->actingAs($cf)->get(route('users.index'))->assertRedirect('/');
        $this->actingAs($cli)->get(route('users.index'))->assertRedirect('/');

        // Liquidación de Viajes: gate lo niega para ambos
        $this->actingAs($cf)->get(route('liquidaciones.index'))->assertForbidden();
        $this->actingAs($cli)->get(route('liquidaciones.index'))->assertForbidden();
    }

    /**
     * La diferencia: cliente_funcionario sí entra al módulo de Contenedores.
     *
     * @test
     */
    public function cliente_funcionario_can_reach_containers_module(): void
    {
        $w = $this->warehouse('Bodega A');
        $cf = $this->userWithWarehouses('cliente_funcionario', 'cf@test.com', [$w->id]);

        $this->actingAs($cf)->get(route('containers.index'))->assertOk();
    }
}
