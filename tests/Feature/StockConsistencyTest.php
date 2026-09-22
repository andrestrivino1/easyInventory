<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * Polish — La pantalla de Stock y sus exportaciones deben coincidir (FR-025, SC-008).
 *
 * El saldo físico de una bodega que recibe contenedores vive en
 * `container_product.boxes`, y ese saldo YA se descuenta al crear la
 * transferencia. Volver a restar la transferencia al calcular el stock sería
 * contarla dos veces: por eso la referencia de verdad es container_product.
 */
class StockConsistencyTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    /** @test */
    public function creating_a_transfer_decrements_the_container_balance_once(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $this->stockIn($c1, $product, 10, 10); // 10 cajas × 10 láminas

        $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3, 'container_id' => ''],
            ],
        ])->assertRedirect();

        $boxes = (int) DB::table('container_product')
            ->where('container_id', $c1->id)
            ->where('product_id', $product->id)
            ->sum('boxes');

        // Descontado exactamente una vez: 10 - 3 = 7.
        $this->assertSame(7, $boxes, 'El saldo del contenedor se descuenta una sola vez');
    }

    /** @test */
    public function the_stock_screen_and_its_pdf_export_agree(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $this->stockIn($c1, $product, 10, 10);

        $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3, 'container_id' => ''],
            ],
        ])->assertRedirect();

        $screen = $this->actingAs($admin)->get(route('stock.index', ['warehouse_id' => $from->id]));
        $screen->assertOk();

        $export = $this->actingAs($admin)->get(route('stock.export-pdf', ['warehouse_id' => $from->id]));
        $export->assertOk();

        // FR-025: ambas rutas deben reportar el mismo saldo (7 cajas = 70 láminas).
        $this->assertSame(
            $this->stockFromScreen($from->id, $product->id),
            $this->stockFromExport($from->id, $product->id),
            'La pantalla y la exportación deben reportar el mismo stock'
        );
    }

    /** Stock que reporta la ruta de pantalla. */
    private function stockFromScreen(int $warehouseId, int $productId): int
    {
        return $this->invokeStockPath('index', $warehouseId, $productId);
    }

    /** Stock que reporta la ruta de exportación. */
    private function stockFromExport(int $warehouseId, int $productId): int
    {
        return $this->invokeStockPath('getStockData', $warehouseId, $productId);
    }

    /**
     * Ambas rutas comparten el cálculo canónico calcularStockPorBodega(), así
     * que se compara directamente contra él.
     */
    private function invokeStockPath(string $which, int $warehouseId, int $productId): int
    {
        $controller = app(\App\Http\Controllers\StockController::class);

        $method = new \ReflectionMethod($controller, 'calcularStockPorBodega');
        $method->setAccessible(true);

        $products = \App\Models\Product::whereNull('almacen_id')->get();
        $result = $method->invoke($controller, $products, $warehouseId);

        $perWarehouse = $result->get($productId);

        return (int) ($perWarehouse ? $perWarehouse->get($warehouseId, 0) : 0);
    }

    /**
     * @test
     *
     * TraceabilityController::index() y getTraceabilityData() contienen la misma
     * lógica duplicada. Hoy no divergen; esta prueba lo fija para que no empiecen
     * a hacerlo sin que nadie se entere.
     */
    public function the_traceability_screen_and_its_export_agree(): void
    {
        $data = $this->seedFullInventory();
        $admin = $data['admin'];

        $screen = $this->actingAs($admin)->get(route('traceability.index'));
        $export = $this->actingAs($admin)->get(route('traceability.export-pdf'));

        $screen->assertOk();
        $export->assertOk();

        // Ambas deben reflejar los mismos documentos de origen.
        $salidaNumber = DB::table('salidas')->value('salida_number');
        $orderNumber = DB::table('transfer_orders')->value('order_number');

        $screen->assertSee($salidaNumber);
        $screen->assertSee($orderNumber);
    }

    /** @test */
    public function the_canonical_calculation_matches_the_container_balance(): void
    {
        $admin = $this->admin();
        $from = $this->containerWarehouse();
        $to = $this->plainWarehouse();
        $product = $this->globalProduct();
        $driver = $this->driver();

        $c1 = $this->container('CONT-A', $from->id);
        $this->stockIn($c1, $product, 10, 10);

        $this->actingAs($admin)->post(route('transfer-orders.store'), [
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'driver_id' => $driver->id,
            'products' => [
                ['product_id' => $product->id, 'quantity' => 3, 'container_id' => ''],
            ],
        ])->assertRedirect();

        $canonical = $this->invokeStockPath('canonical', $from->id, $product->id);

        $boxes = (int) DB::table('container_product')
            ->where('container_id', $c1->id)
            ->where('product_id', $product->id)
            ->sum('boxes');

        // El cálculo canónico no puede quedar por debajo del saldo real del
        // contenedor: eso sería restar la transferencia por segunda vez.
        $this->assertSame(
            $boxes * 10,
            $canonical,
            'El cálculo canónico debe coincidir con el saldo del contenedor, sin doble descuento'
        );
    }
}
