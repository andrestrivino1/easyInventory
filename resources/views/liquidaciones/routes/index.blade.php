@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Rutas — Liquidación de Viajes</h1>
        <div>
            <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary">← Liquidaciones</a>
            <a href="{{ route('liquidaciones.routes.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nueva ruta</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm m-0">
                <thead class="table-light">
                    <tr>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th class="text-end"># Peajes</th>
                        <th class="text-end"># Liquidaciones</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($routes as $route)
                        <tr class="{{ !$route->active ? 'text-muted' : '' }}">
                            <td>{{ $route->origen }}</td>
                            <td>{{ $route->destino }}</td>
                            <td class="text-end">{{ $route->tolls_count }}</td>
                            <td class="text-end">{{ $route->liquidaciones_count }}</td>
                            <td>
                                @if ($route->active)
                                    <span class="badge bg-success">ACTIVA</span>
                                @else
                                    <span class="badge bg-secondary">INACTIVA</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('liquidaciones.routes.show', $route) }}" class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('liquidaciones.routes.edit', $route) }}" class="btn btn-sm btn-outline-warning" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('liquidaciones.routes.toggle-active', $route) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="{{ $route->active ? 'Inactivar' : 'Activar' }}"><i class="bi {{ $route->active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i></button>
                                </form>
                                @if ($route->liquidaciones_count === 0)
                                    <form method="POST" action="{{ route('liquidaciones.routes.destroy', $route) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta ruta?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No hay rutas configuradas. <a href="{{ route('liquidaciones.routes.create') }}">Crea la primera</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $routes->links() }}</div>
</div>
@endsection
