@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Liquidaciones de Viaje</h1>
        <div>
            @can('viewAny', App\Models\LiquidacionRoute::class)
                <a href="{{ route('liquidaciones.routes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-signpost-split"></i> Rutas</a>
            @endcan
            <a href="{{ route('liquidaciones.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Filtros --}}
    <form method="GET" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2">
                <div class="col-md-2"><input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $filters['fecha_desde'] ?? '' }}" placeholder="Desde"></div>
                <div class="col-md-2"><input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $filters['fecha_hasta'] ?? '' }}" placeholder="Hasta"></div>
                <div class="col-md-2"><input type="text" name="placa" class="form-control form-control-sm text-uppercase" value="{{ $filters['placa'] ?? '' }}" placeholder="Placa"></div>
                <div class="col-md-2">
                    <select name="driver_id" class="form-select form-select-sm">
                        <option value="">Conductor</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" {{ ($filters['driver_id'] ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="route_id" class="form-select form-select-sm">
                        <option value="">Ruta</option>
                        @foreach ($routes as $r)
                            <option value="{{ $r->id }}" {{ ($filters['route_id'] ?? null) == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="text" name="transportadora" class="form-control form-control-sm" value="{{ $filters['transportadora'] ?? '' }}" placeholder="Transporte"></div>
                <div class="col-md-2 mt-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="all">Todos los estados</option>
                        <option value="borrador" {{ ($filters['estado'] ?? null) === 'borrador' ? 'selected' : '' }}>Borrador</option>
                        <option value="cerrada"  {{ ($filters['estado'] ?? null) === 'cerrada' ? 'selected' : '' }}>Cerrada</option>
                        <option value="anulada"  {{ ($filters['estado'] ?? null) === 'anulada' ? 'selected' : '' }}>Anulada</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2 form-check ms-2 d-flex align-items-center">
                    <input type="checkbox" class="form-check-input me-1" id="agruparPorMes" name="agrupar_por_mes" value="1" {{ request()->boolean('agrupar_por_mes') ? 'checked' : '' }}>
                    <label class="form-check-label" for="agruparPorMes">Agrupar consolidado por mes</label>
                </div>
                <div class="col-md-7 mt-2 d-flex justify-content-end">
                    <a href="{{ route('liquidaciones.index') }}" class="btn btn-sm btn-link">Limpiar</a>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Consolidado del periodo --}}
    @include('liquidaciones.partials._consolidado-panel', ['c' => $consolidado, 'title' => 'Consolidado del periodo (sin anuladas)'])

    {{-- Consolidado por mes --}}
    @if ($consolidadoMensual && $consolidadoMensual->count() > 0)
        <div class="card mb-3">
            <div class="card-header bg-light"><strong>Desglose por mes</strong></div>
            <div class="card-body">
                @foreach ($consolidadoMensual as $mes)
                    @include('liquidaciones.partials._consolidado-panel', ['c' => $mes, 'title' => 'Mes ' . $mes['periodo']])
                @endforeach
            </div>
        </div>
    @endif

    {{-- Listado --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm table-hover m-0">
                <thead class="table-light">
                    <tr>
                        <th>Estado</th>
                        <th>Placa</th>
                        <th>Ruta</th>
                        <th>Conductor</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Transporte</th>
                        <th class="text-end">Gastos</th>
                        <th class="text-end">Peajes</th>
                        <th class="text-end">Saldo</th>
                        <th class="text-end">Ganancia</th>
                        <th>A favor</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liquidaciones as $liq)
                        @php
                            $badge = ['borrador' => 'bg-secondary', 'cerrada' => 'bg-success', 'anulada' => 'bg-danger'][$liq->estado] ?? 'bg-secondary';
                        @endphp
                        <tr class="{{ $liq->estado === 'anulada' ? 'text-muted' : '' }}">
                            <td><span class="badge {{ $badge }}">{{ strtoupper($liq->estado) }}</span></td>
                            <td>{{ $liq->vehicle_plate }}</td>
                            <td>{{ $liq->route->name ?? '—' }}</td>
                            <td>{{ $liq->driver->name ?? '—' }}</td>
                            <td>{{ $liq->fecha_inicio?->format('Y-m-d') }}</td>
                            <td>{{ $liq->fecha_fin?->format('Y-m-d') }}</td>
                            <td>{{ $liq->transportadora }}</td>
                            <td class="text-end">{{ number_format($liq->sumatoria_gastos_operativos, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($liq->sumatoria_peajes, 0, ',', '.') }}</td>
                            <td class="text-end {{ $liq->saldo_viaje >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($liq->saldo_viaje, 0, ',', '.') }}</td>
                            <td class="text-end {{ $liq->ganancia_viaje >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($liq->ganancia_viaje, 0, ',', '.') }}</td>
                            <td>{{ strtoupper($liq->a_favor_de) }}</td>
                            <td>
                                <a href="{{ route('liquidaciones.show', $liq) }}" class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('liquidaciones.pdf', $liq) }}" target="_blank" class="btn btn-sm btn-outline-info" title="PDF"><i class="bi bi-file-pdf"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-muted py-4">No hay liquidaciones que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $liquidaciones->links() }}</div>
</div>
@endsection
