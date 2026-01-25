<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeightToTransferOrderProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->decimal('weight_per_box', 10, 2)->nullable()->after('sheets_per_box');
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
            $table->dropColumn('weight_per_box');
        });
    }
}
