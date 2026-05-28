<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Feature 005 — Recalcula los cached totals de las liquidaciones existentes
 * con las fórmulas nuevas del panel (las columnas saldo_pendiente y saldo_viaje
 * cambian de significado; total_anticipos incluye sobreanticipo; ganancia y
 * sumatoria_gastos_totales incluyen descuentos y todos los peajes).
 *
 * Se calcula a partir de las columnas base ya almacenadas (no requiere releer
 * expenses/tolls porque sumatoria_gastos_operativos y sumatoria_peajes ya están
 * persistidas y su fórmula no cambió).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE `liquidaciones` SET
                `sumatoria_gastos_totales` = `sumatoria_gastos_operativos` + `descuentos` + `sumatoria_peajes`,
                `saldo_pendiente` = `valor_flete` - `anticipo_empresa`,
                `saldo_viaje` = (`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`),
                `ganancia_viaje` = `valor_flete` - (`sumatoria_gastos_operativos` + `descuentos` + `sumatoria_peajes`),
                `total_anticipos` = `anticipo_empresa` + `anticipo_conductor` + `sobreanticipo`,
                `a_favor_de` = CASE
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`)) > 0 THEN 'conductor'
                    WHEN ((`sumatoria_gastos_operativos` + `descuentos`) - (`anticipo_conductor` + `sobreanticipo`)) < 0 THEN 'empresa'
                    ELSE 'ninguno'
                END
        ");
    }

    public function down(): void
    {
        // Restaura las fórmulas previas (feature 004).
        DB::statement("
            UPDATE `liquidaciones` SET
                `sumatoria_gastos_totales` = `sumatoria_gastos_operativos` + `sumatoria_peajes`,
                `saldo_pendiente` = `anticipo_empresa` - `descuentos`,
                `saldo_viaje` = (`anticipo_empresa` + `anticipo_conductor`) - `sumatoria_gastos_operativos` - `sumatoria_peajes_conductor`,
                `ganancia_viaje` = `valor_flete` - (`sumatoria_gastos_operativos` + (`sumatoria_peajes` - `sumatoria_peajes_conductor`)),
                `total_anticipos` = `anticipo_empresa` + `anticipo_conductor`,
                `a_favor_de` = CASE
                    WHEN ((`anticipo_empresa` + `anticipo_conductor`) - `sumatoria_gastos_operativos` - `sumatoria_peajes_conductor`) > 0 THEN 'empresa'
                    WHEN ((`anticipo_empresa` + `anticipo_conductor`) - `sumatoria_gastos_operativos` - `sumatoria_peajes_conductor`) < 0 THEN 'conductor'
                    ELSE 'ninguno'
                END
        ");
    }
};
