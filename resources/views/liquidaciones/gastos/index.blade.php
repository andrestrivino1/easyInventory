@extends('layouts.app')

@php
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
@endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Gastos mensuales</h1>
        <div>
            <a href="{{ route('liquidaciones.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Liquidaciones</a>
            <a href="{{ route('liquidaciones.gastos.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nuevo gasto mensual</a>
        </div>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    {{-- Filtros --}}
    <form method="GET" class="card mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-0 small">Placa</label>
                    <input type="text" name="placa" class="form-control form-control-sm text-uppercase" value="{{ $placa }}" placeholder="Placa">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0 small">Año</label>
                    <input type="number" name="anio" class="form-control form-control-sm" value="{{ $anio }}" placeholder="Año" min="2020" max="2100">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-0 small">Mes</label>
                    <select name="mes" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach ($meses as $num => $nombre)
                            <option value="{{ $num }}" {{ (string)$mes === (string)$num ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex justify-content-end gap-2">
                    <a href="{{ route('liquidaciones.gastos.index') }}" class="btn btn-sm btn-link">Limpiar</a>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Listado --}}
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-sm table-hover m-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Placa</th>
                        <th>Conductor</th>
                        <th>Período</th>
                        <th class="text-end">Sueldo</th>
                        <th class="text-end">Seg. social</th>
                        <th class="text-end">Cuota banco</th>
                        <th class="text-end">Cuota tercero</th>
                        <th class="text-end">Satelital</th>
                        <th class="text-end">Seguro veh.</th>
                        <th class="text-end">Otro</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gastos as $g)
                        <tr>
                            <td>{{ $g->vehicle_plate ?? '— sin placa —' }}</td>
                            <td>{{ $g->driver->name ?? '—' }}</td>
                            <td>{{ str_pad($g->mes, 2, '0', STR_PAD_LEFT) }}/{{ $g->anio }}</td>
                            <td class="text-end">{{ number_format($g->sueldo_conductor, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($g->seguridad_social, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($g->cuota_banco, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($g->cuota_tercero, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($g->satelital, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($g->seguro_vehiculo, 0, ',', '.') }}</td>
                            <td class="text-end" title="{{ $g->otro_descripcion }}">{{ number_format($g->otro_valor, 0, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($g->total, 0, ',', '.') }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('liquidaciones.gastos.edit', $g) }}" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('liquidaciones.gastos.destroy', $g) }}" class="d-inline" onsubmit="return confirm('¿Eliminar este gasto mensual?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-center text-muted py-4">No hay gastos mensuales que coincidan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $gastos->links() }}</div>
</div>
@endsection
