{{--
    Form reusable create + edit. Espera:
    - $liq (Liquidacion|null)
    - $drivers, $routes, $categories
    - $expensesByCategory (Collection keyBy expense_category_id) — solo edit
--}}
@php
    $liq = $liq ?? null;
    $expensesByCategory = $expensesByCategory ?? collect();
    $existingTolls = $liq ? $liq->tolls : collect();
@endphp

<div x-data='liquidacionForm({!! json_encode([
    "categories" => $categories->map(fn($c) => ["id" => $c->id, "code" => $c->code, "name" => $c->name, "has_galones" => (bool)$c->has_galones])->all(),
    "existingExpenses" => $liq ? $liq->expenses->map(fn($e) => ["expense_category_id" => $e->expense_category_id, "valor" => (int)$e->valor, "galones" => $e->galones])->all() : [],
    "existingTolls" => $existingTolls->map(fn($t) => ["name" => $t->name, "valor" => (int)$t->valor, "sort_order" => (int)$t->sort_order, "direction" => $t->direction, "route_toll_id" => $t->route_toll_id, "is_adhoc" => (bool)$t->is_adhoc, "is_used" => (bool)$t->is_used])->values()->all(),
    "initialAnticipo" => (int)($liq->anticipo ?? 0),
    "initialSobreanticipo" => (int)($liq->sobreanticipo ?? 0),
    "initialFlete" => (int)($liq->valor_flete ?? 0),
    "routePeajesUrlTpl" => url("/liquidaciones/rutas/__ID__/peajes"),
    "driverInfoUrlTpl" => url("/liquidaciones/drivers/__ID__/info"),
]) !!})'>

    @csrf
    @if ($liq)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- Cabecera --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Conductor</label>
                    <select name="driver_id" class="form-select" required
                            x-on:change="loadDriver($event.target.value)">
                        <option value="">— Seleccionar —</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" data-plate="{{ $d->vehicle_plate }}"
                                {{ old('driver_id', $liq->driver_id ?? '') == $d->id ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->vehicle_plate }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">PLACA</label>
                    <input type="text" name="vehicle_plate" class="form-control text-uppercase"
                           value="{{ old('vehicle_plate', $liq->vehicle_plate ?? '') }}" required
                           x-ref="plateInput">
                </div>
                <div class="col-md-4">
                    <label class="form-label">RUTA</label>
                    <select name="route_id" class="form-select"
                            x-on:change="loadRouteTolls($event.target.value)">
                        <option value="">— Ninguna / Ad-hoc —</option>
                        @foreach ($routes as $r)
                            <option value="{{ $r->id }}"
                                {{ old('route_id', $liq->route_id ?? '') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">TRANSPORTE</label>
                    <input type="text" name="transportadora" class="form-control"
                           value="{{ old('transportadora', $liq->transportadora ?? '') }}" required maxlength="150">
                </div>

                <div class="col-md-3">
                    <label class="form-label">ANTICIPO</label>
                    <input type="number" name="anticipo" class="form-control" min="0" required
                           x-model.number="anticipo"
                           value="{{ old('anticipo', $liq->anticipo ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">SOBREANTICIPO</label>
                    <input type="number" name="sobreanticipo" class="form-control" min="0"
                           x-model.number="sobreanticipo"
                           value="{{ old('sobreanticipo', $liq->sobreanticipo ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">FECHA INICIO</label>
                    <input type="date" name="fecha_inicio" class="form-control" required
                           value="{{ old('fecha_inicio', isset($liq) && $liq->fecha_inicio ? $liq->fecha_inicio->format('Y-m-d') : '') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">FECHA FIN</label>
                    <input type="date" name="fecha_fin" class="form-control" required
                           value="{{ old('fecha_fin', isset($liq) && $liq->fecha_fin ? $liq->fecha_fin->format('Y-m-d') : '') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">NÚMERO DE MFTO</label>
                    <input type="text" name="numero_mfto" class="form-control"
                           value="{{ old('numero_mfto', $liq->numero_mfto ?? '') }}" maxlength="60">
                </div>
                <div class="col-md-4">
                    <label class="form-label">TELÉFONO EMPRESA</label>
                    <input type="text" name="telefono_empresa" class="form-control"
                           value="{{ old('telefono_empresa', $liq->telefono_empresa ?? '') }}" maxlength="40">
                </div>
                <div class="col-md-4">
                    <label class="form-label">VALOR FLETE</label>
                    <input type="number" name="valor_flete" class="form-control" min="0" required
                           x-model.number="valorFlete"
                           value="{{ old('valor_flete', $liq->valor_flete ?? 0) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Tabla de Gastos --}}
        <div class="col-lg-6">
            @include('liquidaciones.partials._expenses-table')
        </div>

        {{-- Tabla de Peajes --}}
        <div class="col-lg-6">
            @include('liquidaciones.partials._tolls-table')
        </div>
    </div>

    {{-- Espaciador para que el contenido no quede tapado por el sticky bar --}}
    <div style="height: 130px;"></div>

    {{-- Panel sticky con totales + acciones (siempre visible al fondo) --}}
    <div class="liq-sticky-bar shadow-lg">
        <div class="container-fluid py-2">
            <div class="row align-items-center g-2">
                <div class="col-md-9">
                    <div class="row text-center small">
                        <div class="col">
                            <div class="text-muted">Σ GASTOS</div>
                            <strong x-text="formatMoney(sumGastosOperativos)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">Σ PEAJES</div>
                            <strong x-text="formatMoney(sumPeajes)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">Σ TOTAL</div>
                            <strong x-text="formatMoney(sumGastosTotales)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">ANTICIPOS</div>
                            <strong x-text="formatMoney(totalAnticipos)" class="fs-6"></strong>
                        </div>
                        <div class="col border-start">
                            <div class="text-muted">SALDO</div>
                            <strong x-text="formatMoney(saldoViaje)" class="fs-5"
                                    :class="saldoViaje >= 0 ? 'text-success' : 'text-danger'"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">GANANCIA</div>
                            <strong x-text="formatMoney(gananciaViaje)" class="fs-5"
                                    :class="gananciaViaje >= 0 ? 'text-success' : 'text-danger'"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">A FAVOR</div>
                            <span x-text="aFavorDeLabel" class="badge bg-warning text-dark"></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <a href="{{ route('liquidaciones.index') }}" class="btn btn-sm btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-save"></i> Guardar Liquidación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .liq-sticky-bar {
        position: fixed;
        bottom: 40px; /* deja espacio para el main-footer fijo del layout */
        left: 210px;  /* ancho del sidebar */
        right: 0;
        background: #fff;
        border-top: 2px solid #3c8dbc;
        z-index: 1030;
    }
    @media (max-width: 991px) {
        .liq-sticky-bar { left: 0; bottom: 0; }
    }
    /* Tablas de gastos y peajes: filas más compactas para que la mayoría quepa en pantalla */
    .table-sm td, .table-sm th { padding: .25rem .4rem; vertical-align: middle; }
    .form-control-sm { padding: .15rem .35rem; font-size: .85rem; min-height: 28px; }
</style>
