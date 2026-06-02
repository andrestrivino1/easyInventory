<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill — el peaje que paga el conductor sale de su bolsillo y debe contar
 * como gasto suyo en "Ant - gastos" (saldo_viaje). La fórmula 005 lo omitía.
 *
 * Solo se reescriben saldo_viaje y a_favor_de (las únicas columnas afectadas);
 * sumatoria_gastos_totales y ganancia_viaje no cambian (el peaje del conductor
 * ya estaba contado una sola vez dentro de sumatoria_peajes).
 *
 * Recalcula a partir de las columnas base ya almacenadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE `liquidaciones` SET
                `saldo_viaje` = (`sumatoria_gastos_operativos` + `descuentos` + `sumatoria_peajes_conductor`) - (`anticipo_conductor` + `sobreanticipo`),
                `a_favor_de` = CASE
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos` + `sumatoria_peajes_conductor`) - (`anticipo_conductor` + `sobreanticipo`)) > 0 THEN 'conductor'
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos` + `sumatoria_peajes_conductor`) - (`anticipo_conductor` + `sobreanticipo`)) < 0 THEN 'empresa'
                    ELSE 'ninguno'
                END
        ");
    }

    public function down(): void
    {
        // Restaura la fórmula 005 (sin el peaje del conductor).
        DB::statement("
            UPDATE `liquidaciones` SET
                `saldo_viaje` = (`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`),
                `a_favor_de` = CASE
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`)) > 0 THEN 'conductor'
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`)) < 0 THEN 'empresa'
                    ELSE 'ninguno'
                END
        ");
    }
};
