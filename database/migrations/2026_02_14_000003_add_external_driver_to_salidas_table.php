<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->string('external_driver_name', 255)->nullable()->after('driver_id');
            $table->string('external_driver_identity', 50)->nullable()->after('external_driver_name');
            $table->string('external_driver_plate', 50)->nullable()->after('external_driver_identity');
        });
    }

    public function down()
    {
        Schema::table('salidas', function (Blueprint $table) {
            $table->dropColumn(['external_driver_name', 'external_driver_identity', 'external_driver_plate']);
        });
    }
};
