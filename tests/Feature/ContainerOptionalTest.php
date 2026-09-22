<?php

namespace Tests\Feature;

use App\Services\ContainerAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * US3 — Transferencias y salidas descuentan sin exigir contenedor.
 *
 * FR-018 a FR-026, SC-006, SC-007, SC-010.
 */
class ContainerOptionalTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    private function allocator(): ContainerAllocator
    {
        return app(ContainerAllocator::class);
    }

    private function boxesOf(int $containerId, int $productId): int
    {
        return (int) DB::table('container_product')
            ->where('container_id', $containerId)
            ->where('product_id', $productId)
            ->sum('boxes');
    }

    // ---------------------------------------------------------------
    // Reparto FIFO
    // ---------------------------------------------------------------

    /** @test */
    public function allocator_spreads_across_containers_in_fifo_order(): void
    {
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();

        $c1 = $this->container('CONT-A', $wh->id);
        $c2 = $this->container('CONT-B', $wh->id);
        $c3 = $this->container('CONT-C', $wh->id);
        $this->stockIn($c1, $product, 10);
        $this->stockIn($c2, $product, 10);
        $this->stockIn($c3, $product, 10);

        // 25 cajas: agota A y B, y toma 5 de C.
        $this->allocator()->deduct($wh->id, $product->id, 25);

        $this->assertSame(0, $this->boxesOf($c1->id, $product->id), 'El primero se agota');
        $this->assertSame(0, $this->boxesOf($c2->id, $product->id), 'El segundo se agota');
        $this->assertSame(5, $this->boxesOf($c3->id, $product->id), 'Del tercero salen 5');
    }

    /** @test */
    public function allocator_rejects_more_than_available_without_partial_deduction(): void
    {
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();

        $c1 = $this->container('CONT-A', $wh->id);
        $c2 = $this->container('CONT-B', $wh->id);
        $this->stockIn($c1, $product, 10);
        $this->stockIn($c2, $product, 10);

        try {
            $this->allocator()->deduct($wh->id, $product->id, 25);
            $this->fail('Debería rechazar 25 cajas cuando sólo hay 20');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('20', $e->getMessage(), 'El mensaje indica el disponible');
        }

        // SC-010: ningún descuento parcial.
        $this->assertSame(10, $this->boxesOf($c1->id, $product->id), 'Sin descuento parcial');
        $this->assertSame(10, $this->boxesOf($c2->id, $product->id), 'Sin descuento parcial');
    }

    /** @test */
    public function allocator_iterates_row_by_row_when_sheets_per_box_differ(): void
    {
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();
        $c1 = $this->container('CONT-A', $wh->id);

        // El índice unique(container_id, product_id) fue eliminado: el mismo
        // producto puede estar dos veces en un contenedor con distinto empaque.
        $this->stockIn($c1, $product, 4, 10);
        $this->stockIn($c1, $product, 6, 20);

        $this->assertSame(10, $this->allocator()->availableBoxes($wh->id, $product->id));

        // Filtrando por sheets_per_box sólo se ve la fila correspondiente.
        $this->assertSame(4, $this->allocator()->availableBoxes($wh->id, $product->id, 10));
        $this->assertSame(6, $this->allocator()->availableBoxes($wh->id, $product->id, 20));

        $this->allocator()->deduct($wh->id, $product->id, 5);

        // Se consume la primera fila (4) y 1 de la segunda: total 5.
        $this->assertSame(5, $this->boxesOf($c1->id, $product->id));
    }

    /** @test */
    public function allocator_restores_the_total_to_the_warehouse(): void
    {
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();
        $c1 = $this->container('CONT-A', $wh->id);
        $this->stockIn($c1, $product, 10);

        $this->allocator()->deduct($wh->id, $product->id, 10);
        $this->assertSame(0, $this->boxesOf($c1->id, $product->id));

        // La fila quedó en cero: restoreToWarehouse debe encontrarla igualmente.
        $ok = $this->allocator()->restoreToWarehouse($wh->id, $product->id, 10);

        $this->assertTrue($ok);
        $this->assertSame(10, $this->boxesOf($c1->id, $product->id), 'El total vuelve a ser exacto');
    }

    // ---------------------------------------------------------------
    // Transferencias sin contenedor
    // ---------------------------------------------------------------

    /** @test */
    public function transfer_can_be_created_without_choosing_a_container(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $c2 = $this->container('CONT-B', $from->id);
        $this->stockIn($c1, $product, 10);
        $this->stockIn($c2, $product, 10);

        $response = $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 15, 'container_id' => ''],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transfer_order_products', [
            'product_id' => $product->id,
            'quantity' => 15,
            'container_id' => null,
        ]);

        // 15 cajas repartidas: agota el primero, 5 del segundo.
        $this->assertSame(0, $this->boxesOf($c1->id, $product->id));
        $this->assertSame(5, $this->boxesOf($c2->id, $product->id));
    }

    /** @test */
    public function transfer_with_a_chosen_container_still_deducts_from_it(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $c2 = $this->container('CONT-B', $from->id);
        $this->stockIn($c1, $product, 10);
        $this->stockIn($c2, $product, 10);

        $response = $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 4, 'container_id' => $c2->id],
            ],
        ]);

        $response->assertRedirect();

        // FR-021: sale del contenedor elegido, no del primero.
        $this->assertSame(10, $this->boxesOf($c1->id, $product->id));
        $this->assertSame(6, $this->boxesOf($c2->id, $product->id));

        $this->assertDatabaseHas('transfer_order_products', [
            'product_id' => $product->id,
            'container_id' => $c2->id,
        ]);
    }

    /** @test */
    public function transfer_rejects_more_than_the_warehouse_total(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $this->stockIn($c1, $product, 10);

        $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 50, 'container_id' => ''],
            ],
        ]);

        // FR-022 / SC-010: nada se descuenta y no se crea la transferencia.
        $this->assertSame(10, $this->boxesOf($c1->id, $product->id));
        $this->assertSame(0, DB::table('transfer_order_products')->count());
    }

    // ---------------------------------------------------------------
    // Salidas sin contenedor
    // ---------------------------------------------------------------

    /** @test */
    public function salida_records_null_container_when_none_is_chosen(): void
    {
        $admin = $this->admin();
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();

        $c1 = $this->container('CONT-A', $wh->id);
        $this->stockIn($c1, $product, 10, 10);

        $response = $this->actingAs($admin)->post(route('salidas.store'), [
            'warehouse_id' => $wh->id,
            'fecha' => now()->toDateString(),
            'a_nombre_de' => 'Cliente',
            'nit_cedula' => '900123',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2, 'container_id' => ''],
            ],
        ]);

        $response->assertRedirect();

        // FR-024: se registra sin contenedor, no con uno arbitrario.
        $this->assertDatabaseHas('salida_products', [
            'product_id' => $product->id,
            'container_id' => null,
        ]);
    }

    /** @test */
    public function salida_keeps_the_chosen_container(): void
    {
        $admin = $this->admin();
        $wh = $this->containerWarehouse();
        $product = $this->globalProduct();

        $c1 = $this->container('CONT-A', $wh->id);
        $this->stockIn($c1, $product, 10, 10);

        $response = $this->actingAs($admin)->post(route('salidas.store'), [
            'warehouse_id' => $wh->id,
            'fecha' => now()->toDateString(),
            'a_nombre_de' => 'Cliente',
            'nit_cedula' => '900123',
            'products' => [
                ['product_id' => $product->id, 'quantity' => 2, 'container_id' => $c1->id],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('salida_products', [
            'product_id' => $product->id,
            'container_id' => $c1->id,
        ]);
    }

    // ---------------------------------------------------------------
    // Trazabilidad
    // ---------------------------------------------------------------

    /** @test */
    public function traceability_lists_movements_registered_without_a_container(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $this->stockIn($c1, $product, 20);

        $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 5, 'container_id' => ''],
            ],
        ])->assertRedirect();

        $order = DB::table('transfer_orders')->first();

        // FR-023 / SC-007: el movimiento aparece en la trazabilidad.
        $this->actingAs($admin)->get(route('traceability.index'))
            ->assertOk()
            ->assertSee($order->order_number);
    }
}
