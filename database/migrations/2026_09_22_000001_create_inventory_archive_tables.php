<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas de archivo para el reinicio de inventario (feature 008).
 *
 * Cada tabla de archivo replica la estructura de su tabla origen mediante
 * CREATE TABLE ... LIKE, que copia columnas e índices pero NO las claves
 * foráneas: el archivo debe ser un depósito inerte, no puede exigir que
 * existan registros vivos que justamente acabamos de borrar.
 *
 * Después se retiran todos los índices heredados porque impedirían archivar
 * dos veces la misma fila:
 *   - el AUTO_INCREMENT y la PRIMARY KEY sobre `id` (queremos conservar el id
 *     original tal cual, como columna ordinaria, para poder restaurar)
 *   - los índices UNIQUE (containers.reference, salidas.salida_number,
 *     transfer_orders.order_number) que colisionarían entre lotes
 *
 * En su lugar se añade una clave propia del archivo y las dos columnas de
 * control: archived_at y archive_batch_id.
 */
return new class extends Migration
{
    /** Tablas de inventario que se archivan, en el orden en que se documentan. */
    private const TABLES = [
        'containers',
        'container_product',
        'transfer_orders',
        'transfer_order_products',
        'salidas',
        'salida_products',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $source) {
            $archive = 'archived_' . $source;

            if (! Schema::hasTable($source) || Schema::hasTable($archive)) {
                continue;
            }

            DB::statement("CREATE TABLE `{$archive}` LIKE `{$source}`");

            $this->stripInheritedKeys($archive);
            $this->addArchiveColumns($archive);
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $source) {
            Schema::dropIfExists('archived_' . $source);
        }
    }

    /**
     * Retira el AUTO_INCREMENT, la PRIMARY KEY y todos los índices heredados,
     * dejando las columnas como datos planos.
     */
    private function stripInheritedKeys(string $archive): void
    {
        // El AUTO_INCREMENT debe desaparecer antes de poder soltar la PRIMARY KEY.
        $idColumn = DB::selectOne(
            'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$archive, 'id']
        );

        if ($idColumn) {
            DB::statement("ALTER TABLE `{$archive}` MODIFY `id` {$idColumn->COLUMN_TYPE} NOT NULL");
        }

        $indexes = DB::select("SHOW INDEX FROM `{$archive}`");
        $names = array_unique(array_map(fn ($i) => $i->Key_name, $indexes));

        foreach ($names as $name) {
            if ($name === 'PRIMARY') {
                DB::statement("ALTER TABLE `{$archive}` DROP PRIMARY KEY");
                continue;
            }

            DB::statement("ALTER TABLE `{$archive}` DROP INDEX `{$name}`");
        }
    }

    /** Añade la clave propia del archivo y las columnas de control. */
    private function addArchiveColumns(string $archive): void
    {
        DB::statement(
            "ALTER TABLE `{$archive}`
                ADD COLUMN `archive_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
                ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT NULL,
                ADD COLUMN `archive_batch_id` CHAR(36) NOT NULL DEFAULT '',
                ADD PRIMARY KEY (`archive_id`),
                ADD INDEX `{$archive}_batch_idx` (`archive_batch_id`),
                ADD INDEX `{$archive}_orig_id_idx` (`id`)"
        );
    }
};
