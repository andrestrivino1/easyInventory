@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>{{ $route->name }}
            @if ($route->active)<span class="badge bg-success fs-6">ACTIVA</span>@else<span class="badge bg-secondary fs-6">INACTIVA</span>@endif
        </h1>
        <div>
            <a href="{{ route('liquidaciones.routes.index') }}" class="btn btn-outline-secondary">← Volver</a>
            <a href="{{ route('liquidaciones.routes.edit', $route) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if ($route->descripcion)
        <div class="alert alert-light">{{ $route->descripcion }}</div>
    @endif

    <div class="card">
        <div class="card-header"><strong>Peajes ({{ $route->tolls->count() }})</strong></div>
        <table class="table table-sm m-0">
            <thead class="table-light"><tr><th>Orden</th><th>Peaje</th><th class="text-end">Valor sugerido</th><th>Sentido</th></tr></thead>
            <tbody>
                @forelse ($route->tolls as $t)
                    <tr>
                        <td>{{ $t->sort_order }}</td>
                        <td>{{ $t->name }}</td>
                        <td class="text-end">{{ number_format($t->suggested_value, 0, ',', '.') }}</td>
                        <td>{{ strtoupper($t->direction) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Esta ruta no tiene peajes configurados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
