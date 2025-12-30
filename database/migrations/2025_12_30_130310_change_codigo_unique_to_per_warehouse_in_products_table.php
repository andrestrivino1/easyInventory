<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCodigoUniqueToPerWarehouseInProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar la restricción UNIQUE del campo codigo
            $table->dropUnique(['codigo']);
            // Agregar restricción UNIQUE compuesta de codigo y almacen_id
            // Esto permite que el mismo código exista en diferentes bodegas
            $table->unique(['codigo', 'almacen_id'], 'products_codigo_almacen_unique');
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
            // Eliminar la restricción UNIQUE compuesta
            $table->dropUnique('products_codigo_almacen_unique');
            // Restaurar la restricción UNIQUE solo en codigo
            $table->unique('codigo');
        });
    }
}
