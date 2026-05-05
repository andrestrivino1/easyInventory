<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSheetsQualityToTransferOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transfer_order_products', function (Blueprint $table) {
            $table->integer('good_sheets')->nullable()->after('quantity')->comment('Láminas en buen estado recibidas');
            $table->integer('bad_sheets')->nullable()->after('good_sheets')->comment('Láminas en mal estado recibidas');
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
            $table->dropColumn(['good_sheets', 'bad_sheets']);
        });
    }
}
