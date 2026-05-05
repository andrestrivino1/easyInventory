<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiveByToTransferOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->enum('receive_by', ['cajas', 'laminas'])->nullable()->after('bad_sheets')->comment('Forma en que se recibe la transferencia: por cajas o por láminas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->dropColumn('receive_by');
        });
    }
}
