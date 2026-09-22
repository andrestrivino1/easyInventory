<?php

namespace App\Console\Commands;

use App\Services\InventoryResetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Contrapartida de inventory:reset — es lo que hace real la promesa de que el
 * borrado es recuperable y no una destrucción definitiva.
 */
class InventoryResetRestore extends Command
{
    protected $signature = 'inventory:reset-restore
        {batch_id : Identificador del lote a reponer}
        {--force : Repone aunque las tablas vivas ya tengan datos}
        {--dry-run : Informa qué se repondría sin escribir nada}';

    protected $description = 'Deshace una ejecución de inventory:reset reponiendo las filas archivadas.';

    public function handle(InventoryResetService $service): int
    {
        $batchId = (string) $this->argument('batch_id');

        $log = DB::table('inventory_reset_logs')->where('batch_id', $batchId)->first();

        if (! $log) {
            $this->error("  No existe ningún lote con el identificador {$batchId}.");

            return 1;
        }

        $this->newLine();
        $this->line('  Lote:     ' . $log->batch_id);
        $this->line('  Ámbito:   ' . $log->scope);
        $this->line('  Ejecutado: ' . $log->executed_at . ($log->executed_by ? ' por ' . $log->executed_by : ''));
        $this->newLine();

        $counts = json_decode($log->counts ?? '[]', true) ?: [];
        foreach ($counts as $table => $rows) {
            $this->line(sprintf('    %-28s %6s filas', $table, number_format($rows)));
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('  Ensayo (--dry-run): no se ha repuesto nada.');

            return 0;
        }

        // Comprobar antes de preguntar: no tiene sentido hacer confirmar una
        // operación que de todos modos se va a rechazar.
        $blocker = $service->restoreBlocker($batchId, (bool) $this->option('force'));

        if ($blocker !== null) {
            $this->error('  No se pudo restaurar: ' . $blocker);

            return 1;
        }

        if (! $this->option('force') && ! $this->confirm('¿Confirma la restauración de este lote?', false)) {
            $this->info('  Operación cancelada.');

            return 0;
        }

        try {
            $result = $service->restore($batchId, (bool) $this->option('force'));
        } catch (Throwable $e) {
            $this->error('  No se pudo restaurar: ' . $e->getMessage());

            return 1;
        }

        $this->newLine();
        foreach ($result['counts'] as $table => $rows) {
            $this->line(sprintf('    %-28s %6s filas repuestas', $table, number_format($rows)));
        }

        $this->newLine();
        $this->info('  Restauración completada.');

        return 0;
    }
}
