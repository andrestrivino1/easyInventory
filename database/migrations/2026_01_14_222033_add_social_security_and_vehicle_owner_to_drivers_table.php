<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialSecurityAndVehicleOwnerToDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->date('social_security_date')->nullable()->after('vehicle_photo_path')->comment('Fecha de seguridad social');
            $table->string('social_security_pdf')->nullable()->after('social_security_date')->comment('PDF de seguridad social');
            $table->string('vehicle_owner')->nullable()->after('social_security_pdf')->comment('Propietario del vehículo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['social_security_date', 'social_security_pdf', 'vehicle_owner']);
        });
    }
}
