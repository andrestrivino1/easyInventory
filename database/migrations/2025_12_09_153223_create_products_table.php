<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->string('nombre');
            // codigo arranca con índice UNIQUE (products_codigo_unique); migraciones
            // posteriores lo cambian a (codigo, almacen_id) y luego de vuelta a codigo.
            // Sin este unique, change_codigo_unique_to_per_warehouse falla al dropUnique
            // en migrate:fresh (entornos nuevos / tests).
            $table->string('codigo')->unique();
            $table->decimal('precio', 12, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('estado')->default(true); // 1=activo, 0=inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
