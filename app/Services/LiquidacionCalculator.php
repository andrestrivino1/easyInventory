<?php

namespace App\Services;

use App\Models\Liquidacion;
use App\Models\MonthlyExpense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LiquidacionCalculator
{
    /**
     * Suma los valores de gastos operativos (16 categorías).
     * Cada $expense: ['valor' => int|null, 'galones' => float|null].
     */
    public static function computeSumatoriaGastos(array $expenses): int
    {
        $total = 0;
        foreach ($expenses as $exp) {
            $valor = (int) ($exp['valor'] ?? 0);
            $total += $valor;
        }
        return $total;
    }

    /**
     * Suma los valores de peajes — solo cuenta peajes con is_used = true.
     * Cada $toll: ['valor' => int, 'is_used' => bool, 'paid_by' => string].
     */
    public static function computeSumatoriaPeajes(array $tolls): int
    {
        $total = 0;
        foreach ($tolls as $t) {
            $used = $t['is_used'] ?? true;
            if ($used) {
                $total += (int) ($t['valor'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * Suma los peajes usados que paga el CONDUCTOR (paid_by = 'conductor').
     * Estos se descuentan de su saldo y NO se cuentan como costo de empresa.
     */
    public static function computeSumatoriaPeajesConductor(array $tolls): int
    {
        $total = 0;
        foreach ($tolls as $t) {
            $used = $t['is_used'] ?? true;
            $payer = $t['paid_by'] ?? 'empresa';
            if ($used && $payer === 'conductor') {
                $total += (int) ($t['valor'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * Total de anticipos del viaje = empresa + conductor + sobre anticipo.
     * Se persiste en total_anticipos (lo usa el consolidado del índice).
     */
    public static function computeTotalAnticipos(int $anticipoEmpresa, int $anticipoConductor, int $sobreanticipo = 0): int
    {
        return $anticipoEmpresa + $anticipoConductor + $sobreanticipo;
    }

    /**
     * Anticipos del conductor = anticipo del conductor + sobre anticipo.
     */
    public static function anticiposConductor(int $anticipoConductor, int $sobreanticipo): int
    {
        return $anticipoConductor + $sobreanticipo;
    }

    /**
     * A favor de, según el signo de "Ant - gastos" (= gastos − anticipos conductor):
     *  - 'conductor' si > 0 (el conductor gastó más de lo anticipado: la empresa le debe)
     *  - 'empresa'   si < 0 (le anticiparon de más: debe devolver el excedente)
     *  - 'ninguno'   si = 0
     */
    public static function aFavorDe(int $antGastos): string
    {
        if ($antGastos > 0) return Liquidacion::AFAVOR_CONDUCTOR;
        if ($antGastos < 0) return Liquidacion::AFAVOR_EMPRESA;
        return Liquidacion::AFAVOR_NINGUNO;
    }

    /**
     * Recalcula y persiste los cached totals con las fórmulas del panel (feature 005).
     *
     * Significado de columnas (algunas repurposadas):
     *  - sumatoria_gastos_totales = gastos_op + descuentos + peajes ("Suma de gastos total de viaje")
     *  - saldo_pendiente          = valor_flete − anticipo_empresa ("Saldo adeudado empresa de transporte")
     *  - saldo_viaje              = (gastos_op + descuentos) − (anticipo_conductor + sobreanticipo) ("Ant - gastos")
     *  - ganancia_viaje           = valor_flete − sumatoria_gastos_totales ("Ganancia final de viaje")
     */
    public static function recalcAndSave(Liquidacion $liq): void
    {
        $expenses = $liq->expenses->map(fn ($e) => ['valor' => $e->valor])->all();
        $tolls = $liq->tolls->map(fn ($t) => ['valor' => $t->valor, 'is_used' => $t->is_used, 'paid_by' => $t->paid_by])->all();

        $gastosOp = self::computeSumatoriaGastos($expenses);
        $peajes = self::computeSumatoriaPeajes($tolls);                       // todos los usados
        $peajesConductor = self::computeSumatoriaPeajesConductor($tolls);     // subconjunto que paga el conductor

        $descuentos = (int) $liq->descuentos;
        $anticipoEmpresa = (int) $liq->anticipo_empresa;
        $anticipoConductor = (int) $liq->anticipo_conductor;
        $sobreanticipo = (int) $liq->sobreanticipo;
        $valorFlete = (int) $liq->valor_flete;

        $sumGastos = $gastosOp + $descuentos;                                 // "Sumatoria de gastos"
        $gastosTot = $sumGastos + $peajes;                                    // "Suma de gastos total de viaje"
        $anticiposCond = self::anticiposConductor($anticipoConductor, $sobreanticipo);
        $antGastos = $sumGastos - $anticiposCond;                            // "Ant - gastos" -> saldo_viaje
        $saldoAdeudadoEmpresa = $valorFlete - $anticipoEmpresa;              // "Saldo adeudado empresa" -> saldo_pendiente
        $ganancia = $valorFlete - $gastosTot;                                // "Ganancia final de viaje"
        $totalAnt = self::computeTotalAnticipos($anticipoEmpresa, $anticipoConductor, $sobreanticipo);

        $liq->sumatoria_gastos_operativos = $gastosOp;
        $liq->sumatoria_peajes = $peajes;
        $liq->sumatoria_peajes_conductor = $peajesConductor;
        $liq->sumatoria_gastos_totales = $gastosTot;
        $liq->total_anticipos = $totalAnt;
        $liq->saldo_pendiente = $saldoAdeudadoEmpresa;
        $liq->saldo_viaje = $antGastos;
        $liq->ganancia_viaje = $ganancia;
        $liq->a_favor_de = self::aFavorDe($antGastos);
        $liq->save();
    }

    /**
     * Agregación del consolidado para el conjunto filtrado.
     * Devuelve totales del periodo (excluye anuladas y soft-deleted).
     */
    public static function aggregate(Builder $query): array
    {
        $row = (clone $query)
            ->activas()
            ->selectRaw('
                COUNT(*) as count,
                COALESCE(SUM(sumatoria_gastos_operativos),0) as sum_gastos_operativos,
                COALESCE(SUM(sumatoria_peajes),0) as sum_peajes,
                COALESCE(SUM(sumatoria_peajes_conductor),0) as sum_peajes_conductor,
                COALESCE(SUM(sumatoria_gastos_totales),0) as sum_gastos_totales,
                COALESCE(SUM(total_anticipos),0) as sum_anticipos,
                COALESCE(SUM(descuentos),0) as sum_descuentos,
                COALESCE(SUM(valor_flete),0) as sum_flete,
                COALESCE(SUM(saldo_viaje),0) as sum_saldo,
                COALESCE(SUM(ganancia_viaje),0) as sum_ganancia
            ')
            ->first();

        $count = (int) ($row->count ?? 0);
        $sumGanancia = (int) ($row->sum_ganancia ?? 0);
        $sumFlete = (int) ($row->sum_flete ?? 0);

        return [
            'count' => $count,
            'sum_gastos_operativos' => (int) $row->sum_gastos_operativos,
            'sum_peajes' => (int) $row->sum_peajes,
            'sum_peajes_conductor' => (int) $row->sum_peajes_conductor,
            'sum_gastos_totales' => (int) $row->sum_gastos_totales,
            'sum_anticipos' => (int) $row->sum_anticipos,
            'sum_descuentos' => (int) $row->sum_descuentos,
            'sum_flete' => $sumFlete,
            'sum_saldo' => (int) $row->sum_saldo,
            'sum_ganancia' => $sumGanancia,
            'avg_ganancia' => $count > 0 ? (int) round($sumGanancia / $count) : 0,
            'margen_pct' => $sumFlete > 0 ? round(($sumGanancia / $sumFlete) * 100, 2) : 0,
        ];
    }

    /**
     * Agrega los mismos totales pero agrupados por mes calendario (YYYY-MM)
     * sobre fecha_inicio.
     */
    public static function aggregateByMonth(Builder $query): Collection
    {
        $rows = (clone $query)
            ->activas()
            ->selectRaw("
                DATE_FORMAT(fecha_inicio, '%Y-%m') as periodo,
                COUNT(*) as count,
                COALESCE(SUM(sumatoria_gastos_operativos),0) as sum_gastos_operativos,
                COALESCE(SUM(sumatoria_peajes),0) as sum_peajes,
                COALESCE(SUM(sumatoria_peajes_conductor),0) as sum_peajes_conductor,
                COALESCE(SUM(sumatoria_gastos_totales),0) as sum_gastos_totales,
                COALESCE(SUM(total_anticipos),0) as sum_anticipos,
                COALESCE(SUM(descuentos),0) as sum_descuentos,
                COALESCE(SUM(valor_flete),0) as sum_flete,
                COALESCE(SUM(saldo_viaje),0) as sum_saldo,
                COALESCE(SUM(ganancia_viaje),0) as sum_ganancia
            ")
            ->groupByRaw("DATE_FORMAT(fecha_inicio, '%Y-%m')")
            ->orderByRaw("periodo DESC")
            ->get();

        return $rows->map(function ($r) {
            $count = (int) $r->count;
            $sumGanancia = (int) $r->sum_ganancia;
            $sumFlete = (int) $r->sum_flete;
            return [
                'periodo' => $r->periodo,
                'count' => $count,
                'sum_gastos_operativos' => (int) $r->sum_gastos_operativos,
                'sum_peajes' => (int) $r->sum_peajes,
                'sum_peajes_conductor' => (int) $r->sum_peajes_conductor,
                'sum_gastos_totales' => (int) $r->sum_gastos_totales,
                'sum_anticipos' => (int) $r->sum_anticipos,
                'sum_descuentos' => (int) $r->sum_descuentos,
                'sum_flete' => $sumFlete,
                'sum_saldo' => (int) $r->sum_saldo,
                'sum_ganancia' => $sumGanancia,
                'avg_ganancia' => $count > 0 ? (int) round($sumGanancia / $count) : 0,
                'margen_pct' => $sumFlete > 0 ? round(($sumGanancia / $sumFlete) * 100, 2) : 0,
            ];
        });
    }

    // --- Gastos mensuales que afectan el consolidado (utilidad final) ---

    /**
     * Tuplas (driver_id, anio, mes) de los viajes del conjunto filtrado,
     * usando el mes/año de la fecha de inicio. Cada conductor/mes aparece una vez.
     */
    public static function tripPeriods(Builder $query): Collection
    {
        return (clone $query)->activas()
            ->selectRaw('DISTINCT driver_id, YEAR(fecha_inicio) as anio, MONTH(fecha_inicio) as mes')
            ->get()
            ->map(fn ($r) => ['driver_id' => (int) $r->driver_id, 'anio' => (int) $r->anio, 'mes' => (int) $r->mes]);
    }

    /**
     * Igual que tripPeriods pero agrupado por periodo calendario 'YYYY-MM'.
     */
    public static function tripPeriodsByMonth(Builder $query): Collection
    {
        return (clone $query)->activas()
            ->selectRaw("DATE_FORMAT(fecha_inicio, '%Y-%m') as periodo, driver_id, YEAR(fecha_inicio) as anio, MONTH(fecha_inicio) as mes")
            ->distinct()
            ->get()
            ->groupBy('periodo')
            ->map(fn ($rows) => $rows->map(fn ($r) => ['driver_id' => (int) $r->driver_id, 'anio' => (int) $r->anio, 'mes' => (int) $r->mes]));
    }

    /**
     * Suma de los gastos mensuales (7 conceptos) para un conjunto de tuplas
     * (driver_id, anio, mes). Cada conductor/mes se cuenta una sola vez.
     */
    public static function monthlyExpensesTotalFor($tuples): int
    {
        $tuples = collect($tuples);
        if ($tuples->isEmpty()) {
            return 0;
        }

        return (int) MonthlyExpense::query()
            ->where(function ($w) use ($tuples) {
                foreach ($tuples as $t) {
                    $w->orWhere(fn ($x) => $x
                        ->where('driver_id', $t['driver_id'])
                        ->where('anio', $t['anio'])
                        ->where('mes', $t['mes']));
                }
            })
            ->selectRaw('COALESCE(SUM(sueldo_conductor+seguridad_social+cuota_banco+cuota_tercero+satelital+seguro_vehiculo+otro_valor),0) as s')
            ->value('s');
    }
}
