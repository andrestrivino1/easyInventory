@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gastos mensuales</h1>
        <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Liquidaciones</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    {{-- Selector: abrir/crear el año de un conductor --}}
    <form method="GET" action="{{ route('liquidaciones.gastos.year') }}" class="card mb-4">
        <div class="card-header bg-light"><strong>Abrir año de un conductor</strong></div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Conductor</label>
                    <select name="driver_id" class="form-select" required>
                        <option value="">— Seleccionar —</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->vehicle_plate }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Año</label>
                    <input type="number" name="anio" class="form-control" min="2020" max="2100" value="{{ now()->year }}" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar3"></i> Abrir año</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Años ya registrados --}}
    <div class="card">
        <div class="card-header bg-light"><strong>Años registrados</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover m-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Placa</th>
                        <th>Conductor</th>
                        <th class="text-center">Año</th>
                        <th class="text-center">Meses</th>
                        <th class="text-end">Total año</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $g)
                        <tr>
                            <td>{{ $g->vehicle_plate ?? '— sin placa —' }}</td>
                            <td>{{ $g->driver->name ?? '—' }}</td>
                            <td class="text-center">{{ $g->anio }}</td>
                            <td class="text-center">{{ $g->meses }}</td>
                            <td class="text-end fw-bold">{{ number_format($g->total_anio, 0, ',', '.') }}</td>
                            <td class="text-center">
                                <a href="{{ route('liquidaciones.gastos.year', ['driver_id' => $g->driver_id, 'anio' => $g->anio]) }}"
                                   class="btn btn-sm btn-outline-primary" title="Abrir/editar año"><i class="bi bi-pencil-square"></i> Abrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay gastos mensuales registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
