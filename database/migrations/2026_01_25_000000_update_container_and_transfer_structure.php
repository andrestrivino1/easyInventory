<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContainerAndTransferStructure extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Eliminar restricción única de container_product para permitir múltiples entradas del mismo producto
        Schema::table('container_product', function (Blueprint $table) {
            // Agregar índice normal primero para no romper la FK de container_id
            $table->index('container_id', 'container_product_container_id_index');
            $table->dropUnique('unique_container_product');
        });

        // 2. Agregar sheets_per_box a transfer_order_products
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->integer('sheets_per_box')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->dropColumn('sheets_per_box');
        });

        Schema::table('container_product', function (Blueprint $table) {
            // Nota: Esto fallará si existen duplicados
            $table->unique(['container_id', 'product_id']);
        });
    }
}
