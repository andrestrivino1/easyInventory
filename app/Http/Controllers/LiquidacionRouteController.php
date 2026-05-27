<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLiquidacionRouteRequest;
use App\Http\Requests\UpdateLiquidacionRouteRequest;
use App\Models\LiquidacionRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LiquidacionRouteController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', LiquidacionRoute::class);

        $routes = LiquidacionRoute::withCount('tolls')
            ->withCount('liquidaciones')
            ->orderBy('active', 'desc')
            ->orderBy('name')
            ->paginate(50);

        return view('liquidaciones.routes.index', compact('routes'));
    }

    public function create()
    {
        $this->authorize('create', LiquidacionRoute::class);

        return view('liquidaciones.routes.create');
    }

    public function store(StoreLiquidacionRouteRequest $request)
    {
        $data = $request->validated();

        $route = DB::transaction(function () use ($data) {
            $route = LiquidacionRoute::create([
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'vehicle_type' => $data['vehicle_type'],
                'descripcion' => $data['descripcion'] ?? null,
                'active' => (bool) ($data['active'] ?? true),
            ]);

            foreach (($data['tolls'] ?? []) as $t) {
                $route->tolls()->create([
                    'name' => $t['name'],
                    'suggested_value' => (int) ($t['suggested_value'] ?? 0),
                    'sort_order' => (int) $t['sort_order'],
                    'direction' => $t['direction'],
                ]);
            }

            return $route;
        });

        return redirect()
            ->route('liquidaciones.routes.show', $route)
            ->with('success', "Ruta '{$route->name}' creada con " . $route->tolls()->count() . ' peajes.');
    }

    public function show(LiquidacionRoute $route)
    {
        $route->load('tolls');
        return view('liquidaciones.routes.show', compact('route'));
    }

    public function edit(LiquidacionRoute $route)
    {
        $this->authorize('update', $route);
        $route->load('tolls');
        return view('liquidaciones.routes.edit', compact('route'));
    }

    public function update(UpdateLiquidacionRouteRequest $request, LiquidacionRoute $route)
    {
        $data = $request->validated();

        DB::transaction(function () use ($route, $data) {
            $route->update([
                'origen' => $data['origen'],
                'destino' => $data['destino'],
                'vehicle_type' => $data['vehicle_type'],
                'descripcion' => $data['descripcion'] ?? null,
                'active' => (bool) ($data['active'] ?? true),
            ]);

            $route->tolls()->delete();
            foreach (($data['tolls'] ?? []) as $t) {
                $route->tolls()->create([
                    'name' => $t['name'],
                    'suggested_value' => (int) ($t['suggested_value'] ?? 0),
                    'sort_order' => (int) $t['sort_order'],
                    'direction' => $t['direction'],
                ]);
            }
        });

        return redirect()
            ->route('liquidaciones.routes.show', $route)
            ->with('success', 'Ruta actualizada.');
    }

    public function destroy(LiquidacionRoute $route)
    {
        $this->authorize('delete', $route);

        if ($route->liquidaciones()->count() > 0) {
            return redirect()
                ->route('liquidaciones.routes.index')
                ->with('error', 'No se puede eliminar la ruta porque tiene liquidaciones asociadas. Inactívala en su lugar.');
        }

        $route->delete();

        return redirect()
            ->route('liquidaciones.routes.index')
            ->with('success', 'Ruta eliminada.');
    }

    public function toggleActive(LiquidacionRoute $route)
    {
        $this->authorize('toggleActive', $route);
        $route->update(['active' => !$route->active]);

        return redirect()
            ->route('liquidaciones.routes.index')
            ->with('success', "Ruta '{$route->name}' " . ($route->active ? 'activada' : 'inactivada') . '.');
    }

    public function peajes(LiquidacionRoute $route)
    {
        return response()->json([
            'tolls' => $route->tolls()->orderBy('sort_order')->get([
                'id', 'name', 'suggested_value', 'sort_order', 'direction',
            ]),
        ]);
    }
}
