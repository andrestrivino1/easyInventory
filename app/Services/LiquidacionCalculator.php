<?php

namespace App\Services;

use App\Models\Liquidacion;
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

    public static function computeTotalAnticipos(int $anticipo, int $sobreanticipo): int
    {
        return $anticipo + $sobreanticipo;
    }

    /**
     * Saldo viaje = total_anticipos − gastos_operativos − peajes pagados por el conductor.
     * Los peajes de empresa (GoPass) NO tocan el saldo; los del conductor sí (salen de su anticipo).
     */
    public static function computeSaldoViaje(int $totalAnticipos, int $sumatoriaGastosOperativos, int $peajesConductor = 0): int
    {
        return $totalAnticipos - $sumatoriaGastosOperativos - $peajesConductor;
    }

    /**
     * Ganancia viaje = valor_flete − costo de empresa.
     * Costo de empresa = gastos_operativos + peajes de empresa (los del conductor los absorbe él,
     * por eso NO entran en la ganancia).
     */
    public static function computeGananciaViaje(int $valorFlete, int $costoEmpresa): int
    {
        return $valorFlete - $costoEmpresa;
    }

    /**
     * A favor de:
     *  - 'empresa'    si saldo > 0 (el conductor debe devolver excedente)
     *  - 'conductor'  si saldo < 0 (la empresa le debe al conductor)
     *  - 'ninguno'    si saldo = 0
     */
    public static function aFavorDe(int $saldo): string
    {
        if ($saldo > 0) return Liquidacion::AFAVOR_EMPRESA;
        if ($saldo < 0) return Liquidacion::AFAVOR_CONDUCTOR;
        return Liquidacion::AFAVOR_NINGUNO;
    }

    /**
     * Recalcula y persiste todos los cached totals en la liquidación.
     */
    public static function recalcAndSave(Liquidacion $liq): void
    {
        $expenses = $liq->expenses->map(fn ($e) => ['valor' => $e->valor])->all();
        $tolls = $liq->tolls->map(fn ($t) => ['valor' => $t->valor, 'is_used' => $t->is_used, 'paid_by' => $t->paid_by])->all();

        $gastosOp = self::computeSumatoriaGastos($expenses);
        $peajes = self::computeSumatoriaPeajes($tolls);                       // todos los usados
        $peajesConductor = self::computeSumatoriaPeajesConductor($tolls);     // subconjunto que paga el conductor
        $peajesEmpresa = $peajes - $peajesConductor;
        $gastosTot = $gastosOp + $peajes;                                     // total del viaje (incluye ambos)
        $totalAnt = self::computeTotalAnticipos((int) $liq->anticipo, (int) $liq->sobreanticipo);
        $saldo = self::computeSaldoViaje($totalAnt, $gastosOp, $peajesConductor);
        $ganancia = self::computeGananciaViaje((int) $liq->valor_flete, $gastosOp + $peajesEmpresa);

        $liq->sumatoria_gastos_operativos = $gastosOp;
        $liq->sumatoria_peajes = $peajes;
        $liq->sumatoria_peajes_conductor = $peajesConductor;
        $liq->sumatoria_gastos_totales = $gastosTot;
        $liq->total_anticipos = $totalAnt;
        $liq->saldo_viaje = $saldo;
        $liq->ganancia_viaje = $ganancia;
        $liq->a_favor_de = self::aFavorDe($saldo);
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
                'sum_flete' => $sumFlete,
                'sum_saldo' => (int) $r->sum_saldo,
                'sum_ganancia' => $sumGanancia,
                'avg_ganancia' => $count > 0 ? (int) round($sumGanancia / $count) : 0,
                'margen_pct' => $sumFlete > 0 ? round(($sumGanancia / $sumFlete) * 100, 2) : 0,
            ];
        });
    }
}
