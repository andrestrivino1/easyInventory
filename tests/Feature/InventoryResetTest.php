<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Import;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * US1 — Stock y Trazabilidad arrancan vacíos.
 *
 * FR-001 a FR-010, SC-001, SC-002, SC-004.
 */
class InventoryResetTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    /** Las seis fuentes de stock y trazabilidad. */
    private const INVENTORY_TABLES = [
        'containers',
        'container_product',
        'transfer_orders',
        'transfer_order_products',
        'salidas',
        'salida_products',
    ];

    /** @test */
    public function reset_empties_every_inventory_source(): void
    {
        $this->seedFullInventory();

        foreach (self::INVENTORY_TABLES as $table) {
            $this->assertGreaterThan(0, DB::table($table)->count(), "{$table} debería tener datos antes");
        }

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true])
            ->assertExitCode(0);

        foreach (self::INVENTORY_TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} debería quedar vacía (FR-003)");
        }
    }

    /** @test */
    public function reset_preserves_master_data(): void
    {
        $this->seedFullInventory();
        $this->driver();
        $this->globalProduct('P002', 'Vidrio 2');

        $before = [
            'products' => Product::count(),
            'warehouses' => Warehouse::count(),
            'users' => User::count(),
            'drivers' => Driver::count(),
        ];

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame($before['products'], Product::count(), 'FR-004: productos intactos');
        $this->assertSame($before['warehouses'], Warehouse::count(), 'FR-004: bodegas intactas');
        $this->assertSame($before['users'], User::count(), 'FR-004: usuarios intactos');
        $this->assertSame($before['drivers'], Driver::count(), 'FR-004: conductores intactos');
    }

    /** @test */
    public function reset_preserves_user_warehouse_assignments(): void
    {
        $data = $this->seedFullInventory();
        $cliente = User::create([
            'nombre_completo' => 'Cliente',
            'name' => 'cliente@test.com',
            'email' => 'cliente@test.com',
            'rol' => 'cliente',
            'password' => bcrypt('secret123'),
        ]);
        $cliente->almacenes()->sync([$data['origin']->id]);

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(1, $cliente->fresh()->almacenes()->count(), 'FR-004: asignaciones intactas');
    }

    /** @test */
    public function reset_does_not_touch_imports_when_scope_is_inventory(): void
    {
        $this->seedFullInventory();
        $this->import('VJP26-010');

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(1, Import::count(), 'El ámbito inventory no debe tocar importaciones');
    }

    /** @test */
    public function stock_screen_is_empty_after_reset(): void
    {
        $data = $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);

        $response = $this->actingAs($data['admin'])->get(route('stock.index'));

        $response->assertOk();
        $response->assertDontSee('CONT-001');
        $response->assertDontSee('CONT-002');
    }

    /** @test */
    public function traceability_screen_is_empty_after_reset(): void
    {
        $data = $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);

        $response = $this->actingAs($data['admin'])->get(route('traceability.index'));

        $response->assertOk();
        $response->assertDontSee('CONT-001');
    }

    /** @test */
    public function cliente_role_sees_no_residual_data_after_reset(): void
    {
        $data = $this->seedFullInventory();
        $cliente = User::create([
            'nombre_completo' => 'Cliente',
            'name' => 'cliente2@test.com',
            'email' => 'cliente2@test.com',
            'rol' => 'cliente',
            'password' => bcrypt('secret123'),
        ]);
        $cliente->almacenes()->sync([$data['origin']->id]);

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);

        $this->actingAs($cliente)->get(route('stock.index'))
            ->assertOk()
            ->assertDontSee('CONT-001');

        $this->actingAs($cliente)->get(route('traceability.index'))
            ->assertOk()
            ->assertDontSee('CONT-001');
    }

    /** @test */
    public function reset_is_idempotent(): void
    {
        $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true])->assertExitCode(0);

        // FR-010: una segunda ejecución sobre un sistema ya vacío no debe fallar.
        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true])->assertExitCode(0);

        foreach (self::INVENTORY_TABLES as $table) {
            $this->assertSame(0, DB::table($table)->count());
        }
    }

    /** @test */
    public function repeated_reset_does_not_alter_the_do_consecutive(): void
    {
        $this->import('VJP26-057', now()->toDateString());

        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true]);
        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true]);

        // El número emitido sigue siendo visible con withTrashed, así que el
        // siguiente consecutivo no puede retroceder.
        $last = Import::withTrashed()->orderByDesc('do_code')->first();
        $this->assertSame('VJP26-057', $last->do_code);
    }

    /** @test */
    public function dry_run_writes_nothing(): void
    {
        $this->seedFullInventory();
        $before = DB::table('containers')->count();

        $this->artisan('inventory:reset', ['--scope' => 'all', '--dry-run' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame($before, DB::table('containers')->count(), '--dry-run no debe escribir');
        $this->assertSame(0, DB::table('archived_containers')->count(), '--dry-run no debe archivar');
        $this->assertSame(0, DB::table('inventory_reset_logs')->count(), '--dry-run no debe registrar');
    }

    /** @test */
    public function reset_records_an_audit_log(): void
    {
        $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true, '--by' => 'andres']);

        $log = DB::table('inventory_reset_logs')->first();

        $this->assertNotNull($log);
        $this->assertSame('all', $log->scope);
        $this->assertSame('andres', $log->executed_by);
        $this->assertNotNull($log->counts);
    }
}
