<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAproboAndCiudadDestinoToTransferOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('aprobo')->nullable()->after('note');
            $table->string('ciudad_destino')->nullable()->after('aprobo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn(['aprobo', 'ciudad_destino']);
        });
    }
}
