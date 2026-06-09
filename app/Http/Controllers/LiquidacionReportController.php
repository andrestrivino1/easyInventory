<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Services\LiquidacionCalculator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LiquidacionReportController extends Controller
{
    /** Conceptos de gasto fijo mensual (orden y etiqueta de presentación). */
    public const CONCEPTOS_FIJOS = [
        'sueldo_conductor' => 'Sueldo conductor',
        'seguridad_social' => 'Seguridad social',
        'cuota_banco' => 'Cuota banco',
        'cuota_tercero' => 'Cuota tercero',
        'satelital' => 'Satelital',
        'seguro_vehiculo' => 'Seguro vehículo',
        'otro_valor' => 'Otros',
    ];

    /** Dashboard del informe (HTML). */
    public function index(Request $request)
    {
        $data = $this->buildReport($request);
        $data['drivers'] = Driver::orderBy('name')->get(['id', 'name', 'vehicle_plate']);

        return view('liquidaciones.reportes.index', $data);
    }

    /** Exportación del informe a PDF (recibe las gráficas como PNG opcionales). */
    public function pdf(Request $request)
    {
        $data = $this->buildReport($request);

        $charts = $request->input('charts', []);
        $data['charts'] = is_array($charts) ? $charts : [];

        $pdf = Pdf::loadView('liquidaciones.reportes.pdf', $data)
            ->setPaper('letter', 'portrait');

        $slug = $data['filtros']['slug'];
        $placa = $data['driverActual']->vehicle_plate ?? null;
        $sufijo = $placa ? '-' . preg_replace('/[^A-Z0-9]/i', '', $placa) : '';

        return $pdf->download("informe-liquidaciones-{$slug}{$sufijo}.pdf");
    }

    /**
     * Compone todos los datos del informe para el periodo/alcance solicitado.
     * Reutilizado por el dashboard y por el PDF para garantizar paridad.
     */
    private function buildReport(Request $request): array
    {
        $v = $request->validate([
            'tipo' => 'nullable|in:mes,semestre,anio',
            'anio' => 'nullable|integer|min:2000|max:2100',
            'mes' => 'nullable|integer|min:1|max:12',
            'semestre' => 'nullable|integer|min:1|max:2',
            'driver_id' => 'nullable|integer|exists:drivers,id',
        ]);

        $hoy = Carbon::now();
        $tipo = $v['tipo'] ?? 'mes';
        $anio = (int) ($v['anio'] ?? $hoy->year);
        $mes = (int) ($v['mes'] ?? $hoy->month);
        $semestre = (int) ($v['semestre'] ?? ($mes <= 6 ? 1 : 2));
        $driverId = $v['driver_id'] ?? null;

        [$desde, $hasta, $label, $slug] = $this->resolvePeriod($tipo, $anio, $mes, $semestre);

        $base = $this->baseQuery($desde, $hasta, $driverId);

        // --- Resumen consolidado + utilidad neta (US1) ---
        $resumen = LiquidacionCalculator::aggregate(clone $base);
        $gastosMensuales = LiquidacionCalculator::monthlyExpensesTotalFor(
            LiquidacionCalculator::tripPeriods(clone $base)
        );
        $resumen['sum_gastos_mensuales'] = $gastosMensuales;
        $resumen['utilidad_neta'] = $resumen['sum_ganancia'] - $gastosMensuales;
        $resumen['resultado'] = $resumen['utilidad_neta'] > 0
            ? 'ganancia'
            : ($resumen['utilidad_neta'] < 0 ? 'perdida' : 'equilibrio');

        // --- Desglose por categoría operativa (US1) ---
        $categorias = LiquidacionCalculator::expensesByCategory(clone $base);

        // --- Desglose de los 7 gastos fijos mensuales (US1) ---
        $gastosFijos = LiquidacionCalculator::monthlyExpensesBreakdownFor(
            LiquidacionCalculator::tripPeriods(clone $base)
        );

        // --- Evolución mensual + mejor/peor mes (US2) ---
        $tuplasPorMes = LiquidacionCalculator::tripPeriodsByMonth(clone $base);
        $evolucion = LiquidacionCalculator::aggregateByMonth(clone $base)
            ->map(function ($m) use ($tuplasPorMes) {
                $gm = LiquidacionCalculator::monthlyExpensesTotalFor(
                    $tuplasPorMes->get($m['periodo'], collect())
                );
                $m['sum_gastos_mensuales'] = $gm;
                $m['utilidad_neta'] = $m['sum_ganancia'] - $gm;

                return $m;
            })
            ->sortBy('periodo')
            ->values();

        $mejorMes = $evolucion->isNotEmpty() ? $evolucion->sortByDesc('utilidad_neta')->first() : null;
        $peorMes = $evolucion->isNotEmpty() ? $evolucion->sortBy('utilidad_neta')->first() : null;

        // --- Desglose por conductor (US4) — solo en vista consolidada ---
        $porConductor = $driverId ? collect() : $this->breakdownByDriver($desde, $hasta);

        return [
            'filtros' => [
                'tipo' => $tipo, 'anio' => $anio, 'mes' => $mes, 'semestre' => $semestre,
                'driver_id' => $driverId, 'label' => $label, 'slug' => $slug,
            ],
            'resumen' => $resumen,
            'categorias' => $categorias,
            'gastosFijos' => $gastosFijos,
            'conceptosFijos' => self::CONCEPTOS_FIJOS,
            'evolucion' => $evolucion,
            'mejorMes' => $mejorMes,
            'peorMes' => $peorMes,
            'porConductor' => $porConductor,
            'driverActual' => $driverId ? Driver::find($driverId) : null,
        ];
    }

    /**
     * Traduce la selección de periodo a [Carbon $desde, Carbon $hasta, string $label, string $slug].
     * Semestres calendario fijos: S1 ene–jun, S2 jul–dic.
     */
    private function resolvePeriod(string $tipo, int $anio, int $mes, int $semestre): array
    {
        if ($tipo === 'anio') {
            return [
                Carbon::create($anio, 1, 1)->startOfDay(),
                Carbon::create($anio, 12, 31)->endOfDay(),
                "Año {$anio}",
                "anio-{$anio}",
            ];
        }

        if ($tipo === 'semestre') {
            $mesIni = $semestre === 1 ? 1 : 7;
            $mesFin = $semestre === 1 ? 6 : 12;

            return [
                Carbon::create($anio, $mesIni, 1)->startOfDay(),
                Carbon::create($anio, $mesFin, 1)->endOfMonth(),
                "Semestre {$semestre} de {$anio}",
                "sem{$semestre}-{$anio}",
            ];
        }

        // mes
        $desde = Carbon::create($anio, $mes, 1)->startOfDay();
        $nombreMes = ucfirst($desde->locale('es')->isoFormat('MMMM'));

        return [
            $desde,
            (clone $desde)->endOfMonth(),
            "{$nombreMes} {$anio}",
            sprintf('%04d-%02d', $anio, $mes),
        ];
    }

    /** Builder base por rango de fecha_inicio (+ conductor opcional). No aplica activas() (lo añade el calculador). */
    private function baseQuery(Carbon $desde, Carbon $hasta, ?int $driverId = null)
    {
        return Liquidacion::query()
            ->whereBetween('fecha_inicio', [$desde->toDateString(), $hasta->toDateString()])
            ->when($driverId, fn ($q, $v) => $q->where('driver_id', $v));
    }

    /**
     * Un ResumenPeriodo (subconjunto) por cada conductor con liquidaciones activas
     * en el periodo, incluyendo sus propios gastos fijos. La suma de utilidad_neta
     * por conductor reproduce el consolidado de la empresa (FR-018).
     */
    private function breakdownByDriver(Carbon $desde, Carbon $hasta)
    {
        $driverIds = $this->baseQuery($desde, $hasta)->activas()
            ->distinct()->pluck('driver_id')->filter()->values();

        if ($driverIds->isEmpty()) {
            return collect();
        }

        $drivers = Driver::whereIn('id', $driverIds)->get()->keyBy('id');

        return $driverIds->map(function ($id) use ($desde, $hasta, $drivers) {
            $b = $this->baseQuery($desde, $hasta, $id);
            $r = LiquidacionCalculator::aggregate(clone $b);
            $gm = LiquidacionCalculator::monthlyExpensesTotalFor(
                LiquidacionCalculator::tripPeriods(clone $b)
            );
            $driver = $drivers->get($id);

            return [
                'driver_id' => $id,
                'name' => $driver->name ?? 'N/D',
                'vehicle_plate' => $driver->vehicle_plate ?? '',
                'count' => $r['count'],
                'sum_flete' => $r['sum_flete'],
                'sum_gastos_totales' => $r['sum_gastos_totales'],
                'sum_ganancia' => $r['sum_ganancia'],
                'sum_gastos_mensuales' => $gm,
                'utilidad_neta' => $r['sum_ganancia'] - $gm,
            ];
        })->sortByDesc('utilidad_neta')->values();
    }
}
