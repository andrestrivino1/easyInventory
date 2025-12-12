<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('warehouse_from_id');
            $table->unsignedBigInteger('warehouse_to_id');
            $table->string('order_number')->unique();
            $table->string('status')->default('en_transito');
            $table->timestamp('date')->useCurrent();
            $table->string('note')->nullable();
            $table->string('driver_name');
            $table->string('driver_id', 20);
            $table->string('vehicle_plate', 20);
            $table->timestamps();
            $table->foreign('warehouse_from_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('warehouse_to_id')->references('id')->on('warehouses')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfer_orders');
    }
}
