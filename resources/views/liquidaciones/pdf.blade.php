<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Liquidación de Viaje {{ $liq->id }}</title>
<style>
    @page { margin: 12mm; size: letter portrait; }
    body { font-family: 'Helvetica', sans-serif; font-size: 9pt; color: #000; }
    .doc-header { width: 100%; border-collapse: collapse; margin-bottom: 6px; border: 2px solid #000; }
    .doc-header td { vertical-align: middle; padding: 6px; }
    .doc-header .col-logo { width: 18%; text-align: center; border-right: 1px solid #000; }
    .doc-header .col-logo img { max-width: 70px; max-height: 70px; display: block; margin: 0 auto; }
    .doc-header .col-title { text-align: center; }
    .doc-header .col-title .company { font-size: 11pt; font-weight: bold; }
    .doc-header .col-title .company-sub { font-size: 8pt; color: #333; margin-top: 1px; }
    .doc-header .col-title .doc-title {
        font-size: 13pt; font-weight: bold; margin-top: 4px;
        border-top: 1px solid #000; padding-top: 3px;
    }
    .doc-header .col-photos { width: 28%; border-left: 1px solid #000; text-align: center; }
    .doc-header .col-photos table { width: 100%; border-collapse: collapse; }
    .doc-header .col-photos td { padding: 0 2px; vertical-align: top; text-align: center; }
    .doc-header .photo-item img {
        width: 60px; height: 60px; object-fit: cover;
        border: 1px solid #999; display: block; margin: 0 auto;
    }
    .doc-header .photo-placeholder {
        width: 60px; height: 60px; border: 1px dashed #999;
        display: block; margin: 0 auto; text-align: center;
        font-size: 7pt; color: #999; padding-top: 24px; box-sizing: border-box;
    }
    .doc-header .photo-label { font-size: 7pt; font-weight: bold; margin-top: 2px; text-transform: uppercase; }
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

@php
    $logoBase64 = '';
    $logoPath = public_path('logo.png');
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $driverPhotoBase64 = '';
    if ($liq->driver && $liq->driver->photo_path) {
        $p = storage_path('app/public/' . $liq->driver->photo_path);
        if (file_exists($p)) {
            $info = @getimagesize($p);
            $mime = $info ? $info['mime'] : 'image/jpeg';
            $driverPhotoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }
    }

    $vehiclePhotoBase64 = '';
    if ($liq->driver && $liq->driver->vehicle_photo_path) {
        $p = storage_path('app/public/' . $liq->driver->vehicle_photo_path);
        if (file_exists($p)) {
            $info = @getimagesize($p);
            $mime = $info ? $info['mime'] : 'image/jpeg';
            $vehiclePhotoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($p));
        }
    }

@endphp

<table class="doc-header">
    <tr>
        <td class="col-logo">
            @if ($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </td>
        <td class="col-title">
            <div class="company">VIDRIOS J&amp;P S.A.S.</div>
            <div class="company-sub">NIT: 901.701.161-4</div>
            <div class="doc-title">LIQUIDACIÓN DE VIAJE</div>
        </td>
        <td class="col-photos">
            <table>
                <tr>
                    <td>
                        <div class="photo-item">
                            @if ($driverPhotoBase64)
                                <img src="{{ $driverPhotoBase64 }}" alt="Conductor">
                            @else
                                <div class="photo-placeholder">Sin foto</div>
                            @endif
                            <div class="photo-label">Conductor</div>
                        </div>
                    </td>
                    <td>
                        <div class="photo-item">
                            @if ($vehiclePhotoBase64)
                                <img src="{{ $vehiclePhotoBase64 }}" alt="Vehículo">
                            @else
                                <div class="photo-placeholder">Sin foto</div>
                            @endif
                            <div class="photo-label">Vehículo</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

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
        <td class="label-cell">ANTICIPO CONDUCTOR</td>
        <td class="right">{{ number_format($liq->anticipo_conductor, 0, ',', '.') }}</td>
        <td class="label-cell">FECHA INICIO</td>
        <td>{{ $liq->fecha_inicio?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td class="label-cell">SOBRE ANTICIPO</td>
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
            <thead><tr><th>PEAJE</th><th class="center">PAGA</th><th class="right">VALOR</th></tr></thead>
            <tbody>
                @foreach ($liq->tolls as $t)
                    <tr style="{{ $t->is_used ? '' : 'text-decoration:line-through;color:#999;' }}">
                        <td>PEAJE {{ strtoupper($t->name) }} <small>({{ strtoupper($t->direction) }})</small></td>
                        <td class="center">{{ $t->paid_by === 'conductor' ? 'CONDUCTOR' : 'EMPRESA' }}</td>
                        <td class="right">{{ number_format($t->valor, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@php
    // El peaje que paga el conductor cuenta como gasto suyo (no se resta de la sumatoria de peajes).
    $sumatoriaGastos = $liq->sumatoria_gastos_operativos + $liq->descuentos + $liq->sumatoria_peajes_conductor;
    $anticiposConductor = $liq->anticipo_conductor + $liq->sobreanticipo;
    $aFavorLabel = $liq->a_favor_de === 'empresa' ? 'VIDRIOS J&P' : ($liq->a_favor_de === 'conductor' ? 'CONDUCTOR' : '—');
@endphp
<table class="data-table" style="margin-top:8px;">
    <tr>
        <td class="label-cell" style="width:30%">SUMATORIA DE GASTOS</td>
        <td class="right" style="width:20%">{{ number_format($sumatoriaGastos, 0, ',', '.') }}</td>
        <td class="label-cell" style="width:30%">ANTICIPOS CONDUCTOR</td>
        <td class="right" style="width:20%">{{ number_format($anticiposConductor, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="label-cell">SUMATORIA DE PEAJES</td>
        <td class="right">{{ number_format($liq->sumatoria_peajes, 0, ',', '.') }}</td>
        <td class="label-cell">ANT - GASTOS</td>
        <td class="right bold" style="background:#fff3cd;">{{ number_format($liq->saldo_viaje, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="label-cell">SUMA DE GASTOS TOTAL DE VIAJE</td>
        <td class="right bold">{{ number_format($liq->sumatoria_gastos_totales, 0, ',', '.') }}</td>
        <td class="label-cell">A FAVOR DE</td>
        <td class="right bold" style="background:#fff3cd;">{{ $aFavorLabel }}</td>
    </tr>
    <tr>
        <td class="label-cell">VALOR FLETE PACTADO</td>
        <td class="right">{{ number_format($liq->valor_flete, 0, ',', '.') }}</td>
        <td class="label-cell">GANANCIA FINAL DE VIAJE</td>
        <td class="right bold">{{ number_format($liq->ganancia_viaje, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="label-cell">ANTICIPO EMPRESA DE TRANSPORTE</td>
        <td class="right">{{ number_format($liq->anticipo_empresa, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
    <tr>
        <td class="label-cell">SALDO ADEUDADO EMPRESA DE TRANSPORTE</td>
        <td class="right bold" style="background:#fff3cd;">{{ number_format($liq->saldo_pendiente, 0, ',', '.') }}</td>
        <td></td><td></td>
    </tr>
</table>

<div class="firma-box">
    FIRMA FUNCIONARIO REVISÓ
</div>

</body>
</html>
