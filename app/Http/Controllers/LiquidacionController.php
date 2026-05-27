<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiquidacionRequest;
use App\Http\Requests\UpdateLiquidacionRequest;
use App\Models\Driver;
use App\Models\ExpenseCategory;
use App\Models\Liquidacion;
use App\Models\LiquidacionRoute;
use App\Services\LiquidacionCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LiquidacionController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'fecha_desde', 'fecha_hasta', 'placa', 'driver_id',
            'route_id', 'transportadora', 'estado',
        ]);

        $base = Liquidacion::query()
            ->when($filters['fecha_desde'] ?? null, fn ($q, $v) => $q->where('fecha_inicio', '>=', $v))
            ->when($filters['fecha_hasta'] ?? null, fn ($q, $v) => $q->where('fecha_inicio', '<=', $v))
            ->when($filters['placa'] ?? null, fn ($q, $v) => $q->where('vehicle_plate', 'like', '%' . strtoupper($v) . '%'))
            ->when($filters['driver_id'] ?? null, fn ($q, $v) => $q->where('driver_id', $v))
            ->when($filters['route_id'] ?? null, fn ($q, $v) => $q->where('route_id', $v))
            ->when($filters['transportadora'] ?? null, fn ($q, $v) => $q->where('transportadora', 'like', '%' . $v . '%'))
            ->when(($filters['estado'] ?? 'all') !== 'all', fn ($q) => $q->where('estado', $filters['estado']));

        // Scoping para el rol placas: solo liquidaciones de sus conductores asignados.
        $user = $request->user();
        $assignedIds = $user->isPlacas() ? $user->assignedDriverIds() : null;
        if ($assignedIds !== null) {
            $base->whereIn('driver_id', $assignedIds);
        }

        $liquidaciones = (clone $base)
            ->with(['driver', 'route'])
            ->orderByDesc('fecha_inicio')
            ->paginate(25)
            ->withQueryString();

        $consolidado = \App\Services\LiquidacionCalculator::aggregate(clone $base);
        $consolidadoMensual = $request->boolean('agrupar_por_mes')
            ? \App\Services\LiquidacionCalculator::aggregateByMonth(clone $base)
            : null;

        $drivers = Driver::where('active', 1)
            ->when($assignedIds !== null, fn ($q) => $q->whereIn('id', $assignedIds))
            ->orderBy('name')->get(['id', 'name']);
        $routes = LiquidacionRoute::orderBy('name')->get(['id', 'name']);

        return view('liquidaciones.index', compact(
            'liquidaciones', 'consolidado', 'consolidadoMensual',
            'drivers', 'routes', 'filters'
        ));
    }

    public function create()
    {
        $user = auth()->user();
        $assignedIds = $user->isPlacas() ? $user->assignedDriverIds() : null;
        $drivers = Driver::where('active', 1)
            ->when($assignedIds !== null, fn ($q) => $q->whereIn('id', $assignedIds))
            ->orderBy('name')->get(['id', 'name', 'vehicle_plate', 'vehicle_owner', 'phone']);
        $routes = LiquidacionRoute::active()->orderBy('name')->get(['id', 'name', 'origen', 'destino']);
        $categories = ExpenseCategory::active()->ordered()->get();

        return view('liquidaciones.create', compact('drivers', 'routes', 'categories'));
    }

    public function store(StoreLiquidacionRequest $request)
    {
        $data = $request->validated();
        $userId = $request->user()->id;
        $manifiestoPath = $this->storeManifiestoFile($request);

        $liq = DB::transaction(function () use ($data, $userId, $manifiestoPath) {
            $liquidacion = Liquidacion::create([
                'driver_id' => $data['driver_id'],
                'vehicle_plate' => strtoupper($data['vehicle_plate']),
                'route_id' => $data['route_id'] ?? null,
                'transportadora' => $data['transportadora'],
                'telefono_empresa' => $data['telefono_empresa'] ?? null,
                'anticipo_empresa' => (int) $data['anticipo_empresa'],
                'anticipo_conductor' => (int) ($data['anticipo_conductor'] ?? 0),
                'descuentos' => (int) ($data['descuentos'] ?? 0),
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'numero_mfto' => $data['numero_mfto'] ?? null,
                'manifiesto_pdf_path' => $manifiestoPath,
                'valor_flete' => (int) $data['valor_flete'],
                'estado' => Liquidacion::ESTADO_BORRADOR,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->syncExpenses($liquidacion, $data['expenses'] ?? []);
            $this->syncTolls($liquidacion, $data['tolls'] ?? []);

            $liquidacion->refresh()->load('expenses', 'tolls');
            LiquidacionCalculator::recalcAndSave($liquidacion);

            return $liquidacion;
        });

        return redirect()
            ->route('liquidaciones.show', $liq)
            ->with('success', 'Liquidación creada en estado Borrador.');
    }

    public function show(Liquidacion $liquidacion)
    {
        $this->authorize('view', $liquidacion);

        $liquidacion->load([
            'driver',
            'route',
            'creator',
            'updater',
            'expenses.category',
            'tolls',
            'stateLogs.user',
        ]);

        return view('liquidaciones.show', ['liq' => $liquidacion]);
    }

    public function edit(Liquidacion $liquidacion)
    {
        $this->authorize('update', $liquidacion);

        $user = auth()->user();
        $assignedIds = $user->isPlacas() ? $user->assignedDriverIds() : null;
        $liquidacion->load(['expenses.category', 'tolls']);
        $drivers = Driver::where('active', 1)
            ->when($assignedIds !== null, fn ($q) => $q->whereIn('id', $assignedIds))
            ->orderBy('name')->get(['id', 'name', 'vehicle_plate']);
        $routes = LiquidacionRoute::active()->orderBy('name')->get(['id', 'name', 'origen', 'destino']);
        $categories = ExpenseCategory::active()->ordered()->get();

        // Indexar expenses existentes por category_id para pre-llenar el form
        $expensesByCategory = $liquidacion->expenses->keyBy('expense_category_id');

        return view('liquidaciones.edit', [
            'liq' => $liquidacion,
            'drivers' => $drivers,
            'routes' => $routes,
            'categories' => $categories,
            'expensesByCategory' => $expensesByCategory,
        ]);
    }

    public function update(UpdateLiquidacionRequest $request, Liquidacion $liquidacion)
    {
        $data = $request->validated();
        $userId = $request->user()->id;

        // Manifiesto: si llega uno nuevo, reemplaza al anterior (borra el archivo previo).
        $newManifiesto = $this->storeManifiestoFile($request);
        if ($newManifiesto && $liquidacion->manifiesto_pdf_path) {
            Storage::delete($liquidacion->manifiesto_pdf_path);
        }

        DB::transaction(function () use ($liquidacion, $data, $userId, $newManifiesto) {
            $attrs = [
                'driver_id' => $data['driver_id'],
                'vehicle_plate' => strtoupper($data['vehicle_plate']),
                'route_id' => $data['route_id'] ?? null,
                'transportadora' => $data['transportadora'],
                'telefono_empresa' => $data['telefono_empresa'] ?? null,
                'anticipo_empresa' => (int) $data['anticipo_empresa'],
                'anticipo_conductor' => (int) ($data['anticipo_conductor'] ?? 0),
                'descuentos' => (int) ($data['descuentos'] ?? 0),
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
                'numero_mfto' => $data['numero_mfto'] ?? null,
                'valor_flete' => (int) $data['valor_flete'],
                'updated_by' => $userId,
            ];
            if ($newManifiesto) {
                $attrs['manifiesto_pdf_path'] = $newManifiesto;
            }
            $liquidacion->update($attrs);

            $liquidacion->expenses()->delete();
            $liquidacion->tolls()->delete();
            $this->syncExpenses($liquidacion, $data['expenses'] ?? []);
            $this->syncTolls($liquidacion, $data['tolls'] ?? []);

            $liquidacion->load('expenses', 'tolls');
            LiquidacionCalculator::recalcAndSave($liquidacion);
        });

        return redirect()
            ->route('liquidaciones.show', $liquidacion)
            ->with('success', 'Liquidación actualizada.');
    }

    public function destroy(Liquidacion $liquidacion)
    {
        $this->authorize('delete', $liquidacion);
        $liquidacion->delete();

        return redirect()
            ->route('liquidaciones.index')
            ->with('success', 'Liquidación eliminada.');
    }

    // --- Manifiesto PDF ---

    public function manifiesto(Liquidacion $liquidacion)
    {
        $this->authorize('view', $liquidacion);

        abort_if(
            ! $liquidacion->manifiesto_pdf_path || ! Storage::exists($liquidacion->manifiesto_pdf_path),
            404,
            'Esta liquidación no tiene manifiesto cargado.'
        );

        return Storage::response($liquidacion->manifiesto_pdf_path, 'manifiesto-' . $liquidacion->id . '.pdf');
    }

    public function destroyManifiesto(Liquidacion $liquidacion)
    {
        $this->authorize('update', $liquidacion);
        abort_unless($liquidacion->isBorrador(), 403, 'Solo se puede modificar el manifiesto en estado Borrador.');

        if ($liquidacion->manifiesto_pdf_path && Storage::exists($liquidacion->manifiesto_pdf_path)) {
            Storage::delete($liquidacion->manifiesto_pdf_path);
        }
        $liquidacion->update(['manifiesto_pdf_path' => null]);

        return redirect()
            ->route('liquidaciones.show', $liquidacion)
            ->with('success', 'Manifiesto eliminado.');
    }

    // --- AJAX helpers ---

    public function driverInfo(Driver $driver)
    {
        $user = auth()->user();
        if ($user->isPlacas() && !in_array($driver->id, $user->assignedDriverIds(), true)) {
            abort(403);
        }

        return response()->json([
            'id' => $driver->id,
            'name' => $driver->name,
            'vehicle_plate' => $driver->vehicle_plate,
            'vehicle_owner' => $driver->vehicle_owner,
            'phone' => $driver->phone,
        ]);
    }

    public function duplicateCheck(Request $request)
    {
        $placa = strtoupper(trim((string) $request->query('placa')));
        $mfto = trim((string) $request->query('numero_mfto'));
        $exceptId = $request->query('except_id');

        if ($placa === '' || $mfto === '') {
            return response()->json(['duplicate' => false]);
        }

        $query = Liquidacion::where('vehicle_plate', $placa)
            ->where('numero_mfto', $mfto)
            ->where('estado', '!=', Liquidacion::ESTADO_ANULADA);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $existing = $query->select('id', 'fecha_inicio')->first();

        if (!$existing) {
            return response()->json(['duplicate' => false]);
        }

        return response()->json([
            'duplicate' => true,
            'existing_id' => $existing->id,
            'existing_fecha_inicio' => $existing->fecha_inicio?->format('Y-m-d'),
        ]);
    }

    // --- Transiciones de estado (Phase 8) ---

    public function cerrar(Request $request, Liquidacion $liquidacion)
    {
        $this->authorize('close', $liquidacion);
        try {
            app(\App\Services\LiquidacionStateMachine::class)->close($liquidacion, $request->user());
        } catch (\DomainException $e) {
            return redirect()->route('liquidaciones.show', $liquidacion)->with('error', $e->getMessage());
        }
        return redirect()->route('liquidaciones.show', $liquidacion)->with('success', 'Liquidación cerrada.');
    }

    public function reabrir(Request $request, Liquidacion $liquidacion)
    {
        $this->authorize('reopen', $liquidacion);
        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        try {
            app(\App\Services\LiquidacionStateMachine::class)
                ->reopen($liquidacion, $request->user(), $request->input('motivo'));
        } catch (\DomainException $e) {
            return redirect()->route('liquidaciones.show', $liquidacion)->with('error', $e->getMessage());
        }
        return redirect()->route('liquidaciones.show', $liquidacion)->with('success', 'Liquidación reabierta. Ahora está en Borrador.');
    }

    public function anular(Request $request, Liquidacion $liquidacion)
    {
        $this->authorize('cancel', $liquidacion);
        $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ]);
        try {
            app(\App\Services\LiquidacionStateMachine::class)
                ->cancel($liquidacion, $request->user(), $request->input('motivo'));
        } catch (\DomainException $e) {
            return redirect()->route('liquidaciones.show', $liquidacion)->with('error', $e->getMessage());
        }
        return redirect()->route('liquidaciones.show', $liquidacion)->with('success', 'Liquidación anulada.');
    }

    // --- Internos ---

    /**
     * Guarda el PDF del manifiesto en storage/app/manifiestos y devuelve su ruta,
     * o null si no se subió archivo.
     */
    protected function storeManifiestoFile(Request $request): ?string
    {
        if (! $request->hasFile('manifiesto_pdf')) {
            return null;
        }

        return $request->file('manifiesto_pdf')->store('manifiestos');
    }

    protected function syncExpenses(Liquidacion $liq, array $expenses): void
    {
        foreach ($expenses as $exp) {
            $valor = (int) ($exp['valor'] ?? 0);
            $galones = isset($exp['galones']) && $exp['galones'] !== '' ? (float) $exp['galones'] : null;

            if ($valor === 0 && $galones === null) {
                continue; // no escribir filas vacías
            }

            $liq->expenses()->create([
                'expense_category_id' => (int) $exp['expense_category_id'],
                'valor' => $valor,
                'galones' => $galones,
            ]);
        }
    }

    protected function syncTolls(Liquidacion $liq, array $tolls): void
    {
        foreach ($tolls as $t) {
            $liq->tolls()->create([
                'route_toll_id' => $t['route_toll_id'] ?? null,
                'name' => $t['name'],
                'valor' => (int) ($t['valor'] ?? 0),
                'sort_order' => (int) $t['sort_order'],
                'direction' => $t['direction'] ?? 'ida',
                'is_adhoc' => (bool) ($t['is_adhoc'] ?? false),
                'is_used' => array_key_exists('is_used', $t) ? (bool) $t['is_used'] : true,
                'paid_by' => ($t['paid_by'] ?? 'empresa') === 'conductor' ? 'conductor' : 'empresa',
            ]);
        }
    }
}
