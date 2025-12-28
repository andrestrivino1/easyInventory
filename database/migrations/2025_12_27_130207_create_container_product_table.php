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
            $table->unique(['container_id', 'product_id']);
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
