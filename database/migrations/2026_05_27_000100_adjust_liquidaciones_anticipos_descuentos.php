<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature 004 — Anticipos diferenciados, descuentos y saldo pendiente.
 *
 * Renombra anticipo -> anticipo_empresa y sobreanticipo -> anticipo_conductor
 * (preservando datos vía ALTER TABLE ... CHANGE, sin doctrine/dbal) y agrega
 * descuentos, saldo_pendiente (cacheado) y manifiesto_pdf_path.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rename preservando datos (MySQL). doctrine/dbal no está instalado.
        DB::statement("ALTER TABLE `liquidaciones` CHANGE `anticipo` `anticipo_empresa` DECIMAL(12,0) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE `liquidaciones` CHANGE `sobreanticipo` `anticipo_conductor` DECIMAL(12,0) NOT NULL DEFAULT 0");

        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->decimal('descuentos', 12, 0)->default(0)->after('anticipo_conductor');
            // saldo_pendiente puede ser negativo (anticipo_empresa - descuentos) -> NO unsigned.
            $table->decimal('saldo_pendiente', 12, 0)->default(0)->after('total_anticipos');
            $table->string('manifiesto_pdf_path', 255)->nullable()->after('numero_mfto');
        });

        // Backfill: saldo_pendiente = anticipo_empresa - descuentos (descuentos arranca en 0).
        DB::statement("UPDATE `liquidaciones` SET `saldo_pendiente` = `anticipo_empresa` - `descuentos`");
    }

    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn(['descuentos', 'saldo_pendiente', 'manifiesto_pdf_path']);
        });

        DB::statement("ALTER TABLE `liquidaciones` CHANGE `anticipo_empresa` `anticipo` DECIMAL(12,0) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE `liquidaciones` CHANGE `anticipo_conductor` `sobreanticipo` DECIMAL(12,0) NOT NULL DEFAULT 0");
    }
};
