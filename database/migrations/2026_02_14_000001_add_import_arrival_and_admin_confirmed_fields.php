<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->timestamp('delivered_to_transport_at')->nullable()->after('received_at');
            $table->unsignedBigInteger('delivered_to_transport_by_user_id')->nullable()->after('delivered_to_transport_at');
            $table->timestamp('admin_confirmed_at')->nullable()->after('delivered_to_transport_by_user_id');
            $table->unsignedBigInteger('arrival_confirmed_by_user_id')->nullable()->after('admin_confirmed_at')->comment('Usuario seleccionado al confirmar arribo (simulado)');
        });
    }

    public function down()
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
                'delivered_to_transport_at',
                'delivered_to_transport_by_user_id',
                'admin_confirmed_at',
                'arrival_confirmed_by_user_id',
            ]);
        });
    }
};
