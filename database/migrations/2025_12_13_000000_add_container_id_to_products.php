<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContainerIdToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('container_id')->nullable()->after('almacen_id');
            $table->foreign('container_id')->references('id')->on('containers')->restrictOnDelete();
        });
    }
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['container_id']);
            $table->dropColumn('container_id');
        });
    }
}
