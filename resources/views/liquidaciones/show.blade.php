@extends('layouts.app')

@php
    $estadoBadge = [
        'borrador' => 'bg-secondary',
        'cerrada' => 'bg-success',
        'anulada' => 'bg-danger',
    ][$liq->estado] ?? 'bg-secondary';
@endphp

@section('content')
<div class="container-fluid">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">Liquidación #{{ $liq->id }} <span class="badge {{ $estadoBadge }} fs-6">{{ strtoupper($liq->estado) }}</span></h1>
            <small class="text-muted">Creada por {{ $liq->creator->name ?? '—' }} el {{ $liq->created_at?->format('Y-m-d H:i') }}</small>
        </div>
        <div class="btn-group">
            @if ($liq->isBorrador())
                <a href="{{ route('liquidaciones.edit', $liq) }}" class="btn btn-primary"><i class="bi bi-pencil"></i> Editar</a>
                <form method="POST" action="{{ route('liquidaciones.cerrar', $liq) }}" class="d-inline" onsubmit="return confirm('¿Cerrar la liquidación?')">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-lock"></i> Cerrar</button>
                </form>
                <form method="POST" action="{{ route('liquidaciones.destroy', $liq) }}" class="d-inline" onsubmit="return confirm('¿Eliminar esta liquidación?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
            @endif
            @if ($liq->isCerrada())
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalReabrir"><i class="bi bi-unlock"></i> Reabrir</button>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalAnular"><i class="bi bi-x-circle"></i> Anular</button>
            @endif
            <a href="{{ route('liquidaciones.pdf', $liq) }}" class="btn btn-info" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
            @if ($liq->hasManifiesto())
                <a href="{{ route('liquidaciones.manifiesto', $liq) }}" class="btn btn-outline-dark" target="_blank"><i class="bi bi-paperclip"></i> Manifiesto</a>
                @if ($liq->isBorrador())
                    <form method="POST" action="{{ route('liquidaciones.manifiesto.destroy', $liq) }}" class="d-inline" onsubmit="return confirm('¿Eliminar el manifiesto cargado?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" title="Eliminar manifiesto"><i class="bi bi-trash"></i> Manifiesto</button>
                    </form>
                @endif
            @endif
            <a href="{{ route('liquidaciones.index') }}" class="btn btn-secondary">Volver</a>
        </div>
    </div>

    @if ($liq->isAnulada())
        <div class="alert alert-danger">
            <strong>ANULADA.</strong> Motivo: {{ $liq->motivo_anulacion ?? '—' }}
        </div>
    @endif

    {{-- Cabecera --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>TRANSPORTE:</strong><br>{{ $liq->transportadora }}</div>
                <div class="col-md-3"><strong>CONDUCTOR:</strong><br>{{ $liq->driver->name ?? '—' }}</div>
                <div class="col-md-2"><strong>PLACA:</strong><br>{{ $liq->vehicle_plate }}</div>
                <div class="col-md-4"><strong>RUTA:</strong><br>{{ $liq->route->name ?? '—' }}</div>

                <div class="col-md-3 mt-3"><strong>ANTICIPO EMPRESA:</strong><br>{{ number_format($liq->anticipo_empresa, 0, ',', '.') }}</div>
                <div class="col-md-3 mt-3"><strong>ANTICIPO CONDUCTOR:</strong><br>{{ number_format($liq->anticipo_conductor, 0, ',', '.') }}</div>
                <div class="col-md-3 mt-3"><strong>SOBRE ANTICIPO:</strong><br>{{ number_format($liq->sobreanticipo, 0, ',', '.') }}</div>
                <div class="col-md-3 mt-3"><strong>DESCUENTOS (empresa):</strong><br>{{ number_format($liq->descuentos, 0, ',', '.') }}</div>

                <div class="col-md-3 mt-3"><strong>FECHA INICIO:</strong><br>{{ $liq->fecha_inicio?->format('Y-m-d') }}</div>
                <div class="col-md-3 mt-3"><strong>FECHA FIN:</strong><br>{{ $liq->fecha_fin?->format('Y-m-d') }}</div>

                <div class="col-md-4 mt-3"><strong>NÚMERO MFTO:</strong><br>{{ $liq->numero_mfto ?? '—' }}</div>
                <div class="col-md-4 mt-3"><strong>TELÉFONO EMPRESA:</strong><br>{{ $liq->telefono_empresa ?? '—' }}</div>
                <div class="col-md-4 mt-3"><strong>VALOR FLETE:</strong><br>{{ number_format($liq->valor_flete, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Gastos + Peajes --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><strong>Gastos</strong></div>
                <table class="table table-sm m-0">
                    <thead class="table-light"><tr><th>DESCRIPCIÓN</th><th class="text-end">VALOR</th><th class="text-end">GALONES</th></tr></thead>
                    <tbody>
                        @foreach ($liq->expenses->sortBy(fn($e) => $e->category->sort_order ?? 99) as $exp)
                            <tr>
                                <td>{{ $exp->category->name ?? '?' }}</td>
                                <td class="text-end">{{ number_format($exp->valor, 0, ',', '.') }}</td>
                                <td class="text-end">{{ $exp->galones !== null ? number_format($exp->galones, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3">
                <div class="card-header"><strong>Peajes</strong></div>
                <table class="table table-sm m-0">
                    <thead class="table-light"><tr><th>PEAJE</th><th>Sentido</th><th>Paga</th><th class="text-end">VALOR</th></tr></thead>
                    <tbody>
                        @foreach ($liq->tolls as $t)
                            <tr class="{{ !$t->is_used ? 'text-muted text-decoration-line-through' : '' }}{{ $t->is_adhoc ? ' table-warning' : '' }}">
                                <td>{{ $t->name }}</td>
                                <td>{{ strtoupper($t->direction) }}</td>
                                <td>
                                    @if ($t->paid_by === 'conductor')
                                        <span class="badge bg-danger">Conductor</span>
                                    @else
                                        <span class="badge bg-secondary">Empresa</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($t->valor, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Totales calculados --}}
    @php
        $sumatoriaGastos = $liq->sumatoria_gastos_operativos + $liq->descuentos;
        $anticiposConductor = $liq->anticipo_conductor + $liq->sobreanticipo;
    @endphp
    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">Totales</div>
        <div class="card-body">
            <div class="row">
                {{-- Columna izquierda: costos y relación con la empresa de transporte --}}
                <div class="col-md-6">
                    <table class="table table-sm mb-0">
                        <tr><th>Sumatoria de gastos</th><td class="text-end fs-6">{{ number_format($sumatoriaGastos, 0, ',', '.') }}</td></tr>
                        <tr><th>Sumatoria de peajes</th><td class="text-end fs-6">{{ number_format($liq->sumatoria_peajes, 0, ',', '.') }}</td></tr>
                        <tr><th>Suma de gastos total de viaje</th><td class="text-end fs-6 fw-bold">{{ number_format($liq->sumatoria_gastos_totales, 0, ',', '.') }}</td></tr>
                        <tr><th>Valor flete pactado</th><td class="text-end fs-6">{{ number_format($liq->valor_flete, 0, ',', '.') }}</td></tr>
                        <tr><th>Anticipo empresa de transporte</th><td class="text-end fs-6">{{ number_format($liq->anticipo_empresa, 0, ',', '.') }}</td></tr>
                        <tr><th>Saldo adeudado empresa de transporte</th><td class="text-end fs-6 fw-bold {{ $liq->saldo_pendiente >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($liq->saldo_pendiente, 0, ',', '.') }}</td></tr>
                    </table>
                </div>
                {{-- Columna derecha: relación con el conductor y rentabilidad --}}
                <div class="col-md-6">
                    <table class="table table-sm mb-0">
                        <tr><th>Anticipos conductor</th><td class="text-end fs-6">{{ number_format($anticiposConductor, 0, ',', '.') }}</td></tr>
                        <tr><th>Ant - gastos</th><td class="text-end fs-6 fw-bold {{ $liq->saldo_viaje >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($liq->saldo_viaje, 0, ',', '.') }}</td></tr>
                        <tr><th>A favor de</th><td class="text-end"><span class="badge bg-warning text-dark fs-6">{{ strtoupper($liq->a_favor_de) }}</span></td></tr>
                        <tr><th>Ganancia final de viaje</th><td class="text-end fs-4 fw-bold {{ $liq->ganancia_viaje >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($liq->ganancia_viaje, 0, ',', '.') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Historial de estados --}}
    @if ($liq->stateLogs->count() > 0)
        <div class="card mb-3">
            <div class="card-header"><strong>Historial</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm m-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Cambio</th><th>Motivo</th></tr></thead>
                    <tbody>
                        @foreach ($liq->stateLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->user->name ?? '—' }}</td>
                                <td>{{ $log->from_state }} → {{ $log->to_state }}</td>
                                <td>{{ $log->motivo ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- Modales Reabrir / Anular (Phase 8 los activa) --}}
<div class="modal fade" id="modalReabrir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('liquidaciones.reabrir', $liq) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Reabrir liquidación</h5></div>
                <div class="modal-body">
                    <label class="form-label">Motivo (mínimo 10 caracteres)</label>
                    <textarea name="motivo" rows="3" class="form-control" required minlength="10" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Reabrir</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="modalAnular" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('liquidaciones.anular', $liq) }}">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Anular liquidación</h5></div>
                <div class="modal-body">
                    <div class="alert alert-warning">La anulación es TERMINAL — no se puede reabrir.</div>
                    <label class="form-label">Motivo de anulación (mínimo 10 caracteres)</label>
                    <textarea name="motivo" rows="3" class="form-control" required minlength="10" maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Anular</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
