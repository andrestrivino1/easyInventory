<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DropContainerIdFromProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'container_id')) {
                // Verificar si existe la foreign key antes de eliminarla
                $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'container_id' AND CONSTRAINT_NAME != 'PRIMARY'");
                if (!empty($foreignKeys)) {
                    $table->dropForeign(['container_id']);
                }
                $table->dropColumn('container_id');
            }
        });
    }
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('container_id')->nullable();
        });
    }
}
