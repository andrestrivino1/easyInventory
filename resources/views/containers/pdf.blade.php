<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Entrada - Contenedor</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #232323; margin: 0 32px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; }
        .subtitle { color: #007bff; font-size: 16px; margin-bottom: 18px; }
        .label { color: #666; font-size: 13px; font-weight: bold; }
        table {border-collapse: collapse;width:100%;margin-bottom:25px;}
        th, td {border: 1px solid #ccc;padding: 8px;text-align: left;font-size:14px;}
        th {background: #edf5ff;}
        .badge { display:inline-block; padding:2px 11px; border-radius:5px; font-size:13px; font-weight: bold;}
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
        @php
            $logoPath = public_path('logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
            }
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-width:180px;max-height:80px;">
        @else
            <img src="{{ asset('logo.png') }}" style="max-width:180px;max-height:80px;">
        @endif
    </div>
    @if(!isset($isExport) || !$isExport)
    <div class="no-print" style="text-align:right;margin-top:18px;">
        <a href="{{ route('containers.export', $container) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;margin-bottom:24px;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    @endif
    <div class="title">Orden de Entrada - Contenedor</div>
    <div class="subtitle">Referencia: {{ $container->reference }}</div>
    <div class="subtitle" style="font-size: 14px; color: #666;">Fecha: {{ now()->format('d/m/Y h:i A') }}</div>

    <table style="margin-bottom:15px;">
        <tr>
            <td class="label">Referencia del Contenedor:</td>
            <td colspan="3"><strong>{{ $container->reference }}</strong></td>
        </tr>
        @if($container->note)
        <tr>
            <td class="label">Observaciones:</td>
            <td colspan="3">{{ $container->note }}</td>
        </tr>
        @endif
    </table>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                <th>Cajas</th>
                <th>Láminas por Caja</th>
                <th>Total Láminas</th>
            </tr>
        </thead>
        <tbody>
        @php
            $totalBoxes = 0;
            $totalSheets = 0;
        @endphp
        @foreach($container->products as $product)
            @php
                $boxes = $product->pivot->boxes;
                $sheetsPerBox = $product->pivot->sheets_per_box;
                $totalProductSheets = $boxes * $sheetsPerBox;
                $totalBoxes += $boxes;
                $totalSheets += $totalProductSheets;
            @endphp
            <tr>
                <td><strong>{{ $product->nombre }}</strong></td>
                <td>{{ $product->codigo }}</td>
                <td>{{ $boxes }}</td>
                <td>{{ $sheetsPerBox }}</td>
                <td><strong>{{ $totalProductSheets }}</strong></td>
            </tr>
        @endforeach
        <tr style="background: #f0f0f0; font-weight: bold;">
            <td colspan="2" style="text-align: right;"><strong>TOTALES:</strong></td>
            <td><strong>{{ $totalBoxes }}</strong></td>
            <td>-</td>
            <td><strong>{{ $totalSheets }}</strong></td>
        </tr>
        </tbody>
    </table>

    <table style="width:100%;margin-top:45px;text-align:center;border:0;">
        <tr>
            <td style="border:none;">
                <div class="firma">
                    Firma recibido
                </div>
            </td>
            <td style="border:none;">
                <div class="firma">
                    Firma almacén
                </div>
            </td>
            <td style="border:none;">
                <div class="firma">
                    Firma supervisor
                </div>
            </td>
        </tr>
    </table>
    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por VIDRIOS J&P S.A.S. - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>

