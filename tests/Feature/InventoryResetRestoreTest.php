<?php

namespace Tests\Feature;

use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * US1 — La recuperabilidad tiene que ser real, no una promesa.
 *
 * FR-006, FR-012.
 */
class InventoryResetRestoreTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    private const PAIRS = [
        'containers' => 'archived_containers',
        'container_product' => 'archived_container_product',
        'transfer_orders' => 'archived_transfer_orders',
        'transfer_order_products' => 'archived_transfer_order_products',
        'salidas' => 'archived_salidas',
        'salida_products' => 'archived_salida_products',
    ];

    /** @test */
    public function every_deleted_row_lands_in_its_archive_table(): void
    {
        $this->seedFullInventory();

        $before = [];
        foreach (self::PAIRS as $live => $archive) {
            $before[$live] = DB::table($live)->count();
        }

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);

        foreach (self::PAIRS as $live => $archive) {
            $this->assertSame(
                $before[$live],
                DB::table($archive)->count(),
                "FR-006: {$archive} debe conservar todas las filas de {$live}"
            );
        }
    }

    /** @test */
    public function archived_rows_share_one_batch_id(): void
    {
        $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);

        $batches = DB::table('archived_containers')->distinct()->pluck('archive_batch_id');

        $this->assertCount(1, $batches);
        $this->assertNotSame('', $batches->first());

        $log = DB::table('inventory_reset_logs')->first();
        $this->assertSame($log->batch_id, $batches->first(), 'El lote archivado debe corresponder al registro');
    }

    /** @test */
    public function restore_brings_back_rows_with_original_ids(): void
    {
        $data = $this->seedFullInventory();
        $originalContainerIds = DB::table('containers')->orderBy('id')->pluck('id')->all();
        $originalRowCounts = [];
        foreach (self::PAIRS as $live => $archive) {
            $originalRowCounts[$live] = DB::table($live)->count();
        }

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);
        $batchId = DB::table('inventory_reset_logs')->value('batch_id');

        $this->artisan('inventory:reset-restore', ['batch_id' => $batchId, '--force' => true])
            ->assertExitCode(0);

        $restoredIds = DB::table('containers')->orderBy('id')->pluck('id')->all();

        $this->assertSame($originalContainerIds, $restoredIds, 'Los id originales deben reponerse tal cual');

        foreach (self::PAIRS as $live => $archive) {
            $this->assertSame($originalRowCounts[$live], DB::table($live)->count(), "{$live} repuesta");
        }
    }

    /** @test */
    public function restore_rejects_an_unknown_batch(): void
    {
        $this->artisan('inventory:reset-restore', [
            'batch_id' => '00000000-0000-0000-0000-000000000000',
            '--force' => true,
        ])->assertExitCode(1);
    }

    /** @test */
    public function restore_refuses_when_live_tables_are_not_empty(): void
    {
        $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);
        $batchId = DB::table('inventory_reset_logs')->value('batch_id');

        // Alguien vuelve a operar después de la limpieza.
        $origin = $this->containerWarehouse('Bodega Nueva');
        $this->container('CONT-NUEVO', $origin->id);

        $this->artisan('inventory:reset-restore', ['batch_id' => $batchId])
            ->assertExitCode(1);
    }

    /** @test */
    public function imports_are_soft_deleted_and_recoverable(): void
    {
        $this->import('VJP26-050');
        $this->import('VJP26-051');

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true]);

        $this->assertSame(0, Import::count(), 'FR-011: ninguna visible');
        $this->assertSame(2, Import::withTrashed()->count(), 'FR-012: conservadas y recuperables');

        $batchId = DB::table('inventory_reset_logs')->value('batch_id');
        $this->artisan('inventory:reset-restore', ['batch_id' => $batchId, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(2, Import::count(), 'Restauradas');
    }

    /** @test */
    public function restore_dry_run_writes_nothing(): void
    {
        $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'inventory', '--force' => true]);
        $batchId = DB::table('inventory_reset_logs')->value('batch_id');

        $this->artisan('inventory:reset-restore', ['batch_id' => $batchId, '--dry-run' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('containers')->count(), '--dry-run no debe reponer nada');
    }
}
