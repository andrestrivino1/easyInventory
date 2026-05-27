@php
    $route = $route ?? null;
    $existingTolls = $route ? $route->tolls->map(fn($t) => [
        'name' => $t->name,
        'suggested_value' => (int) $t->suggested_value,
        'sort_order' => (int) $t->sort_order,
        'direction' => $t->direction,
    ])->all() : [];
@endphp

<div x-data='routeForm({!! json_encode(["existingTolls" => $existingTolls]) !!})'>
    @csrf
    @if ($route)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Ciudad Origen</label>
                    <input type="text" name="origen" class="form-control text-uppercase" required maxlength="100"
                           value="{{ old('origen', $route->origen ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ciudad Destino</label>
                    <input type="text" name="destino" class="form-control text-uppercase" required maxlength="100"
                           value="{{ old('destino', $route->destino ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de vehículo</label>
                    <select name="vehicle_type" class="form-select" required>
                        <option value="">— Seleccionar —</option>
                        @foreach (\App\Models\LiquidacionRoute::VEHICLE_LABELS as $value => $label)
                            <option value="{{ $value }}"
                                {{ old('vehicle_type', $route->vehicle_type ?? '') === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">Estado</label>
                    <div class="form-check form-switch mt-2">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" name="active" value="1" class="form-check-input"
                               {{ old('active', $route->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Activa</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción (opcional)</label>
                    <textarea name="descripcion" class="form-control" rows="2">{{ old('descripcion', $route->descripcion ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>Peajes de la ruta</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="addToll()">
                <i class="bi bi-plus-circle"></i> Agregar peaje
            </button>
        </div>
        <table class="table table-sm m-0">
            <thead class="table-light">
                <tr>
                    <th style="width:5%">Orden</th>
                    <th>Nombre del peaje</th>
                    <th class="text-end" style="width:20%">Valor sugerido</th>
                    <th style="width:15%">Sentido</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(t, idx) in tolls" :key="idx">
                    <tr>
                        <td>
                            <input type="number" min="1" class="form-control form-control-sm"
                                   :name="'tolls[' + idx + '][sort_order]'" x-model.number="t.sort_order" required>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   :name="'tolls[' + idx + '][name]'" x-model="t.name" required maxlength="100">
                        </td>
                        <td>
                            <input type="number" min="0" class="form-control form-control-sm text-end"
                                   :name="'tolls[' + idx + '][suggested_value]'" x-model.number="t.suggested_value">
                        </td>
                        <td>
                            <select class="form-select form-select-sm" :name="'tolls[' + idx + '][direction]'" x-model="t.direction">
                                <option value="ida">Ida</option>
                                <option value="regreso">Regreso</option>
                            </select>
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger" x-on:click="removeToll(idx)"><i class="bi bi-trash"></i></button></td>
                    </tr>
                </template>
                <template x-if="tolls.length === 0">
                    <tr><td colspan="5" class="text-center text-muted py-3">No hay peajes. Agrega al menos uno con el botón superior.</td></tr>
                </template>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between">
        <a href="{{ route('liquidaciones.routes.index') }}" class="btn btn-secondary">Cancelar</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar ruta</button>
    </div>
</div>

<script>
window.routeForm = function (config) {
    return {
        tolls: (config.existingTolls || []).map(t => ({
            name: t.name,
            suggested_value: parseInt(t.suggested_value, 10) || 0,
            sort_order: parseInt(t.sort_order, 10) || 1,
            direction: t.direction || 'ida',
        })),
        addToll() {
            const nextOrder = this.tolls.length > 0 ? Math.max(...this.tolls.map(t => t.sort_order)) + 1 : 1;
            this.tolls.push({ name: '', suggested_value: 0, sort_order: nextOrder, direction: 'ida' });
        },
        removeToll(idx) { this.tolls.splice(idx, 1); },
    };
};
</script>
