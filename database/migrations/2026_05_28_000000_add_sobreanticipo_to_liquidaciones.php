<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 005 — Reincorpora el campo "sobre anticipo" del conductor.
 *
 * Columna nueva separada de anticipo_conductor (que en 004 fue el rename del
 * viejo sobreanticipo). "Anticipos conductor" = anticipo_conductor + sobreanticipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('sobreanticipo', 12, 0)->default(0)->after('anticipo_conductor');
        });
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn('sobreanticipo');
        });
    }
};
