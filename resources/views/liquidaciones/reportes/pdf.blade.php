<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    @php
        $money = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');
        $resultado = $resumen['resultado'];
        $utilColor = $resultado === 'ganancia' ? '#198754' : ($resultado === 'perdida' ? '#dc3545' : '#6c757d');
        $charts = $charts ?? [];
    @endphp
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #222; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 14px 0 4px; border-bottom: 1px solid #ccc; padding-bottom: 2px; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 3px 5px; }
        .summary td { border: 1px solid #ddd; }
        .detail th { background: #f1f3f5; text-align: left; border: 1px solid #ddd; }
        .detail td { border: 1px solid #ddd; }
        .right { text-align: right; }
        .tot { font-weight: bold; background: #f8f9fa; }
        .util { font-size: 14px; font-weight: bold; color: {{ $utilColor }}; }
        .chart img { max-width: 100%; height: auto; }
        .half { width: 49%; vertical-align: top; display: inline-block; }
    </style>
</head>
<body>
    <h1>Informe de Liquidaciones de Viajes</h1>
    <div class="muted">
        Periodo: {{ $filtros['label'] }}
        @if($driverActual) &nbsp;·&nbsp; Conductor: {{ $driverActual->name }} ({{ $driverActual->vehicle_plate }}) @else &nbsp;·&nbsp; Consolidado de la empresa @endif
    </div>

    <h2>Resumen</h2>
    <table class="summary">
        <tr>
            <td>Ingresos por fletes</td><td class="right">{{ $money($resumen['sum_flete']) }}</td>
            <td>Viajes</td><td class="right">{{ $resumen['count'] }}</td>
        </tr>
        <tr>
            <td>Gastos operativos</td><td class="right">{{ $money($resumen['sum_gastos_operativos']) }}</td>
            <td>Peajes</td><td class="right">{{ $money($resumen['sum_peajes']) }}</td>
        </tr>
        <tr>
            <td>Gastos fijos mensuales</td><td class="right">{{ $money($resumen['sum_gastos_mensuales']) }}</td>
            <td>Gastos totales de viaje</td><td class="right">{{ $money($resumen['sum_gastos_totales']) }}</td>
        </tr>
        <tr class="tot">
            <td>Utilidad neta</td><td class="right util">{{ $money($resumen['utilidad_neta']) }}</td>
            <td>Resultado</td><td class="right" style="text-transform:uppercase;color:{{ $utilColor }}">{{ $resultado }}</td>
        </tr>
    </table>

    @if(!empty($charts['evolucion']) || !empty($charts['categorias']))
        <h2>Gráficas</h2>
        <div class="chart">
            @if(!empty($charts['evolucion']))<div class="half"><img src="{{ $charts['evolucion'] }}" alt="Evolución mensual"></div>@endif
            @if(!empty($charts['categorias']))<div class="half"><img src="{{ $charts['categorias'] }}" alt="Gastos por categoría"></div>@endif
        </div>
    @endif

    <h2>Detalle de gastos operativos</h2>
    <table class="detail">
        <thead><tr><th>Categoría</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @forelse($categorias as $c)
                <tr><td>{{ $c['name'] }}</td><td class="right">{{ $money($c['total']) }}</td></tr>
            @empty
                <tr><td colspan="2" class="muted">Sin gastos operativos</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="tot"><td>Total operativos</td><td class="right">{{ $money($resumen['sum_gastos_operativos']) }}</td></tr>
            <tr><td>Peajes</td><td class="right">{{ $money($resumen['sum_peajes']) }}</td></tr>
        </tfoot>
    </table>

    <h2>Gastos fijos mensuales</h2>
    <table class="detail">
        <thead><tr><th>Concepto</th><th class="right">Valor</th></tr></thead>
        <tbody>
            @foreach($conceptosFijos as $key => $etiqueta)
                <tr><td>{{ $etiqueta }}</td><td class="right">{{ $money($gastosFijos[$key] ?? 0) }}</td></tr>
            @endforeach
        </tbody>
        <tfoot><tr class="tot"><td>Total fijos</td><td class="right">{{ $money($gastosFijos['total'] ?? 0) }}</td></tr></tfoot>
    </table>

    @if($porConductor->isNotEmpty())
        <h2>Desglose por conductor</h2>
        <table class="detail">
            <thead><tr><th>Conductor</th><th>Placa</th><th class="right">Viajes</th><th class="right">Fletes</th><th class="right">Gastos fijos</th><th class="right">Utilidad neta</th></tr></thead>
            <tbody>
                @foreach($porConductor as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['vehicle_plate'] }}</td>
                        <td class="right">{{ $row['count'] }}</td>
                        <td class="right">{{ $money($row['sum_flete']) }}</td>
                        <td class="right">{{ $money($row['sum_gastos_mensuales']) }}</td>
                        <td class="right">{{ $money($row['utilidad_neta']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
