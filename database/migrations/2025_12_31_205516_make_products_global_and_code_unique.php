<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeProductsGlobalAndCodeUnique extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar la restricción UNIQUE compuesta de codigo y almacen_id
            $table->dropUnique('products_codigo_almacen_unique');
            
            // Hacer el código único globalmente (sin almacen_id)
            // Primero debemos asegurarnos de que no haya códigos duplicados
            // Los productos ahora serán globales (almacen_id = null)
        });
        
        // Establecer almacen_id como null para todos los productos existentes
        // para convertirlos en productos globales
        DB::table('products')->update(['almacen_id' => null]);
        
        Schema::table('products', function (Blueprint $table) {
            // Agregar restricción UNIQUE solo en codigo
            $table->unique('codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar la restricción UNIQUE de codigo
            $table->dropUnique(['codigo']);
            
            // Restaurar la restricción UNIQUE compuesta de codigo y almacen_id
            $table->unique(['codigo', 'almacen_id'], 'products_codigo_almacen_unique');
        });
    }
}
