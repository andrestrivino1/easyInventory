<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaidByToLiquidacionTolls extends Migration
{
    public function up()
    {
        // Quién paga cada peaje: empresa (GoPass) por defecto, o el conductor.
        Schema::table('liquidacion_tolls', function (Blueprint $table) {
            $table->enum('paid_by', ['empresa', 'conductor'])->default('empresa')->after('is_used');
        });

        // Total de peajes pagados por el conductor (subconjunto usado de sumatoria_peajes).
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('sumatoria_peajes_conductor', 12, 0)->default(0)->after('sumatoria_peajes');
        });
    }

    public function down()
    {
        Schema::table('liquidacion_tolls', function (Blueprint $table) {
            $table->dropColumn('paid_by');
        });

        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn('sumatoria_peajes_conductor');
        });
    }
}
