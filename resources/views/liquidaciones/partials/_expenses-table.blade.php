<div class="card mb-3">
    <div class="card-header bg-light"><strong>Gastos del viaje</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered m-0">
            <thead class="table-light">
                <tr>
                    <th>DESCRIPCIÓN</th>
                    <th class="text-end" style="width:35%">VALOR</th>
                    <th class="text-end" style="width:20%">GALONES</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(exp, idx) in expenses" :key="exp.expense_category_id">
                    <tr>
                        <td>
                            <span x-text="exp.category_name"></span>
                            <input type="hidden" :name="'expenses[' + idx + '][expense_category_id]'" :value="exp.expense_category_id">
                        </td>
                        <td>
                            <input type="number" min="0" class="form-control form-control-sm text-end"
                                   :name="'expenses[' + idx + '][valor]'"
                                   x-model.number="exp.valor"
                                   x-on:input="recalc()">
                        </td>
                        <td>
                            <template x-if="exp.has_galones">
                                <input type="number" step="0.01" min="0"
                                       class="form-control form-control-sm text-end"
                                       :name="'expenses[' + idx + '][galones]'"
                                       x-model.number="exp.galones">
                            </template>
                            <template x-if="!exp.has_galones">
                                <span class="text-muted">—</span>
                            </template>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
