<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 004 — Gastos mensuales por conductor/placa (costos fijos).
 * Registro independiente de las liquidaciones por viaje. Un registro por
 * conductor por período (mes/año).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->string('vehicle_plate', 20)->nullable();
            $table->smallInteger('anio')->unsigned();
            $table->tinyInteger('mes')->unsigned();
            $table->decimal('sueldo_conductor', 12, 0)->default(0);
            $table->decimal('seguridad_social', 12, 0)->default(0);
            $table->decimal('cuota_banco', 12, 0)->default(0);
            $table->decimal('cuota_tercero', 12, 0)->default(0);
            $table->decimal('satelital', 12, 0)->default(0);
            $table->decimal('seguro_vehiculo', 12, 0)->default(0);
            $table->decimal('otro_valor', 12, 0)->default(0);
            $table->string('otro_descripcion', 150)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();

            $table->unique(['driver_id', 'anio', 'mes'], 'uq_monthly_driver_period');
            $table->index('vehicle_plate');
            $table->index(['anio', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_expenses');
    }
};
