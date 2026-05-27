<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContainerProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('container_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('container_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('boxes')->default(0);
            $table->integer('sheets_per_box')->default(0);
            $table->timestamps();
            
            $table->foreign('container_id')->references('id')->on('containers')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // Nombre explícito: una migración posterior (2026_01_25) hace
            // dropUnique('unique_container_product'); sin este nombre, migrate:fresh
            // falla en entornos nuevos / tests.
            $table->unique(['container_id', 'product_id'], 'unique_container_product');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('container_product');
    }
}
