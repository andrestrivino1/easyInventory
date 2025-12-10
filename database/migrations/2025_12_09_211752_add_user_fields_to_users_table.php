<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nombre_completo')->after('name');
            $table->string('telefono')->nullable()->after('email');
            $table->unsignedBigInteger('almacen_id')->nullable()->after('telefono');
            $table->string('rol')->default('usuario')->after('almacen_id');
            $table->foreign('almacen_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn(['nombre_completo', 'telefono', 'almacen_id', 'rol']);
        });
    }
}
