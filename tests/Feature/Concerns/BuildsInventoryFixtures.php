<?php

namespace Tests\Feature\Concerns;

use App\Models\Container;
use App\Models\Driver;
use App\Models\Import;
use App\Models\Product;
use App\Models\Salida;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fixtures compartidos por las pruebas de la funcionalidad 008.
 *
 * El stock y la trazabilidad no tienen tabla propia: se derivan de contenedores,
 * transferencias y salidas. Por eso los helpers construyen esas tres fuentes.
 */
trait BuildsInventoryFixtures
{
    /** Bodega que recibe contenedores (la regla es por nombre/ciudad, no por bandera). */
    protected function containerWarehouse(string $nombre = 'Bodega Buenaventura'): Warehouse
    {
        return Warehouse::create([
            'nombre' => $nombre,
            'ciudad' => 'Buenaventura',
            'direccion' => 'Calle 1',
        ]);
    }

    /** Bodega ordinaria: su saldo se deriva de las transferencias recibidas. */
    protected function plainWarehouse(string $nombre = 'Bodega Cali'): Warehouse
    {
        return Warehouse::create([
            'nombre' => $nombre,
            'ciudad' => 'Cali',
            'direccion' => 'Calle 2',
        ]);
    }

    protected function globalProduct(string $codigo = 'P001', string $nombre = 'Vidrio'): Product
    {
        return Product::create([
            'codigo' => $codigo,
            'nombre' => $nombre,
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

    protected function container(string $reference, int $warehouseId): Container
    {
        return Container::create([
            'reference' => $reference,
            'note' => null,
            'warehouse_id' => $warehouseId,
        ]);
    }

    /** Carga un producto en un contenedor: esto es lo que crea existencia real. */
    protected function stockIn(Container $container, Product $product, int $boxes, int $sheetsPerBox = 10): void
    {
        DB::table('container_product')->insert([
            'container_id' => $container->id,
            'product_id' => $product->id,
            'boxes' => $boxes,
            'sheets_per_box' => $sheetsPerBox,
            'weight_per_box' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function admin(string $email = 'admin@test.com'): User
    {
        return User::create([
            'nombre_completo' => 'Admin',
            'name' => $email,
            'email' => $email,
            'rol' => 'admin',
            'password' => bcrypt('secret123'),
        ]);
    }

    protected function driver(string $name = 'Conductor Uno'): Driver
    {
        return Driver::create([
            'name' => $name,
            'identity' => (string) random_int(10000, 99999),
            'vehicle_plate' => 'ABC' . random_int(100, 999),
            'active' => 1,
        ]);
    }

    /** Salida ya registrada, con su detalle. */
    protected function salida(Warehouse $warehouse, Product $product, int $quantity, User $user, ?int $containerId = null): Salida
    {
        $salida = Salida::create([
            'salida_number' => 'SAL-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'fecha' => now()->toDateString(),
            'a_nombre_de' => 'Cliente',
            'nit_cedula' => '900',
        ]);

        DB::table('salida_products')->insert([
            'salida_id' => $salida->id,
            'product_id' => $product->id,
            'container_id' => $containerId,
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $salida;
    }

    /**
     * Transferencia ya registrada, con su detalle.
     *
     * transfer_orders.driver_id es NOT NULL en el esquema que generan las
     * migraciones, así que el conductor se crea aquí si no se indica uno.
     */
    protected function transfer(Warehouse $from, Warehouse $to, Product $product, int $quantity, ?int $containerId = null, string $status = 'en_transito', ?int $driverId = null): TransferOrder
    {
        $order = TransferOrder::create([
            'order_number' => 'TO-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'warehouse_from_id' => $from->id,
            'warehouse_to_id' => $to->id,
            'status' => $status,
            'date' => now()->toDateString(),
            'driver_id' => $driverId ?? $this->driver()->id,
        ]);

        DB::table('transfer_order_products')->insert([
            'transfer_order_id' => $order->id,
            'product_id' => $product->id,
            'container_id' => $containerId,
            'quantity' => $quantity,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $order;
    }

    protected function import(string $doCode, ?string $arrivalDate = null, ?int $userId = null): Import
    {
        return Import::create([
            'do_code' => $doCode,
            'user_id' => $userId ?? $this->admin('import-' . Str::random(6) . '@test.com')->id,
            'origin' => 'China',
            'destination' => 'Buenaventura',
            'departure_date' => $arrivalDate ?? now()->toDateString(),
            'arrival_date' => $arrivalDate ?? now()->toDateString(),
            'status' => 'pending',
        ]);
    }

    /** Escenario completo: contenedores con carga, una transferencia y una salida. */
    protected function seedFullInventory(): array
    {
        $admin = $this->admin();
        $origin = $this->containerWarehouse();
        $dest = $this->plainWarehouse();
        $product = $this->globalProduct();

        $c1 = $this->container('CONT-001', $origin->id);
        $c2 = $this->container('CONT-002', $origin->id);
        $this->stockIn($c1, $product, 50);
        $this->stockIn($c2, $product, 30);

        $this->transfer($origin, $dest, $product, 5, $c1->id);
        $this->salida($origin, $product, 20, $admin, $c1->id);

        return compact('admin', 'origin', 'dest', 'product', 'c1', 'c2');
    }
}
