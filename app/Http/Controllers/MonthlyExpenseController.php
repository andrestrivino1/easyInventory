<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMonthlyExpenseYearRequest;
use App\Models\Driver;
use App\Models\MonthlyExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonthlyExpenseController extends Controller
{
    /** Conceptos monetarios mensuales (orden de columnas en la grilla). */
    public const CONCEPTOS = [
        'sueldo_conductor', 'seguridad_social', 'cuota_banco', 'cuota_tercero',
        'satelital', 'seguro_vehiculo', 'otro_valor',
    ];

    /**
     * Landing: selector (conductor + año) + lista de grupos conductor/año ya registrados.
     */
    public function index(Request $request)
    {
        $drivers = Driver::where('active', 1)->orderBy('name')->get(['id', 'name', 'vehicle_plate']);

        $groups = MonthlyExpense::query()
            ->selectRaw('driver_id, vehicle_plate, anio, COUNT(*) as meses, '
                . 'SUM(sueldo_conductor+seguridad_social+cuota_banco+cuota_tercero+satelital+seguro_vehiculo+otro_valor) as total_anio')
            ->with('driver:id,name')
            ->groupBy('driver_id', 'vehicle_plate', 'anio')
            ->orderByDesc('anio')->orderBy('vehicle_plate')
            ->get();

        return view('liquidaciones.gastos.index', compact('drivers', 'groups'));
    }

    /**
     * Grilla anual: 12 meses de un conductor en un año, con los datos existentes pre-cargados.
     */
    public function year(Request $request)
    {
        $driver = Driver::find((int) $request->query('driver_id'));
        if (! $driver) {
            return redirect()->route('liquidaciones.gastos.index')
                ->with('error', 'Selecciona un conductor para abrir su año.');
        }
        $anio = (int) ($request->query('anio') ?: now()->year);

        $existing = MonthlyExpense::where('driver_id', $driver->id)
            ->where('anio', $anio)->get()->keyBy('mes');

        return view('liquidaciones.gastos.year', compact('driver', 'anio', 'existing'));
    }

    /**
     * Guarda la grilla anual: upsert de los meses marcados como "registrar",
     * y borra los desmarcados que existieran.
     */
    public function saveYear(SaveMonthlyExpenseYearRequest $request)
    {
        $data = $request->validated();
        $driver = Driver::findOrFail($data['driver_id']);
        $anio = (int) $data['anio'];
        $uid = $request->user()->id;
        $meses = $request->input('meses', []);

        DB::transaction(function () use ($meses, $driver, $anio, $uid) {
            for ($m = 1; $m <= 12; $m++) {
                $row = $meses[$m] ?? [];
                $key = ['driver_id' => $driver->id, 'anio' => $anio, 'mes' => $m];

                if (empty($row['registrar'])) {
                    MonthlyExpense::where($key)->delete();
                    continue;
                }

                $exp = MonthlyExpense::firstOrNew($key);
                if (! $exp->exists) {
                    $exp->created_by = $uid;
                }
                $exp->vehicle_plate = $driver->vehicle_plate;
                foreach (self::CONCEPTOS as $c) {
                    $exp->{$c} = (int) ($row[$c] ?? 0);
                }
                $exp->otro_descripcion = $row['otro_descripcion'] ?? null;
                $exp->updated_by = $uid;
                $exp->save();
            }
        });

        return redirect()
            ->route('liquidaciones.gastos.year', ['driver_id' => $driver->id, 'anio' => $anio])
            ->with('success', "Gastos del año {$anio} guardados.");
    }

    public function destroy(MonthlyExpense $gasto)
    {
        $gasto->delete();

        return redirect()->route('liquidaciones.gastos.index')
            ->with('success', 'Gasto mensual eliminado.');
    }
}
