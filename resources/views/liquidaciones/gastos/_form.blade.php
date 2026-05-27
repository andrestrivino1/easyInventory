{{--
    Form reusable create + edit de gasto mensual. Espera:
    - $gasto (MonthlyExpense|null)
    - $drivers (colección con id, name, vehicle_plate)
    El <form> lo provee create.blade.php / edit.blade.php.
--}}
@php
    $gasto = $gasto ?? null;
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $platesMap = $drivers->mapWithKeys(fn($d) => [$d->id => $d->vehicle_plate])->all();
@endphp

@csrf
@if ($gasto) @method('PUT') @endif

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div x-data="{ plates: {{ \Illuminate\Support\Js::from($platesMap) }}, plate: '{{ old('vehicle_plate', $gasto->vehicle_plate ?? '') }}' }">
    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Conductor y período</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Conductor</label>
                    <select name="driver_id" class="form-select" required
                            x-on:change="plate = plates[$event.target.value] || ''">
                        <option value="">— Seleccionar —</option>
                        @foreach ($drivers as $d)
                            <option value="{{ $d->id }}" {{ (string)old('driver_id', $gasto->driver_id ?? '') === (string)$d->id ? 'selected' : '' }}>
                                {{ $d->name }} ({{ $d->vehicle_plate }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Placa (vehículo asociado)</label>
                    <input type="text" class="form-control text-uppercase" x-model="plate" readonly placeholder="—">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Año</label>
                    <input type="number" name="anio" class="form-control" min="2020" max="2100" required
                           value="{{ old('anio', $gasto->anio ?? now()->year) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Mes</label>
                    <select name="mes" class="form-select" required>
                        @foreach ($meses as $num => $nombre)
                            <option value="{{ $num }}" {{ (string)old('mes', $gasto->mes ?? now()->month) === (string)$num ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-light"><strong>Valores mensuales</strong></div>
        <div class="card-body">
            <div class="row g-3">
                @php
                    $campos = [
                        'sueldo_conductor' => 'Sueldo conductor',
                        'seguridad_social' => 'Seguridad social conductor',
                        'cuota_banco' => 'Cuota banco',
                        'cuota_tercero' => 'Cuota tercero',
                        'satelital' => 'Satelital',
                        'seguro_vehiculo' => 'Seguro vehículo',
                    ];
                @endphp
                @foreach ($campos as $name => $label)
                    <div class="col-md-4">
                        <label class="form-label">{{ $label }}</label>
                        <input type="number" name="{{ $name }}" class="form-control text-end" min="0"
                               value="{{ old($name, $gasto->$name ?? 0) }}">
                    </div>
                @endforeach
                <div class="col-md-4">
                    <label class="form-label">Otro (valor)</label>
                    <input type="number" name="otro_valor" class="form-control text-end" min="0"
                           value="{{ old('otro_valor', $gasto->otro_valor ?? 0) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Otro (descripción)</label>
                    <input type="text" name="otro_descripcion" class="form-control" maxlength="150"
                           value="{{ old('otro_descripcion', $gasto->otro_descripcion ?? '') }}"
                           placeholder="¿A qué corresponde el valor 'Otro'?">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('liquidaciones.gastos.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
    </div>
</div>
