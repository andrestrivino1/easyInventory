<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoMedidaYEquivalenciaToProducts extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->enum('tipo_medida', ['unidad','caja'])->default('unidad')->after('estado');
            $table->integer('unidades_por_caja')->nullable()->after('tipo_medida');
        });
    }
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['tipo_medida','unidades_por_caja']);
        });
    }
}
