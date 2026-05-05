<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriversTable extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identity', 20);
            $table->string('phone', 20)->nullable();
            $table->string('vehicle_plate', 20);
            $table->boolean('active')->default(true); // true = activo, false = inactivo
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
}
