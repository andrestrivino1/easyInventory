<?php

namespace Tests\Feature;

use App\Models\Container;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US3 — "cliente_funcionario" trabaja el módulo de Contenedores limitado a sus
 * bodegas asignadas: ve/crea/edita/exporta/imprime, pero NO elimina, y no puede
 * operar contenedores de bodegas no asignadas.
 */
class ClienteFuncionarioContainersTest extends TestCase
{
    use RefreshDatabase;

    private function warehouse(string $nombre): Warehouse
    {
        // ciudad "Buenaventura" => la bodega recibe contenedores
        return Warehouse::create([
            'nombre' => $nombre,
            'ciudad' => 'Buenaventura',
            'direccion' => 'Calle 1',
        ]);
    }

    private function clienteFuncionario(array $warehouseIds): User
    {
        $u = User::create([
            'nombre_completo' => 'Cliente Funcionario',
            'name' => 'cf@test.com',
            'email' => 'cf@test.com',
            'rol' => 'cliente_funcionario',
            'password' => bcrypt('secret123'),
        ]);
        $u->almacenes()->sync($warehouseIds);

        return $u;
    }

    private function container(string $reference, int $warehouseId): Container
    {
        return Container::create([
            'reference' => $reference,
            'note' => null,
            'warehouse_id' => $warehouseId,
        ]);
    }

    private function globalProduct(): Product
    {
        return Product::create([
            'codigo' => 'P001',
            'nombre' => 'Vidrio',
            'medidas' => '1x1',
            'calibre' => 4,
            'alto' => 1,
            'ancho' => 1,
            'peso_empaque' => 1,
            'weight_per_box' => 10,
            'precio' => 1000,
            'stock' => 0,
            'estado' => 1,
            'almacen_id' => null,
            'tipo_medida' => 'caja',
            'unidades_por_caja' => 10,
        ]);
    }

    /** @test */
    public function index_only_shows_containers_of_assigned_warehouses(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $other = $this->warehouse('Bodega Ajena');
        $cont = $this->container('CONT-ASIGNADA', $assigned->id);
        $this->container('CONT-AJENA', $other->id);

        $cf = $this->clienteFuncionario([$assigned->id]);

        $response = $this->actingAs($cf)->get(route('containers.index'));
        $response->assertOk();
        $response->assertSee('CONT-ASIGNADA');
        $response->assertDontSee('CONT-AJENA');
    }

    /** @test */
    public function can_create_container_in_assigned_warehouse(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $product = $this->globalProduct();
        $cf = $this->clienteFuncionario([$assigned->id]);

        $response = $this->actingAs($cf)->post(route('containers.store'), [
            'reference' => 'CONT-NUEVO',
            'note' => 'obs',
            'warehouse_id' => $assigned->id,
            'products' => [
                ['product_id' => $product->id, 'boxes' => 5, 'sheets_per_box' => 10, 'weight_per_box' => 20],
            ],
        ]);

        $response->assertRedirect(route('containers.index'));
        $this->assertDatabaseHas('containers', ['reference' => 'CONT-NUEVO', 'warehouse_id' => $assigned->id]);
    }

    /** @test */
    public function cannot_create_container_in_unassigned_warehouse(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $other = $this->warehouse('Bodega Ajena');
        $product = $this->globalProduct();
        $cf = $this->clienteFuncionario([$assigned->id]);

        $response = $this->actingAs($cf)->post(route('containers.store'), [
            'reference' => 'CONT-RECHAZADO',
            'warehouse_id' => $other->id,
            'products' => [
                ['product_id' => $product->id, 'boxes' => 5, 'sheets_per_box' => 10, 'weight_per_box' => 20],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('containers', ['reference' => 'CONT-RECHAZADO']);
    }

    /** @test */
    public function can_edit_container_of_assigned_warehouse_but_not_of_another(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $other = $this->warehouse('Bodega Ajena');
        $own = $this->container('CONT-PROPIA', $assigned->id);
        $foreign = $this->container('CONT-AJENA', $other->id);
        $cf = $this->clienteFuncionario([$assigned->id]);

        $this->actingAs($cf)->get(route('containers.edit', $own))->assertOk();

        $this->actingAs($cf)->get(route('containers.edit', $foreign))
            ->assertRedirect(route('containers.index'));
    }

    /** @test */
    public function can_export_and_print_assigned_but_not_foreign_containers(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $other = $this->warehouse('Bodega Ajena');
        $own = $this->container('CONT-PROPIA', $assigned->id);
        $foreign = $this->container('CONT-AJENA', $other->id);
        $cf = $this->clienteFuncionario([$assigned->id]);

        $this->actingAs($cf)->get(route('containers.export', $own))->assertOk();
        $this->actingAs($cf)->get(route('containers.print', $own))->assertOk();

        $this->actingAs($cf)->get(route('containers.export', $foreign))
            ->assertRedirect(route('containers.index'));
        $this->actingAs($cf)->get(route('containers.print', $foreign))
            ->assertRedirect(route('containers.index'));
    }

    /** @test */
    public function cannot_delete_containers(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $own = $this->container('CONT-PROPIA', $assigned->id);
        $cf = $this->clienteFuncionario([$assigned->id]);

        $this->actingAs($cf)->delete(route('containers.destroy', $own))
            ->assertRedirect(route('containers.index'));

        $this->assertDatabaseHas('containers', ['id' => $own->id]);
    }

    /**
     * No-regresión: funcionario sigue en solo lectura (no puede crear/editar).
     *
     * @test
     */
    public function funcionario_remains_read_only_on_containers(): void
    {
        $assigned = $this->warehouse('Bodega Asignada');
        $cont = $this->container('CONT-A', $assigned->id);

        $funcionario = User::create([
            'nombre_completo' => 'Funcionario',
            'name' => 'func@test.com',
            'email' => 'func@test.com',
            'rol' => 'funcionario',
            'password' => bcrypt('secret123'),
        ]);

        $this->actingAs($funcionario)->get(route('containers.create'))
            ->assertRedirect(route('containers.index'));
        $this->actingAs($funcionario)->get(route('containers.edit', $cont))
            ->assertRedirect(route('containers.index'));
    }
}
