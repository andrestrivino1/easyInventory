<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiquidacionStateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('liquidacion_state_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('liquidaciones')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('from_state', ['borrador', 'cerrada', 'anulada']);
            $table->enum('to_state', ['borrador', 'cerrada', 'anulada']);
            $table->text('motivo')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['liquidacion_id', 'created_at'], 'idx_state_logs');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidacion_state_logs');
    }
}
