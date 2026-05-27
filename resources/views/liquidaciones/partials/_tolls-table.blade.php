<div class="card mb-3">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <strong>Peajes del viaje</strong>
        <button type="button" class="btn btn-sm btn-outline-primary" x-on:click="addAdhocToll()">
            <i class="bi bi-plus-circle"></i> Agregar peaje ad-hoc
        </button>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered m-0">
            <thead class="table-light">
                <tr>
                    <th style="width:5%">#</th>
                    <th>PEAJE</th>
                    <th class="text-end" style="width:22%">VALOR</th>
                    <th class="text-center" style="width:18%">PAGA</th>
                    <th class="text-center" style="width:9%">Usado</th>
                    <th class="text-center" style="width:8%"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(toll, idx) in tolls" :key="idx">
                    <tr :class="toll.is_adhoc ? 'table-warning' : ''">
                        <td x-text="toll.sort_order"></td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   :name="'tolls[' + idx + '][name]'"
                                   x-model="toll.name"
                                   :readonly="!toll.is_adhoc">
                            <input type="hidden" :name="'tolls[' + idx + '][sort_order]'" :value="toll.sort_order">
                            <input type="hidden" :name="'tolls[' + idx + '][direction]'" :value="toll.direction">
                            <input type="hidden" :name="'tolls[' + idx + '][route_toll_id]'" :value="toll.route_toll_id || ''">
                            <input type="hidden" :name="'tolls[' + idx + '][is_adhoc]'" :value="toll.is_adhoc ? 1 : 0">
                            <small class="text-muted" x-text="toll.direction === 'ida' ? 'IDA' : 'REGRESO'"></small>
                        </td>
                        <td>
                            <input type="number" min="0" class="form-control form-control-sm text-end"
                                   :name="'tolls[' + idx + '][valor]'"
                                   x-model.number="toll.valor"
                                   x-on:input="recalc()">
                        </td>
                        <td>
                            <select class="form-select form-select-sm"
                                    :name="'tolls[' + idx + '][paid_by]'"
                                    x-model="toll.paid_by"
                                    x-on:change="recalc()"
                                    title="Empresa = GoPass (no afecta el saldo del conductor). Conductor = se descuenta de su saldo.">
                                <option value="empresa">Empresa (GoPass)</option>
                                <option value="conductor">Conductor</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input"
                                   :name="'tolls[' + idx + '][is_used]'"
                                   x-model="toll.is_used"
                                   x-on:change="recalc()"
                                   value="1">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    x-on:click="removeToll(idx)" title="Eliminar peaje del viaje">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
                <template x-if="tolls.length === 0">
                    <tr><td colspan="6" class="text-center text-muted py-3">
                        Selecciona una ruta para autocargar los peajes, o agrega peajes ad-hoc.
                    </td></tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
