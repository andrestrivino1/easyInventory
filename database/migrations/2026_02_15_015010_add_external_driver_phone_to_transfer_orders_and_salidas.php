<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('external_driver_phone', 20)->nullable()->after('external_driver_plate');
        });

        Schema::table('salidas', function (Blueprint $table) {
            $table->string('external_driver_phone', 20)->nullable()->after('external_driver_plate');
        });
    }

    public function down()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn('external_driver_phone');
        });

        Schema::table('salidas', function (Blueprint $table) {
            $table->dropColumn('external_driver_phone');
        });
    }
};
