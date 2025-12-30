<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalidasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salidas', function (Blueprint $table) {
            $table->id();
            $table->string('salida_number')->unique();
            $table->unsignedBigInteger('warehouse_id');
            $table->date('fecha');
            $table->string('a_nombre_de');
            $table->string('nit_cedula');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
        });

        Schema::create('salida_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salida_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('container_id')->nullable();
            $table->integer('quantity');
            $table->timestamps();
            $table->foreign('salida_id')->references('id')->on('salidas')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('container_id')->references('id')->on('containers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('salida_products');
        Schema::dropIfExists('salidas');
    }
}
