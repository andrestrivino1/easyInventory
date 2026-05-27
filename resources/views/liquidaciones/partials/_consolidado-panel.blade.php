@php
    $title = $title ?? 'Consolidado del periodo';
    $c = $c ?? $consolidado;
@endphp
<div class="card mb-3 border-info">
    <div class="card-header bg-info text-white d-flex justify-content-between">
        <strong>{{ $title }}</strong>
        <span>{{ $c['count'] }} viaje{{ $c['count'] === 1 ? '' : 's' }}</span>
    </div>
    <div class="card-body py-2">
        <div class="row text-end small">
            <div class="col-md-3"><span class="text-muted">Gastos operativos:</span><br><strong>{{ number_format($c['sum_gastos_operativos'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3"><span class="text-muted">Peajes:</span><br><strong>{{ number_format($c['sum_peajes'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3"><span class="text-muted">Gastos totales:</span><br><strong>{{ number_format($c['sum_gastos_totales'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3"><span class="text-muted">Anticipos:</span><br><strong>{{ number_format($c['sum_anticipos'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3 mt-2"><span class="text-muted">Descuentos (empresa):</span><br><strong class="{{ ($c['sum_descuentos'] ?? 0) > 0 ? 'text-danger' : '' }}">{{ number_format($c['sum_descuentos'] ?? 0, 0, ',', '.') }}</strong></div>
            <div class="col-md-3 mt-2"><span class="text-muted">Flete:</span><br><strong>{{ number_format($c['sum_flete'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3 mt-2"><span class="text-muted">Saldo:</span><br><strong class="{{ $c['sum_saldo'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($c['sum_saldo'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3 mt-2"><span class="text-muted">Ganancia:</span><br><strong class="{{ $c['sum_ganancia'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($c['sum_ganancia'], 0, ',', '.') }}</strong></div>
            <div class="col-md-3 mt-2"><span class="text-muted">Promedio ganancia/viaje:</span><br><strong>{{ number_format($c['avg_ganancia'], 0, ',', '.') }}</strong></div>
            <div class="col-md-12 mt-2"><span class="text-muted">Margen del periodo:</span> <strong>{{ $c['margen_pct'] }} %</strong></div>
        </div>
    </div>
</div>
