<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('calibre', 10, 2)->nullable()->after('medidas');
            $table->decimal('alto', 10, 2)->nullable()->after('calibre');
            $table->decimal('ancho', 10, 2)->nullable()->after('alto');
            $table->decimal('peso_empaque', 10, 2)->default(2.5)->after('ancho');
            $table->decimal('weight_per_box', 10, 2)->nullable()->after('peso_empaque')->comment('Peso por caja calculado: calibre*alto*ancho*peso_empaque*cant_laminas*cant_cajas');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['calibre', 'alto', 'ancho', 'peso_empaque', 'weight_per_box']);
        });
    }
};
