<?php

namespace App\Services;

use App\Models\Import;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reinicio de arranque de la operación (funcionalidad 008).
 *
 * Dos mecanismos distintos para una misma propiedad de negocio —que lo borrado
 * siga siendo recuperable— porque cada módulo tiene un coste distinto:
 *
 *  - INVENTARIO: se archiva y se borra. No se retrofitea `deleted_at` porque las
 *    rutas de lectura de stock y trazabilidad tienen 33 consultas crudas
 *    `DB::table()` que no aplican los scopes de Eloquent; un solo `whereNull`
 *    olvidado produciría stock fantasma silencioso. El archivo conserva los
 *    datos sin tocar ni una consulta de lectura.
 *
 *  - IMPORTACIONES: se usa el `SoftDeletes` nativo, que ya existe y que todas
 *    las consultas del módulo respetan. Además es lo que permite que el
 *    consecutivo del DO continúe solo, porque su cálculo usa `withTrashed()`.
 */
class InventoryResetService
{
    /**
     * Tablas de inventario en orden de BORRADO: de hija a madre, para no
     * violar las claves foráneas. La restauración recorre el orden inverso.
     */
    public const INVENTORY_TABLES = [
        'salida_products',
        'salidas',
        'transfer_order_products',
        'transfer_orders',
        'container_product',
        'containers',
    ];

    public const SCOPE_INVENTORY = 'inventory';
    public const SCOPE_IMPORTS = 'imports';
    public const SCOPE_ALL = 'all';

    /** Devuelve cuántas filas afectaría el reinicio, sin tocar nada. */
    public function preview(string $scope): array
    {
        $counts = [];

        if ($this->coversInventory($scope)) {
            foreach (self::INVENTORY_TABLES as $table) {
                $counts[$table] = DB::table($table)->count();
            }
        }

        if ($this->coversImports($scope)) {
            $counts['imports'] = Import::count();
        }

        return $counts;
    }

    /** Recuentos de los datos maestros que NO se tocan (FR-004, FR-005). */
    public function preservedCounts(): array
    {
        $preserved = [];

        foreach (['products', 'warehouses', 'drivers', 'users', 'itrs', 'liquidaciones'] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                $preserved[$table] = DB::table($table)->count();
            }
        }

        return $preserved;
    }

    /**
     * Ejecuta el reinicio dentro de una única transacción: si algo falla, no
     * queda un estado a medio limpiar.
     *
     * @return array{batch_id:string, counts:array<string,int>}
     */
    public function run(string $scope, ?string $executedBy = null): array
    {
        $batchId = (string) Str::uuid();
        $counts = [];

        DB::transaction(function () use ($scope, $batchId, $executedBy, &$counts) {
            if ($this->coversInventory($scope)) {
                $counts = array_merge($counts, $this->resetInventory($batchId));
            }

            if ($this->coversImports($scope)) {
                $counts['imports'] = $this->resetImports();
            }

            DB::table('inventory_reset_logs')->insert([
                'batch_id' => $batchId,
                'executed_at' => now(),
                'executed_by' => $executedBy,
                'scope' => $scope,
                'counts' => json_encode($counts),
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return ['batch_id' => $batchId, 'counts' => $counts];
    }

    /**
     * Archiva y borra las seis fuentes de stock y trazabilidad.
     *
     * @return array<string,int>
     */
    private function resetInventory(string $batchId): array
    {
        $counts = [];

        foreach (self::INVENTORY_TABLES as $table) {
            $archive = 'archived_' . $table;
            $rows = DB::table($table)->count();

            if ($rows > 0) {
                $this->archiveTable($table, $archive, $batchId);
                DB::table($table)->delete();
            }

            $counts[$table] = $rows;
        }

        return $counts;
    }

    /**
     * Copia una tabla completa a su archivo conservando los valores originales.
     *
     * Se hace con INSERT ... SELECT para no traer las filas a memoria: la
     * columna `archive_id` es autoincremental y no se nombra, así que se
     * enumeran explícitamente las columnas de origen.
     */
    private function archiveTable(string $table, string $archive, string $batchId): void
    {
        $columns = $this->columnsOf($table);

        if ($columns === []) {
            throw new RuntimeException("No se pudieron leer las columnas de {$table}");
        }

        $quoted = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

        DB::statement(
            "INSERT INTO `{$archive}` ({$quoted}, `archived_at`, `archive_batch_id`)
             SELECT {$quoted}, ?, ? FROM `{$table}`",
            [now(), $batchId]
        );
    }

    /** Borrado suave de las importaciones (FR-012). */
    private function resetImports(): int
    {
        $count = Import::count();

        if ($count > 0) {
            // delete() sobre el modelo aplica SoftDeletes; no se usa truncate
            // ni borrado físico porque el consecutivo del DO depende de que
            // las filas sigan existiendo (withTrashed).
            Import::query()->delete();
        }

        return $count;
    }

    /**
     * Repone un lote archivado.
     *
     * @return array{scope:string, counts:array<string,int>}
     */
    public function restore(string $batchId, bool $force = false): array
    {
        $log = DB::table('inventory_reset_logs')->where('batch_id', $batchId)->first();

        if (! $log) {
            throw new RuntimeException("No existe ningún lote con el identificador {$batchId}.");
        }

        if (! $force) {
            $this->assertLiveTablesAreEmpty($log->scope);
        }

        $counts = [];

        DB::transaction(function () use ($batchId, $log, &$counts) {
            if ($this->coversInventory($log->scope)) {
                // Orden inverso al borrado: de madre a hija.
                foreach (array_reverse(self::INVENTORY_TABLES) as $table) {
                    $counts[$table] = $this->restoreTable($table, 'archived_' . $table, $batchId);
                }
            }

            if ($this->coversImports($log->scope)) {
                $counts['imports'] = Import::onlyTrashed()->restore();
            }
        });

        return ['scope' => $log->scope, 'counts' => $counts];
    }

    /** Repone una tabla desde su archivo y limpia el lote ya restaurado. */
    private function restoreTable(string $table, string $archive, string $batchId): int
    {
        $columns = $this->columnsOf($table);
        $quoted = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

        $rows = DB::table($archive)->where('archive_batch_id', $batchId)->count();

        if ($rows === 0) {
            return 0;
        }

        DB::statement(
            "INSERT INTO `{$table}` ({$quoted})
             SELECT {$quoted} FROM `{$archive}` WHERE `archive_batch_id` = ?",
            [$batchId]
        );

        DB::table($archive)->where('archive_batch_id', $batchId)->delete();

        return $rows;
    }

    /**
     * Motivo por el que un lote no puede restaurarse, o null si sí puede.
     *
     * Se expone para que el comando avise ANTES de pedir confirmación: no tiene
     * sentido hacer confirmar una operación que va a rechazarse.
     */
    public function restoreBlocker(string $batchId, bool $force = false): ?string
    {
        $log = DB::table('inventory_reset_logs')->where('batch_id', $batchId)->first();

        if (! $log) {
            return "No existe ningún lote con el identificador {$batchId}.";
        }

        if ($force) {
            return null;
        }

        try {
            $this->assertLiveTablesAreEmpty($log->scope);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * Reponer sobre datos nuevos duplicaría identificadores y descuadraría los
     * saldos, así que se exige que las tablas vivas estén vacías.
     */
    private function assertLiveTablesAreEmpty(string $scope): void
    {
        if (! $this->coversInventory($scope)) {
            return;
        }

        foreach (self::INVENTORY_TABLES as $table) {
            if (DB::table($table)->count() > 0) {
                throw new RuntimeException(
                    "La tabla {$table} ya tiene datos nuevos. Restaurar encima duplicaría "
                    . 'identificadores. Use --force sólo si está seguro.'
                );
            }
        }
    }

    /** Columnas reales de una tabla, en su orden de declaración. */
    private function columnsOf(string $table): array
    {
        return DB::getSchemaBuilder()->getColumnListing($table);
    }

    private function coversInventory(string $scope): bool
    {
        return in_array($scope, [self::SCOPE_INVENTORY, self::SCOPE_ALL], true);
    }

    private function coversImports(string $scope): bool
    {
        return in_array($scope, [self::SCOPE_IMPORTS, self::SCOPE_ALL], true);
    }
}
