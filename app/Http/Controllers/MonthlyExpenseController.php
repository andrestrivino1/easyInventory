<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonthlyExpenseRequest;
use App\Http\Requests\UpdateMonthlyExpenseRequest;
use App\Models\Driver;
use App\Models\MonthlyExpense;
use Illuminate\Http\Request;

class MonthlyExpenseController extends Controller
{
    public function index(Request $request)
    {
        $placa = $request->query('placa');
        $anio = $request->query('anio');
        $mes = $request->query('mes');

        $gastos = MonthlyExpense::query()
            ->with('driver')
            ->when($placa, fn ($q, $v) => $q->where('vehicle_plate', 'like', '%' . strtoupper($v) . '%'))
            ->when($anio, fn ($q, $v) => $q->where('anio', $v))
            ->when($mes, fn ($q, $v) => $q->where('mes', $v))
            ->orderByDesc('anio')->orderByDesc('mes')->orderBy('vehicle_plate')
            ->paginate(25)
            ->withQueryString();

        $placas = MonthlyExpense::whereNotNull('vehicle_plate')
            ->distinct()->orderBy('vehicle_plate')->pluck('vehicle_plate');

        return view('liquidaciones.gastos.index', compact('gastos', 'placas', 'placa', 'anio', 'mes'));
    }

    public function create()
    {
        return view('liquidaciones.gastos.create', [
            'gasto' => null,
            'drivers' => $this->driversList(),
        ]);
    }

    public function store(StoreMonthlyExpenseRequest $request)
    {
        $data = $request->validated();
        $driver = Driver::findOrFail($data['driver_id']);
        $userId = $request->user()->id;

        MonthlyExpense::create(array_merge($data, [
            'vehicle_plate' => $driver->vehicle_plate,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]));

        return redirect()->route('liquidaciones.gastos.index')
            ->with('success', 'Gasto mensual creado.');
    }

    public function edit(MonthlyExpense $gasto)
    {
        return view('liquidaciones.gastos.edit', [
            'gasto' => $gasto,
            'drivers' => $this->driversList(),
        ]);
    }

    public function update(UpdateMonthlyExpenseRequest $request, MonthlyExpense $gasto)
    {
        $data = $request->validated();
        $driver = Driver::findOrFail($data['driver_id']);

        $gasto->update(array_merge($data, [
            'vehicle_plate' => $driver->vehicle_plate,
            'updated_by' => $request->user()->id,
        ]));

        return redirect()->route('liquidaciones.gastos.index')
            ->with('success', 'Gasto mensual actualizado.');
    }

    public function destroy(MonthlyExpense $gasto)
    {
        $gasto->delete();

        return redirect()->route('liquidaciones.gastos.index')
            ->with('success', 'Gasto mensual eliminado.');
    }

    private function driversList()
    {
        return Driver::where('active', 1)->orderBy('name')->get(['id', 'name', 'vehicle_plate']);
    }
}
