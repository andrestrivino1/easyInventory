<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContainersTable extends Migration
{
    public function up()
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('product_name');
            $table->integer('boxes');
            $table->integer('sheets_per_box');
            $table->string('note')->nullable();
        });
    }
    public function down()
    {
        Schema::dropIfExists('containers');
    }
}
