<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteTollsTable extends Migration
{
    public function up()
    {
        Schema::create('liquidacion_route_tolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('liquidacion_routes')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('suggested_value', 12, 0)->default(0);
            $table->unsignedSmallInteger('sort_order');
            $table->enum('direction', ['ida', 'regreso'])->default('ida');
            $table->timestamps();
            $table->index(['route_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidacion_route_tolls');
    }
}
