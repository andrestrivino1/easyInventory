<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Liquidación de Viaje {{ $liq->id }}</title>
<style>
    @page { margin: 12mm; size: letter portrait; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #000; }
    h1 { font-size: 14pt; text-align: center; margin: 0; padding: 4px 0; border: 2px solid #000; }
    .header-table, .data-table { width: 100%; border-collapse: collapse; margin: 4px 0; }
    .header-table td, .data-table td, .data-table th {
        border: 1px solid #000; padding: 3px 5px; vertical-align: middle;
    }
    .data-table th { background: #f0f0f0; font-weight: bold; text-align: left; }
    .right { text-align: right; }
    .center { text-align: center; }
    .label-cell { background: #f0f0f0; font-weight: bold; }
    .totals { width: 50%; float: left; }
    .totals-r { width: 50%; float: left; }
    .clearfix::after { content: ""; clear: both; display: table; }
    .firma-box {
        border: 1px solid #000; height: 80px; width: 60%; margin: 20px auto;
        text-align: center; padding-top: 6px;
    }
    .anulada-watermark {
        position: fixed; top: 35%; left: 10%; right: 10%; text-align: center;
        font-size: 70pt; color: rgba(220, 0, 0, 0.25);
        transform: rotate(-30deg); z-index: 999; pointer-events: none;
        font-weight: bold; letter-spacing: 10px;
    }
    .bold { font-weight: bold; }
</style>
</head>
<body>

@if ($liq->isAnulada())
    <div class="anulada-watermark">ANULADA</div>
@endif

<h1>LIQUIDACIÓN DE VIAJE — VIDRIOS J&amp;P S.A.S.</h1>

<table class="header-table">
    <tr>
        <td class="label-cell" style="width:15%">PLACA</td>
        <td style="width:20%">{{ $liq->vehicle_plate }}</td>
        <td class="label-cell" style="width:15%">RUTA</td>
        <td>{{ $liq->route->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label-cell">TRANSPORTE</td>
        <td>{{ $liq->transportadora }}</td>
        <td class="label-cell">CONDUCTOR</td>
        <td>{{ $liq->driver->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label-cell">ANTICIPO</td>
        <td class="right">{{ number_format($liq->anticipo, 0, ',', '.') }}</td>
        <td class="label-cell">FECHA INICIO</td>
        <td>{{ $liq->fecha_inicio?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label-cell">SOBREANTICIPO</td>
        <td class="right">{{ number_format($liq->sobreanticipo, 0, ',', '.') }}</td>
        <td class="label-cell">FECHA FIN</td>
        <td>{{ $liq->fecha_fin?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label-cell">N° MFTO</td>
        <td>{{ $liq->numero_mfto ?? '—' }}</td>
        <td class="label-cell">TELÉFONO EMPRESA</td>
        <td>{{ $liq->telefono_empresa ?? '—' }}</td>
    </tr>
</table>

<div class="clearfix">
    <div style="float:left; width:48%; margin-right:2%;">
        <table class="data-table">
            <thead><tr><th>DESCRIPCIÓN</th><th class="right">VALOR</th><th class="right">GALONES</th></tr></thead>
            <tbody>
                @php $expensesByCat = $liq->expenses->keyBy('expense_category_id'); @endphp
                @foreach ($categories ?? \App\Models\ExpenseCategory::ordered()->get() as $cat)
                    @php $exp = $expensesByCat->get($cat->id); @endphp
                    <tr>
                        <td>{{ $cat->name }}</td>
                        <td class="right">{{ $exp ? number_format($exp->valor, 0, ',', '.') : '' }}</td>
                        <td class="right">{{ $exp && $exp->galones !== null ? number_format($exp->galones, 2) : '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="float:left; width:50%;">
        <table class="data-table">
            <thead><tr><th>PEAJE</th><th class="right">VALOR</th></tr></thead>
            <tbody>
                @foreach ($liq->tolls as $t)
                    <tr style="{{ $t->is_used ? '' : 'text-decoration:line-through;color:#999;' }}">
                        <td>PEAJE {{ strtoupper($t->name) }} <small>({{ strtoupper($t->direction) }})</small></td>
                        <td class="right">{{ number_format($t->valor, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<table class="data-table" style="margin-top:8px;">
    <tr>
        <td class="label-cell" style="width:30%">SUMATORIA DE GASTOS</td>
        <td class="right" style="width:20%">{{ number_format($liq->sumatoria_gastos_operativos, 0, ',', '.') }}</td>
        <td class="label-cell" style="width:30%">TOTAL PEAJES</td>
        <td class="right" style="width:20%">{{ number_format($liq->sumatoria_peajes, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="label-cell">SUMATORIA DE PEAJES</td>
        <td class="right">{{ number_format($liq->sumatoria_peajes, 0, ',', '.') }}</td>
        <td class="label-cell">A FAVOR DE</td>
        <td class="right bold" style="background:#fff3cd;">
            {{ $liq->a_favor_de === 'empresa' ? 'VIDRIOS J&P' : ($liq->a_favor_de === 'conductor' ? 'CONDUCTOR' : '—') }}
        </td>
    </tr>
    <tr>
        <td class="label-cell">SUMATORIA DE GASTOS (TOTAL)</td>
        <td class="right">{{ number_format($liq->sumatoria_gastos_totales, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
    <tr>
        <td class="label-cell">TOTAL ANTICIPOS</td>
        <td class="right">{{ number_format($liq->total_anticipos, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
    <tr>
        <td class="label-cell">SALDO VIAJE</td>
        <td class="right bold" style="background:#fff3cd;">{{ number_format($liq->saldo_viaje, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
    <tr>
        <td class="label-cell">VALOR FLETE</td>
        <td class="right">{{ number_format($liq->valor_flete, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
    <tr>
        <td class="label-cell">GANANCIA VIAJE</td>
        <td class="right bold">{{ number_format($liq->ganancia_viaje, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
</table>

<div class="firma-box">
    FIRMA CONDUCTOR
</div>

</body>
</html>
