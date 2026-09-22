<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de cada ejecución del reinicio de inventario (feature 008).
 *
 * Convierte una operación destructiva y poco frecuente en un hecho auditable:
 * sin esto, la única evidencia de lo ocurrido serían las filas archivadas,
 * sin contexto de cuándo, quién ni con qué alcance.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_reset_logs')) {
            return;
        }

        Schema::create('inventory_reset_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('batch_id', 36)->unique();
            $table->timestamp('executed_at')->nullable();
            $table->string('executed_by')->nullable();
            $table->string('scope', 50);
            $table->json('counts')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reset_logs');
    }
};
