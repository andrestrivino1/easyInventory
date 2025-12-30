<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalidaDestinoToTransferOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('salida')->nullable()->after('warehouse_to_id');
            $table->string('destino')->nullable()->after('salida');
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
            $table->dropColumn(['salida', 'destino']);
        });
    }
}
