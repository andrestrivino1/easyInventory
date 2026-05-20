<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVehicleTypeToLiquidacionRoutes extends Migration
{
    public function up()
    {
        Schema::table('liquidacion_routes', function (Blueprint $table) {
            $table->string('vehicle_type', 30)->nullable()->after('destino');
            $table->index('vehicle_type');
        });
    }

    public function down()
    {
        Schema::table('liquidacion_routes', function (Blueprint $table) {
            $table->dropIndex(['vehicle_type']);
            $table->dropColumn('vehicle_type');
        });
    }
}
