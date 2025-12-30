<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Salida #{{ $salida->salida_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #232323; margin: 0 32px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; }
        .subtitle { color: #007bff; font-size: 16px; margin-bottom: 18px; }
        .label { color: #666; font-size: 13px; font-weight: bold; }
        table {border-collapse: collapse;width:100%;margin-bottom:25px;}
        th, td {border: 1px solid #ccc;padding: 8px;text-align: left;font-size:14px;}
        th {background: #edf5ff;}
        .footer {margin-top:35px; text-align:right; font-size:13px;color:#777;}
        .firma {margin-top:60px;border-top:1px solid #aaa;width:180px;text-align:center;color:#666;font-size:12px;padding-top:6px;}
        @media print {
            body { margin:0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div style="width:100%;text-align:center;margin-top:14px;margin-bottom:18px;">
        <img src="{{ (isset($isExport) && $isExport) ? public_path('logo.png') : asset('logo.png') }}" style="max-width:180px;max-height:80px;">
    </div>
    @if(!isset($isExport) || !$isExport)
    <div class="no-print" style="text-align:right;margin-top:18px;">
        <a href="{{ route('salidas.export', $salida) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;margin-bottom:24px;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    @endif
    <div class="title">Salida #{{ $salida->salida_number }}</div>
    <div class="subtitle">Fecha: {{ $salida->fecha->format('d/m/Y') }}</div>

    <table style="margin-bottom:15px;">
        <tr>
            <td class="label">Bodega:</td>
            <td>{{ $salida->warehouse->nombre ?? '-' }}</td>
            <td class="label">A nombre de:</td>
            <td>{{ $salida->a_nombre_de }}</td>
        </tr>
        <tr>
            <td class="label">NIT/Cédula:</td>
            <td>{{ $salida->nit_cedula }}</td>
            <td class="label">Fecha:</td>
            <td>{{ $salida->fecha->format('d/m/Y') }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Medidas</th>
                <th>Cantidad (Láminas)</th>
            </tr>
        </thead>
        <tbody>
        @foreach($salida->products as $prod)
            @php
                // La cantidad ingresada siempre está en láminas (unidades)
                $laminas = $prod->pivot->quantity;
                
                // Calcular cajas equivalentes si el producto es tipo caja (solo para referencia)
                $cajasEquivalentes = null;
                if ($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                    $cajasEquivalentes = floor($laminas / $prod->unidades_por_caja);
                }
            @endphp
            <tr>
                <td>{{ $prod->nombre }} ({{ $prod->codigo }})</td>
                <td>{{ $prod->medidas ?? '-' }}</td>
                <td>
                    {{ number_format($laminas, 0) }} láminas
                    @if($cajasEquivalentes !== null && $cajasEquivalentes > 0)
                        <br><small style="color: #666;">({{ $cajasEquivalentes }} cajas)</small>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($salida->note)
    <div style="margin-top:20px; padding:10px; background:#f8f9fa; border-radius:6px;">
        <strong>Notas:</strong> {{ $salida->note }}
    </div>
    @endif

    <div class="footer">
        <div>Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>

