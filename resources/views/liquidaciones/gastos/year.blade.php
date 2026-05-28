@extends('layouts.app')

@php
    $nombresMes = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $conceptos = [
        'sueldo_conductor' => 'Sueldo',
        'seguridad_social' => 'Seg. social',
        'cuota_banco' => 'Cuota banco',
        'cuota_tercero' => 'Cuota 3ero',
        'satelital' => 'Satelital',
        'seguro_vehiculo' => 'Seguro veh.',
        'otro_valor' => 'Otro',
    ];
    $alpineMeses = [];
    for ($m = 1; $m <= 12; $m++) {
        $e = $existing->get($m);
        $row = ['mes' => $m, 'nombre' => $nombresMes[$m], 'registrar' => (bool) $e, 'otro_descripcion' => $e->otro_descripcion ?? ''];
        foreach (array_keys($conceptos) as $c) {
            $row[$c] = (int) ($e->$c ?? 0);
        }
        $alpineMeses[] = $row;
    }
    $plantillaInit = array_fill_keys(array_keys($conceptos), 0);
    $plantillaInit['otro_descripcion'] = '';
@endphp

@section('content')
<div class="container-fluid"
     x-data="{
        meses: {{ \Illuminate\Support\Js::from($alpineMeses) }},
        conceptos: {{ \Illuminate\Support\Js::from(array_keys($conceptos)) }},
        rowTotal(r) { return this.conceptos.reduce((s, c) => s + (parseInt(r[c], 10) || 0), 0); },
        get yearTotal() { return this.meses.reduce((s, r) => s + (r.registrar ? this.rowTotal(r) : 0), 0); },
        touch(r) { r.registrar = true; },
        fmt(n) { return (Math.round(parseFloat(n)) || 0).toLocaleString('es-CO'); },
        plantilla: {{ \Illuminate\Support\Js::from($plantillaInit) }},
        selMeses: [],
        marcarTodos() { this.selMeses = this.meses.map(r => r.mes); },
        limpiarSeleccion() { this.selMeses = []; },
        aplicar() {
            this.selMeses.forEach(m => {
                const r = this.meses.find(x => x.mes === m);
                if (! r) return;
                this.conceptos.forEach(c => { r[c] = parseInt(this.plantilla[c], 10) || 0; });
                r.otro_descripcion = this.plantilla.otro_descripcion;
                r.registrar = true;
            });
        }
     }">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="mb-1">Gastos mensuales — {{ $anio }}</h1>
            <span class="text-muted">{{ $driver->name }} · Placa {{ $driver->vehicle_plate ?? '— sin placa —' }}</span>
        </div>
        <a href="{{ route('liquidaciones.gastos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    {{-- Aplicar los mismos valores a varios meses (atajo; no se envía, solo rellena la grilla) --}}
    <div class="card mb-3 border-primary">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-magic"></i> Aplicar los mismos valores a varios meses
        </div>
        <div class="card-body">
            <div class="row g-2 mb-3">
                @foreach ($conceptos as $code => $label)
                    <div class="col-md-3">
                        <label class="form-label mb-0 small">{{ $label }}</label>
                        <input type="number" min="0" class="form-control form-control-sm text-end"
                               x-model.number="plantilla.{{ $code }}">
                    </div>
                @endforeach
                <div class="col-md-3">
                    <label class="form-label mb-0 small">Otro (descripción)</label>
                    <input type="text" maxlength="150" class="form-control form-control-sm"
                           x-model="plantilla.otro_descripcion">
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                <span class="small text-muted me-1">Meses:</span>
                <template x-for="r in meses" :key="'sel' + r.mes">
                    <label class="btn btn-sm btn-outline-secondary mb-0"
                           :class="selMeses.includes(r.mes) ? 'active' : ''">
                        <input type="checkbox" class="d-none" :value="r.mes" x-model.number="selMeses">
                        <span x-text="r.nombre.substring(0, 3)"></span>
                    </label>
                </template>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-sm btn-link p-0" x-on:click="marcarTodos()">Todos</button>
                <button type="button" class="btn btn-sm btn-link p-0" x-on:click="limpiarSeleccion()">Ninguno</button>
                <button type="button" class="btn btn-sm btn-primary ms-auto" x-on:click="aplicar()"
                        x-bind:disabled="selMeses.length === 0">
                    <i class="bi bi-check2-all"></i> Aplicar a <span x-text="selMeses.length"></span> mes(es)
                </button>
            </div>
            <small class="text-muted">Rellena los meses seleccionados con estos valores y los marca como "Registrar". Luego puedes ajustar cualquiera en la grilla antes de guardar.</small>
        </div>
    </div>

    <form method="POST" action="{{ route('liquidaciones.gastos.year.save') }}">
        @csrf
        <input type="hidden" name="driver_id" value="{{ $driver->id }}">
        <input type="hidden" name="anio" value="{{ $anio }}">

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-sm table-bordered m-0 align-middle gastos-grid">
                    <thead class="table-light">
                        <tr>
                            <th style="width:9%">Mes</th>
                            <th class="text-center" style="width:6%" title="Marca los meses que quieres registrar">Reg.</th>
                            @foreach ($conceptos as $label)
                                <th class="text-end">{{ $label }}</th>
                            @endforeach
                            <th>Otro (descripción)</th>
                            <th class="text-end" style="width:9%">Total mes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in meses" :key="r.mes">
                            <tr :class="r.registrar ? '' : 'text-muted'">
                                <td x-text="r.nombre"></td>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input" value="1"
                                           :name="'meses[' + r.mes + '][registrar]'"
                                           x-model="r.registrar">
                                </td>
                                <template x-for="c in conceptos" :key="c">
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-end"
                                               :name="'meses[' + r.mes + '][' + c + ']'"
                                               x-model.number="r[c]"
                                               x-on:input="touch(r)">
                                    </td>
                                </template>
                                <td>
                                    <input type="text" maxlength="150" class="form-control form-control-sm"
                                           :name="'meses[' + r.mes + '][otro_descripcion]'"
                                           x-model="r.otro_descripcion"
                                           x-on:input="touch(r)">
                                </td>
                                <td class="text-end fw-bold" x-text="fmt(rowTotal(r))"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="{{ count($conceptos) + 3 }}" class="text-end">TOTAL AÑO (meses registrados)</th>
                            <th class="text-end fs-6" x-text="fmt(yearTotal)"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3 mb-5">
            <a href="{{ route('liquidaciones.gastos.index') }}" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar año</button>
        </div>
    </form>
</div>

<style>
    .gastos-grid td, .gastos-grid th { padding: .25rem .4rem; vertical-align: middle; }
    .gastos-grid .form-control-sm { padding: .15rem .35rem; font-size: .85rem; min-height: 28px; }
</style>
@endsection
