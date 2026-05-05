<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveContainerFieldsFromProductsAndContainers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Eliminar container_id de products si existe
        if (Schema::hasColumn('products', 'container_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['container_id']);
                $table->dropColumn('container_id');
            });
        }
        
        // Eliminar campos de containers que ahora están en la tabla pivot
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'boxes', 'sheets_per_box']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Restaurar container_id en products
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('container_id')->nullable()->after('almacen_id');
            $table->foreign('container_id')->references('id')->on('containers')->restrictOnDelete();
        });
        
        // Restaurar campos en containers
        Schema::table('containers', function (Blueprint $table) {
            $table->string('product_name')->after('reference');
            $table->integer('boxes')->after('product_name');
            $table->integer('sheets_per_box')->after('boxes');
        });
    }
}
