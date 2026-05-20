<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiquidacionesTable extends Migration
{
    public function up()
    {
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->string('vehicle_plate', 20);
            $table->foreignId('route_id')->nullable()->constrained('liquidacion_routes')->restrictOnDelete();
            $table->string('transportadora', 150);
            $table->string('telefono_empresa', 40)->nullable();
            $table->decimal('anticipo', 12, 0)->default(0);
            $table->decimal('sobreanticipo', 12, 0)->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('numero_mfto', 60)->nullable();
            $table->decimal('valor_flete', 12, 0)->default(0);
            $table->enum('estado', ['borrador', 'cerrada', 'anulada'])->default('borrador');
            $table->text('motivo_anulacion')->nullable();
            // Stored caches (recalculados en cada save)
            $table->decimal('sumatoria_gastos_operativos', 12, 0)->default(0);
            $table->decimal('sumatoria_peajes', 12, 0)->default(0);
            $table->decimal('sumatoria_gastos_totales', 12, 0)->default(0);
            $table->decimal('total_anticipos', 12, 0)->default(0);
            $table->decimal('saldo_viaje', 12, 0)->default(0);
            $table->decimal('ganancia_viaje', 12, 0)->default(0);
            $table->enum('a_favor_de', ['empresa', 'conductor', 'ninguno'])->default('ninguno');
            // Auditoría
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            // Índices
            $table->index('fecha_inicio');
            $table->index('vehicle_plate');
            $table->index('estado');
            $table->index(['fecha_inicio', 'estado', 'deleted_at'], 'idx_liq_listado');
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidaciones');
    }
}
