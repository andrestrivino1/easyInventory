<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->string('product_name')->nullable();
            $table->string('container_ref')->nullable();
            $table->string('container_pdf')->nullable();
            $table->json('container_images')->nullable();
            $table->string('proforma_pdf')->nullable();
            $table->string('invoice_pdf')->nullable();
            $table->string('bl_pdf')->nullable();
            $table->string('etd')->nullable();
            $table->string('shipping_company')->nullable();
            $table->integer('free_days_at_dest')->nullable();
            $table->string('supplier')->nullable();
            $table->enum('credit_time', ['15', '30', '45'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
                'product_name',
                'container_ref',
                'container_pdf',
                'container_images',
                'proforma_pdf',
                'invoice_pdf',
                'bl_pdf',
                'etd',
                'shipping_company',
                'free_days_at_dest',
                'supplier',
                'credit_time'
            ]);
        });
    }
};

