<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('itr_date_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itr_id');
            $table->string('field_name', 80)->comment('fecha_retiro_contenedor, fecha_vaciado_contenedor, fecha_devolucion_contenedor');
            $table->string('old_value', 50)->nullable();
            $table->string('new_value', 50)->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('itr_id')->references('id')->on('itrs')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itr_date_histories');
    }
};
