<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTransferOrdersDriverRelation extends Migration
{
    public function up()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('transfer_orders', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable()->after('note');
                $table->foreign('driver_id')->references('id')->on('drivers')->nullOnDelete();
            }
            // Elimina columnas antiguas si existen
            if (Schema::hasColumn('transfer_orders', 'driver_name')) {
                $table->dropColumn(['driver_name']);
            }
            if (Schema::hasColumn('transfer_orders', 'driver_id') && Schema::hasColumn('transfer_orders', 'vehicle_plate')) {
                // Si existen ambas columnas viejas
                $table->dropColumn(['vehicle_plate']);
            } elseif (Schema::hasColumn('transfer_orders', 'vehicle_plate')) {
                $table->dropColumn(['vehicle_plate']);
            }
            if (Schema::hasColumn('transfer_orders', 'driver_id') && !Schema::hasColumn('transfer_orders', 'driver_id')) {
                // Nada, ya está manejado
            }
        });
    }
    public function down()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('transfer_orders', 'driver_name')) {
                $table->string('driver_name')->nullable();
            }
            if (!Schema::hasColumn('transfer_orders', 'driver_id')) {
                $table->string('driver_id', 20)->nullable();
            }
            if (!Schema::hasColumn('transfer_orders', 'vehicle_plate')) {
                $table->string('vehicle_plate', 20)->nullable();
            }
            if (Schema::hasColumn('transfer_orders', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
        });
    }
}
