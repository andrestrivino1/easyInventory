@extends('layouts.app')

@section('content')
@php
    $money = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
    $meses = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
    $anioActual = (int) date('Y');
    $resultado = $resumen['resultado'];
    $utilColor = $resultado === 'ganancia' ? 'success' : ($resultado === 'perdida' ? 'danger' : 'secondary');
    $sinDatos = ($resumen['count'] ?? 0) === 0;
@endphp

<div class="container-fluid py-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h4 class="mb-0"><i class="bi bi-bar-chart-line"></i> Informes de Liquidaciones</h4>
        <span class="text-muted">{{ $filtros['label'] }}@if($driverActual) · {{ $driverActual->name }} ({{ $driverActual->vehicle_plate }})@endif</span>
    </div>

    {{-- Filtros de periodo --}}
    <form method="GET" action="{{ route('liquidaciones.reportes.index') }}" id="filtrosForm" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small">Periodo</label>
                <select name="tipo" id="tipoSelect" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="mes" {{ $filtros['tipo']==='mes'?'selected':'' }}>Mensual</option>
                    <option value="semestre" {{ $filtros['tipo']==='semestre'?'selected':'' }}>Semestral</option>
                    <option value="anio" {{ $filtros['tipo']==='anio'?'selected':'' }}>Anual</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small">Año</label>
                <select name="anio" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = $anioActual; $y >= $anioActual - 6; $y--)
                        <option value="{{ $y }}" {{ (int)$filtros['anio']===$y?'selected':'' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-6 col-md-2 campo-mes" @if($filtros['tipo']!=='mes') style="display:none" @endif>
                <label class="form-label mb-1 small">Mes</label>
                <select name="mes" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($meses as $n => $nombre)
                        <option value="{{ $n }}" {{ (int)$filtros['mes']===$n?'selected':'' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 campo-semestre" @if($filtros['tipo']!=='semestre') style="display:none" @endif>
                <label class="form-label mb-1 small">Semestre</label>
                <select name="semestre" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="1" {{ (int)$filtros['semestre']===1?'selected':'' }}>S1 (Ene–Jun)</option>
                    <option value="2" {{ (int)$filtros['semestre']===2?'selected':'' }}>S2 (Jul–Dic)</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label mb-1 small">Conductor / placa</label>
                <select name="driver_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos (consolidado)</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ (int)$filtros['driver_id']===$d->id?'selected':'' }}>{{ $d->name }} — {{ $d->vehicle_plate }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-1 d-grid">
                <button type="button" id="btnPdf" class="btn btn-sm btn-danger" title="Descargar PDF"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </div>
        </div>
    </form>

    {{-- Formulario oculto para el POST del PDF (lleva las gráficas como imagen) --}}
    <form method="POST" action="{{ route('liquidaciones.reportes.pdf') }}" id="pdfForm" class="d-none">
        @csrf
        <input type="hidden" name="tipo" value="{{ $filtros['tipo'] }}">
        <input type="hidden" name="anio" value="{{ $filtros['anio'] }}">
        <input type="hidden" name="mes" value="{{ $filtros['mes'] }}">
        <input type="hidden" name="semestre" value="{{ $filtros['semestre'] }}">
        <input type="hidden" name="driver_id" value="{{ $filtros['driver_id'] }}">
        <input type="hidden" name="charts[evolucion]" id="chartEvolucionInput">
        <input type="hidden" name="charts[categorias]" id="chartCategoriasInput">
    </form>

    @if($sinDatos)
        <div class="alert alert-info"><i class="bi bi-info-circle"></i> No hay liquidaciones activas en este periodo. La utilidad neta es {{ $money($resumen['utilidad_neta']) }}.</div>
    @endif

    {{-- Tarjetas de resumen --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card card-body h-100"><div class="text-muted small">Ingresos por fletes</div><div class="fs-5 fw-bold text-primary">{{ $money($resumen['sum_flete']) }}</div><div class="small text-muted">{{ $resumen['count'] }} viaje(s)</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-body h-100"><div class="text-muted small">Gastos operativos</div><div class="fs-5 fw-bold">{{ $money($resumen['sum_gastos_operativos']) }}</div><div class="small text-muted">Peajes: {{ $money($resumen['sum_peajes']) }}</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-body h-100"><div class="text-muted small">Gastos fijos mensuales</div><div class="fs-5 fw-bold">{{ $money($resumen['sum_gastos_mensuales']) }}</div><div class="small text-muted">Sueldos, seg. social, etc.</div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-body h-100 border-{{ $utilColor }}"><div class="text-muted small">Utilidad neta</div><div class="fs-5 fw-bold text-{{ $utilColor }}">{{ $money($resumen['utilidad_neta']) }}</div><div class="small text-{{ $utilColor }} text-uppercase fw-semibold">{{ $resultado }}</div></div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Evolución mensual --}}
        <div class="col-12 col-lg-7">
            <div class="card card-body h-100">
                <h6 class="mb-2">Evolución mensual</h6>
                @if($evolucion->isEmpty())
                    <p class="text-muted small mb-0">Sin datos para graficar.</p>
                @else
                    <canvas id="chartEvolucion" height="120"></canvas>
                    <div class="small text-muted mt-2">
                        @if($mejorMes)<span class="text-success">▲ Mejor mes: {{ $mejorMes['periodo'] }} ({{ $money($mejorMes['utilidad_neta']) }})</span>@endif
                        @if($peorMes) · <span class="text-danger">▼ Peor mes: {{ $peorMes['periodo'] }} ({{ $money($peorMes['utilidad_neta']) }})</span>@endif
                    </div>
                @endif
            </div>
        </div>
        {{-- Desglose por categoría --}}
        <div class="col-12 col-lg-5">
            <div class="card card-body h-100">
                <h6 class="mb-2">Gastos por categoría</h6>
                @if($categorias->isEmpty())
                    <p class="text-muted small mb-0">Sin gastos operativos en el periodo.</p>
                @else
                    <canvas id="chartCategorias" height="160"></canvas>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        {{-- Detalle categorías --}}
        <div class="col-12 col-md-6">
            <div class="card card-body h-100">
                <h6 class="mb-2">Detalle de gastos operativos</h6>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($categorias as $c)
                            <tr><td>{{ $c['name'] }}</td><td class="text-end">{{ $money($c['total']) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="fw-bold border-top"><td>Total operativos</td><td class="text-end">{{ $money($resumen['sum_gastos_operativos']) }}</td></tr>
                    <tr><td>Peajes</td><td class="text-end">{{ $money($resumen['sum_peajes']) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
        {{-- Gastos fijos --}}
        <div class="col-12 col-md-6">
            <div class="card card-body h-100">
                <h6 class="mb-2">Gastos fijos mensuales</h6>
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach($conceptosFijos as $key => $etiqueta)
                            <tr><td>{{ $etiqueta }}</td><td class="text-end">{{ $money($gastosFijos[$key] ?? 0) }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="fw-bold border-top"><td>Total fijos</td><td class="text-end">{{ $money($gastosFijos['total'] ?? 0) }}</td></tr></tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Desglose por conductor (solo consolidado) --}}
    @if($porConductor->isNotEmpty())
        <div class="card card-body mt-3">
            <h6 class="mb-2">Desglose por conductor</h6>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Conductor</th><th>Placa</th><th class="text-end">Viajes</th><th class="text-end">Fletes</th><th class="text-end">Gastos viaje</th><th class="text-end">Gastos fijos</th><th class="text-end">Utilidad neta</th></tr></thead>
                    <tbody>
                        @foreach($porConductor as $row)
                            <tr>
                                <td><a href="{{ route('liquidaciones.reportes.index', array_merge($filtros, ['driver_id'=>$row['driver_id']])) }}">{{ $row['name'] }}</a></td>
                                <td>{{ $row['vehicle_plate'] }}</td>
                                <td class="text-end">{{ $row['count'] }}</td>
                                <td class="text-end">{{ $money($row['sum_flete']) }}</td>
                                <td class="text-end">{{ $money($row['sum_gastos_totales']) }}</td>
                                <td class="text-end">{{ $money($row['sum_gastos_mensuales']) }}</td>
                                <td class="text-end fw-semibold {{ $row['utilidad_neta'] >= 0 ? 'text-success' : 'text-danger' }}">{{ $money($row['utilidad_neta']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
(function () {
    // Mostrar/ocultar campos según el tipo de periodo
    const tipo = document.getElementById('tipoSelect');
    function toggleCampos() {
        document.querySelectorAll('.campo-mes').forEach(e => e.style.display = tipo.value === 'mes' ? '' : 'none');
        document.querySelectorAll('.campo-semestre').forEach(e => e.style.display = tipo.value === 'semestre' ? '' : 'none');
    }
    if (tipo) tipo.addEventListener('change', toggleCampos);

    const fmt = v => new Intl.NumberFormat('es-CO').format(v);
    let chartEvolucion = null, chartCategorias = null;

    @if(!$evolucion->isEmpty())
    const evo = @json($evolucion->values());
    chartEvolucion = new Chart(document.getElementById('chartEvolucion'), {
        type: 'bar',
        data: {
            labels: evo.map(m => m.periodo),
            datasets: [
                { label: 'Ingresos (fletes)', data: evo.map(m => m.sum_flete), backgroundColor: '#0d6efd' },
                { label: 'Gastos viaje', data: evo.map(m => m.sum_gastos_totales), backgroundColor: '#dc3545' },
                { type: 'line', label: 'Utilidad neta', data: evo.map(m => m.utilidad_neta), borderColor: '#198754', backgroundColor: '#198754', tension: 0.2 },
            ]
        },
        options: { responsive: true, plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': $' + fmt(c.parsed.y) } } }, scales: { y: { ticks: { callback: v => '$' + fmt(v) } } } }
    });
    @endif

    @if(!$categorias->isEmpty())
    const cats = @json($categorias->values());
    chartCategorias = new Chart(document.getElementById('chartCategorias'), {
        type: 'doughnut',
        data: { labels: cats.map(c => c.name), datasets: [{ data: cats.map(c => c.total) }] },
        options: { responsive: true, plugins: { legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }, tooltip: { callbacks: { label: c => c.label + ': $' + fmt(c.parsed) } } } }
    });
    @endif

    // Descargar PDF: captura las gráficas a PNG y envía el formulario
    const btnPdf = document.getElementById('btnPdf');
    if (btnPdf) btnPdf.addEventListener('click', function () {
        if (chartEvolucion) document.getElementById('chartEvolucionInput').value = chartEvolucion.toBase64Image('image/png', 1);
        if (chartCategorias) document.getElementById('chartCategoriasInput').value = chartCategorias.toBase64Image('image/png', 1);
        document.getElementById('pdfForm').submit();
    });
})();
</script>
@endsection
