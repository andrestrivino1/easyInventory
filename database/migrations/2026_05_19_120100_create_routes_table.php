<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    public function up()
    {
        Schema::create('liquidacion_routes', function (Blueprint $table) {
            $table->id();
            $table->string('origen', 100);
            $table->string('destino', 100);
            $table->string('name', 255);
            $table->text('descripcion')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('active');
            $table->index('name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidacion_routes');
    }
}
