<?php

namespace App\Console\Commands;

use App\Models\Import;
use App\Services\InventoryResetService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Limpieza de arranque de la operación (funcionalidad 008).
 *
 * Deliberadamente NO se expone en la interfaz web (FR-009): exigir acceso a
 * consola es la barrera adecuada para una acción de este calibre.
 *
 * No se reutiliza `db:clean` porque aquel hace truncate irreversible y además
 * borra productos, conductores y transportistas, que aquí deben conservarse.
 */
class InventoryReset extends Command
{
    protected $signature = 'inventory:reset
        {--scope=all : Qué limpiar: inventory, imports o all}
        {--force : Omite la confirmación interactiva}
        {--dry-run : Informa qué se borraría sin borrar nada}
        {--by= : Identificador de quién ejecuta, para el registro de auditoría}';

    protected $description = 'Vacía stock, trazabilidad y/o importaciones dejando la información recuperable.';

    public function handle(InventoryResetService $service): int
    {
        $scope = (string) $this->option('scope');

        if (! in_array($scope, ['inventory', 'imports', 'all'], true)) {
            $this->error("Ámbito no válido: '{$scope}'. Use inventory, imports o all.");

            return 1;
        }

        $preview = $service->preview($scope);
        $this->renderPreview($preview, $service->preservedCounts());

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('  Ensayo (--dry-run): no se ha escrito nada.');

            return 0;
        }

        if (! $this->option('force') && ! $this->confirmDestruction($preview)) {
            $this->info('  Operación cancelada.');

            return 0;
        }

        try {
            $result = $service->run($scope, $this->option('by'));
        } catch (Throwable $e) {
            $this->error('  Falló la limpieza, no se borró nada: ' . $e->getMessage());

            return 1;
        }

        $this->renderResult($result, $scope);

        return 0;
    }

    private function renderPreview(array $preview, array $preserved): void
    {
        $this->newLine();
        $this->line('  <comment>Se verán afectadas:</comment>');

        if ($preview === [] || array_sum($preview) === 0) {
            $this->line('    (nada: el sistema ya está vacío)');
        } else {
            foreach ($preview as $table => $rows) {
                $this->line(sprintf('    %-28s %6s filas', $table, number_format($rows)));
            }
        }

        $this->newLine();
        $this->line('  <comment>Se conservan intactos:</comment>');
        foreach ($preserved as $table => $rows) {
            $this->line(sprintf('    %-28s %6s filas', $table, number_format($rows)));
        }
    }

    private function confirmDestruction(array $preview): bool
    {
        $total = array_sum($preview);

        $this->newLine();

        return $this->confirm(
            "¿Confirma la limpieza de {$total} registros? La información quedará archivada y es recuperable.",
            false
        );
    }

    private function renderResult(array $result, string $scope): void
    {
        $this->newLine();
        $this->info('  Lote: ' . $result['batch_id']);
        $this->newLine();

        foreach ($result['counts'] as $table => $rows) {
            $action = $table === 'imports' ? 'borrado suave' : 'archivadas y borradas';
            $this->line(sprintf('    %-28s %6s filas  → %s', $table, number_format($rows), $action));
        }

        // Confirmar en pantalla que la numeración continúa, en lugar de dejar
        // que se descubra al crear la siguiente importación (FR-014).
        if (in_array($scope, ['imports', 'all'], true)) {
            $this->newLine();
            $this->line('  Consecutivo DO: el siguiente número será <info>' . Import::nextDoCode() . '</info>');
        }

        $this->newLine();
        $this->info('  Para deshacer: php artisan inventory:reset-restore ' . $result['batch_id']);
    }
}
