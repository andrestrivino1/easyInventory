<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MakeAlmacenIdNullableAndTipoMedidaOptionalInProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modificar almacen_id para que sea nullable
        DB::statement('ALTER TABLE products MODIFY almacen_id BIGINT UNSIGNED NULL');
        
        // Modificar tipo_medida para que sea nullable
        DB::statement('ALTER TABLE products MODIFY tipo_medida VARCHAR(255) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir tipo_medida a NOT NULL (pero necesitamos valores por defecto primero)
        // Primero actualizar los nulls a un valor por defecto
        DB::statement("UPDATE products SET tipo_medida = 'unidad' WHERE tipo_medida IS NULL");
        DB::statement('ALTER TABLE products MODIFY tipo_medida VARCHAR(255) NOT NULL');
        
        // almacen_id puede seguir siendo nullable
    }
}
