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

{{-- Fallback inline: garantiza que liquidacionForm exista aunque public/js/app.js
     esté desactualizado en producción. Se registra en alpine:init para sobrescribir
     cualquier versión parcial cargada por el bundle. --}}
<script>
document.addEventListener('alpine:init', function () {
    window.liquidacionForm = function (config) {
        return {
            categories: config.categories || [],
            routePeajesUrlTpl: config.routePeajesUrlTpl,
            driverInfoUrlTpl: config.driverInfoUrlTpl,
            anticipoEmpresa: parseInt(config.initialAnticipoEmpresa || 0, 10),
            anticipoConductor: parseInt(config.initialAnticipoConductor || 0, 10),
            sobreanticipo: parseInt(config.initialSobreanticipo || 0, 10),
            descuentos: parseInt(config.initialDescuentos || 0, 10),
            valorFlete: parseInt(config.initialFlete || 0, 10),
            expenses: [],
            tolls: [],
            init() {
                const existingMap = {};
                (config.existingExpenses || []).forEach(e => { existingMap[e.expense_category_id] = e; });
                this.expenses = this.categories.map(cat => {
                    const existing = existingMap[cat.id];
                    return {
                        expense_category_id: cat.id,
                        category_name: cat.name,
                        has_galones: cat.has_galones,
                        valor: existing ? parseInt(existing.valor, 10) || 0 : 0,
                        galones: existing && existing.galones !== null ? parseFloat(existing.galones) : null,
                    };
                });
                this.tolls = (config.existingTolls || []).map(t => ({
                    name: t.name,
                    valor: parseInt(t.valor, 10) || 0,
                    sort_order: parseInt(t.sort_order, 10),
                    direction: t.direction || 'ida',
                    route_toll_id: t.route_toll_id || null,
                    is_adhoc: !!t.is_adhoc,
                    is_used: t.is_used !== false,
                    paid_by: t.paid_by || 'empresa',
                }));
            },
            get sumGastosOperativos() {
                return this.expenses.reduce((s, e) => s + (parseInt(e.valor, 10) || 0), 0);
            },
            get sumPeajes() {
                return this.tolls.filter(t => t.is_used).reduce((s, t) => s + (parseInt(t.valor, 10) || 0), 0);
            },
            get sumPeajesConductor() {
                return this.tolls.filter(t => t.is_used && t.paid_by === 'conductor').reduce((s, t) => s + (parseInt(t.valor, 10) || 0), 0);
            },
            get sumGastos() { return this.sumGastosOperativos + (parseInt(this.descuentos, 10) || 0); },
            get sumGastosTotales() { return this.sumGastos + this.sumPeajes; },
            get anticiposConductor() { return (parseInt(this.anticipoConductor, 10) || 0) + (parseInt(this.sobreanticipo, 10) || 0); },
            get totalAnticipos() {
                return (parseInt(this.anticipoEmpresa, 10) || 0) + (parseInt(this.anticipoConductor, 10) || 0) + (parseInt(this.sobreanticipo, 10) || 0);
            },
            get saldoAdeudadoEmpresa() { return (parseInt(this.valorFlete, 10) || 0) - (parseInt(this.anticipoEmpresa, 10) || 0); },
            get antGastos() { return this.sumGastos - this.anticiposConductor; },
            get gananciaViaje() { return (parseInt(this.valorFlete, 10) || 0) - this.sumGastosTotales; },
            get aFavorDeLabel() {
                const s = this.antGastos;
                if (s > 0) return 'CONDUCTOR';
                if (s < 0) return 'EMPRESA';
                return 'NINGUNO';
            },
            recalc() {},
            formatMoney(n) {
                return Math.round(parseFloat(n) || 0).toLocaleString('es-CO');
            },
            loadDriver(driverId) {
                if (!driverId) return;
                fetch(this.driverInfoUrlTpl.replace('__ID__', driverId), {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.vehicle_plate && this.$refs.plateInput) {
                        this.$refs.plateInput.value = data.vehicle_plate;
                    }
                })
                .catch(err => console.error('No se pudo cargar conductor:', err));
            },
            loadRouteTolls(routeId) {
                if (!routeId) return;
                fetch(this.routePeajesUrlTpl.replace('__ID__', routeId), {
                    headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    const arr = (data && data.tolls) ? data.tolls : [];
                    this.tolls = arr.map(t => ({
                        name: t.name,
                        valor: parseInt(t.suggested_value, 10) || 0,
                        sort_order: parseInt(t.sort_order, 10),
                        direction: t.direction || 'ida',
                        route_toll_id: t.id,
                        is_adhoc: false,
                        is_used: true,
                        paid_by: 'empresa',
                    }));
                })
                .catch(err => console.error('No se pudieron cargar los peajes de la ruta:', err));
            },
            addAdhocToll() {
                const nextOrder = this.tolls.length > 0
                    ? Math.max(...this.tolls.map(t => t.sort_order)) + 1
                    : 1;
                this.tolls.push({
                    name: '', valor: 0, sort_order: nextOrder,
                    direction: 'ida', route_toll_id: null, is_adhoc: true, is_used: true,
                    paid_by: 'empresa',
                });
            },
            removeToll(idx) { this.tolls.splice(idx, 1); },
        };
    };
});
</script>

<div x-data='liquidacionForm({!! json_encode([
    "categories" => $categories->map(fn($c) => ["id" => $c->id, "code" => $c->code, "name" => $c->name, "has_galones" => (bool)$c->has_galones])->all(),
    "existingExpenses" => $liq ? $liq->expenses->map(fn($e) => ["expense_category_id" => $e->expense_category_id, "valor" => (int)$e->valor, "galones" => $e->galones])->all() : [],
    "existingTolls" => $existingTolls->map(fn($t) => ["name" => $t->name, "valor" => (int)$t->valor, "sort_order" => (int)$t->sort_order, "direction" => $t->direction, "route_toll_id" => $t->route_toll_id, "is_adhoc" => (bool)$t->is_adhoc, "is_used" => (bool)$t->is_used, "paid_by" => $t->paid_by])->values()->all(),
    "initialAnticipoEmpresa" => (int)($liq->anticipo_empresa ?? 0),
    "initialAnticipoConductor" => (int)($liq->anticipo_conductor ?? 0),
    "initialSobreanticipo" => (int)($liq->sobreanticipo ?? 0),
    "initialDescuentos" => (int)($liq->descuentos ?? 0),
    "initialFlete" => (int)($liq->valor_flete ?? 0),
    {{-- Solo la ruta (sin esquema/host) para que el fetch sea siempre same-origin
         y la cookie de sesión viaje. Conserva el subdirectorio base (p.ej. /inventory). --}}
    "routePeajesUrlTpl" => parse_url(url("/liquidaciones/rutas/__ID__/peajes"), PHP_URL_PATH),
    "driverInfoUrlTpl" => parse_url(url("/liquidaciones/drivers/__ID__/info"), PHP_URL_PATH),
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
                    <label class="form-label">ANTICIPO EMPRESA</label>
                    <input type="number" name="anticipo_empresa" class="form-control" min="0" required
                           x-model.number="anticipoEmpresa"
                           value="{{ old('anticipo_empresa', $liq->anticipo_empresa ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">ANTICIPO CONDUCTOR</label>
                    <input type="number" name="anticipo_conductor" class="form-control" min="0"
                           x-model.number="anticipoConductor"
                           value="{{ old('anticipo_conductor', $liq->anticipo_conductor ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">SOBRE ANTICIPO</label>
                    <input type="number" name="sobreanticipo" class="form-control" min="0"
                           x-model.number="sobreanticipo"
                           value="{{ old('sobreanticipo', $liq->sobreanticipo ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">SALDO ADEUDADO EMPRESA</label>
                    <input type="text" class="form-control fw-bold" readonly
                           x-bind:value="formatMoney(saldoAdeudadoEmpresa)"
                           x-bind:class="saldoAdeudadoEmpresa >= 0 ? 'text-success' : 'text-danger'">
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
                <div class="col-md-6">
                    <label class="form-label">MANIFIESTO (PDF)</label>
                    <input type="file" name="manifiesto_pdf" class="form-control" accept="application/pdf">
                    @if ($liq && $liq->hasManifiesto())
                        <small class="text-muted d-block mt-1">
                            Actual: <a href="{{ route('liquidaciones.manifiesto', $liq) }}" target="_blank">ver manifiesto</a>.
                            Subir uno nuevo lo reemplaza.
                        </small>
                    @endif
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
                            <div class="text-muted" title="Gastos operativos + descuento empresa">Σ GASTOS</div>
                            <strong x-text="formatMoney(sumGastos)" class="fs-6"></strong>
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
                            <div class="text-muted">FLETE</div>
                            <strong x-text="formatMoney(valorFlete)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted">ANT. EMPRESA</div>
                            <strong x-text="formatMoney(anticipoEmpresa)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted" title="Valor flete − anticipo empresa">SALDO ADEUD. EMP.</div>
                            <strong x-text="formatMoney(saldoAdeudadoEmpresa)" class="fs-6"
                                    :class="saldoAdeudadoEmpresa >= 0 ? 'text-success' : 'text-danger'"></strong>
                        </div>
                        <div class="col border-start">
                            <div class="text-muted" title="Anticipo conductor + sobre anticipo">ANT. CONDUCTOR</div>
                            <strong x-text="formatMoney(anticiposConductor)" class="fs-6"></strong>
                        </div>
                        <div class="col">
                            <div class="text-muted" title="Sumatoria de gastos − anticipos conductor">ANT - GASTOS</div>
                            <strong x-text="formatMoney(antGastos)" class="fs-5"
                                    :class="antGastos >= 0 ? 'text-success' : 'text-danger'"></strong>
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
