<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugTransferProducts extends Command
{
    protected $signature = 'debug:transfer-products {warehouseId}';
    protected $description = 'Debug por qué no se muestran productos para una bodega';

    public function handle()
    {
        $warehouseId = (int) $this->argument('warehouseId');
        
        $this->info("=== DEBUG: Bodega ID {$warehouseId} ===\n");
        
        // 1. Verificar que la bodega existe
        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse) {
            $this->error("❌ La bodega con ID {$warehouseId} NO existe");
            return 1;
        }
        $this->info("✓ Bodega encontrada: {$warehouse->nombre}");
        $this->info("  - Ciudad: {$warehouse->ciudad}");
        $this->info("  - Nombre: {$warehouse->nombre}\n");
        
        // 2. Verificar si está en getBodegasQueRecibenContenedores
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        $this->info("Bodegas que reciben contenedores (IDs): " . implode(', ', $bodegasQueRecibenContenedores));
        
        $enLista = in_array($warehouseId, $bodegasQueRecibenContenedores);
        if ($enLista) {
            $this->info("✓ La bodega {$warehouseId} SÍ está en la lista de bodegas que reciben contenedores\n");
        } else {
            $this->warn("⚠ La bodega {$warehouseId} NO está en la lista de bodegas que reciben contenedores");
            $this->warn("  Para que aparezca debe tener:");
            $this->warn("  - id = 1, O");
            $this->warn("  - nombre LIKE '%Pablo Rojas%', O");
            $this->warn("  - nombre LIKE '%Buenaventura%', O");
            $this->warn("  - ciudad LIKE '%Buenaventura%'\n");
        }
        
        // 3. Ver contenedores en esta bodega
        $containers = DB::table('containers')
            ->where('warehouse_id', $warehouseId)
            ->get();
        $this->info("Contenedores en esta bodega: " . $containers->count());
        
        // 4. Ver container_product con boxes > 0
        $containerProducts = DB::table('container_product')
            ->join('containers', 'container_product.container_id', '=', 'containers.id')
            ->where('containers.warehouse_id', $warehouseId)
            ->where('container_product.boxes', '>', 0)
            ->select('container_product.*', 'containers.reference')
            ->get();
        
        $this->info("Filas en container_product con boxes > 0: " . $containerProducts->count() . "\n");
        
        if ($containerProducts->count() > 0) {
            $this->info("Primeros 5 productos con stock:");
            foreach ($containerProducts->take(5) as $cp) {
                $product = Product::find($cp->product_id);
                $this->line("  - Producto #{$cp->product_id}: " . ($product ? $product->nombre : 'N/A') . 
                    " | Contenedor: {$cp->reference} | Cajas: {$cp->boxes} | Láminas/caja: {$cp->sheets_per_box}");
            }
        } else {
            $this->warn("⚠ No hay productos con stock (boxes > 0) en los contenedores de esta bodega");
        }
        
        $this->info("\n=== FIN DEBUG ===");
        return 0;
    }
}
