<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clean {--force : Ejecutar sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia la base de datos eliminando todos los datos excepto los usuarios';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('¿Estás seguro de que deseas limpiar la base de datos? Esta acción NO se puede deshacer.')) {
                $this->info('Operación cancelada.');
                return 0;
            }
        }

        $this->info('Iniciando limpieza de la base de datos...');
        
        try {
            DB::beginTransaction();

            // Desactivar verificación de claves foráneas temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Limpiar tablas en orden (primero las tablas pivot, luego las principales)
            $tables = [
                'salida_products',
                'salidas',
                'transfer_order_products',
                'transfer_orders',
                'container_product',
                'containers',
                'products',
                'drivers',
            ];

            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    $count = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->info("✓ Tabla '{$table}' limpiada ({$count} registros eliminados)");
                } else {
                    $this->warn("⚠ Tabla '{$table}' no existe, se omite");
                }
            }

            // Reactivar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            DB::commit();

            $this->info('');
            $this->info('✓ Base de datos limpiada exitosamente.');
            $this->info('✓ Los usuarios se han mantenido intactos.');
            
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
            $this->error('Error al limpiar la base de datos: ' . $e->getMessage());
            return 1;
        }
    }
}
