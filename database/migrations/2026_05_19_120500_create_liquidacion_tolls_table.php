<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiquidacionTollsTable extends Migration
{
    public function up()
    {
        Schema::create('liquidacion_tolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('liquidaciones')->cascadeOnDelete();
            $table->foreignId('route_toll_id')->nullable()->constrained('liquidacion_route_tolls')->nullOnDelete();
            $table->string('name', 100);
            $table->decimal('valor', 12, 0)->default(0);
            $table->unsignedSmallInteger('sort_order');
            $table->enum('direction', ['ida', 'regreso'])->default('ida');
            $table->boolean('is_adhoc')->default(false);
            $table->boolean('is_used')->default(true);
            $table->timestamps();
            $table->index(['liquidacion_id', 'sort_order'], 'idx_liq_tolls_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidacion_tolls');
    }
}
