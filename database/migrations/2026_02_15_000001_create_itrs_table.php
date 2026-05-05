<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('itrs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id')->nullable()->comment('Importación que generó este ITR al confirmar arribo');
            $table->string('do_code', 100)->comment('DO');
            $table->string('bl_number', 100)->nullable()->comment('No Bill of Lading');
            $table->date('fecha_llegada')->comment('Fecha de llegada');
            $table->unsignedTinyInteger('dias_libres')->default(4)->comment('Días libres para calcular vencimiento');
            $table->date('fecha_vencimiento')->comment('Fecha de vencimiento = fecha_llegada + dias_libres');
            $table->date('fecha_retiro_contenedor')->nullable();
            $table->date('fecha_vaciado_contenedor')->nullable();
            $table->date('fecha_devolucion_contenedor')->nullable();
            $table->string('evidencia_tiquete_retiro_pdf')->nullable();
            $table->string('evidencia_tiquete_devolucion_pdf')->nullable();
            $table->string('evidencia_fotos_pdf')->nullable();
            $table->timestamps();

            $table->foreign('import_id')->references('id')->on('imports')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itrs');
    }
};
