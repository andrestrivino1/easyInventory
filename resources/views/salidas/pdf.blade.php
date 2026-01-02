<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Salida #{{ $salida->salida_number }}</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 12px; 
            color: #232323; 
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-section img {
            max-width: 80px;
            max-height: 80px;
        }
        .company-info {
            font-weight: bold;
            font-size: 14px;
        }
        .company-info .company-name {
            font-size: 16px;
            margin-bottom: 4px;
        }
        .title-section {
            text-align: center;
            margin: 20px 0;
        }
        .main-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-box {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 13px;
            font-weight: 500;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .products-table th {
            background: #0066cc;
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #004499;
        }
        .products-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .products-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .total-row {
            background: #e6f2ff !important;
            font-weight: bold;
        }
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 60px;
        }
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 10px;
            text-align: center;
            font-size: 11px;
        }
        .signature-label {
            font-weight: bold;
            margin-bottom: 50px;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 11px;
            color: #777;
        }
        @media print {
            body { margin: 0; padding: 15px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @if(!isset($isExport) || !$isExport)
    <div class="no-print" style="text-align:right;margin-bottom:20px;">
        <a href="{{ route('salidas.export', $salida) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    @endif

    <!-- Header con logo y datos de empresa -->
    <div class="header">
        <div class="logo-section">
            @php
                try {
                    $logoPath = public_path('logo.png');
                    $logoBase64 = '';
                    if (file_exists($logoPath) && is_readable($logoPath)) {
                        $logoData = @file_get_contents($logoPath);
                        if ($logoData !== false) {
                            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                        }
                    }
                } catch (\Exception $e) {
                    $logoBase64 = '';
                }
            @endphp
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 80px; max-height: 80px;">
            @else
                <img src="{{ asset('logo.png') }}" alt="Logo" style="max-width: 80px; max-height: 80px;">
            @endif
            <div class="company-info">
                <div class="company-name">VIDRIOS J&P S.A.S.</div>
                <div>NIT: 901.701.161-4</div>
            </div>
        </div>
    </div>

    <!-- Título principal -->
    <div class="title-section">
        <div class="main-title">ORDEN DE SALIDA DE INVENTARIO</div>
        <div style="font-size: 13px; margin-top: 5px;">VIDRIOS JYP SAS</div>
        <div style="font-size: 12px; color: #666;">NIT: 901.701.161-4</div>
    </div>

    <!-- Información de elaboración y aprobación -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ELABORO</div>
            <div class="info-value" style="min-height: 20px; border-bottom: 1px solid #ccc;">{{ isset($currentUser) ? ($currentUser->nombre_completo ?? $currentUser->name ?? '') : '' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">APROBO</div>
            <div class="info-value" style="min-height: 20px; border-bottom: 1px solid #ccc;">{{ $salida->aprobo ?? '' }}</div>
        </div>
    </div>

    <!-- Información de orden y bodega -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ORDEN DE CARGUE No.</div>
            <div class="info-value">{{ $salida->salida_number }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">BODEGA DE CARGUE</div>
            <div class="info-value">
                @php
                    $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
                    $isBuenaventura = in_array($salida->warehouse_id, $bodegasBuenaventuraIds);
                @endphp
                {{ $isBuenaventura ? 'Buenaventura' : ($salida->warehouse->nombre ?? '-') }}
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">FECHA</div>
            <div class="info-value">{{ $salida->fecha->format('d/m/Y') }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">A NOMBRE DE</div>
            <div class="info-value">{{ $salida->a_nombre_de }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">NIT/CÉDULA</div>
            <div class="info-value">{{ $salida->nit_cedula }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">CIUDAD DESTINO</div>
            <div class="info-value" style="min-height: 20px; border-bottom: 1px solid #ccc;">{{ $salida->ciudad_destino ?? '' }}</div>
        </div>
    </div>

    <!-- Tabla de productos -->
    @php
        try {
            $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
            $isBuenaventura = in_array($salida->warehouse_id ?? 0, $bodegasBuenaventuraIds);
        } catch (\Exception $e) {
            $bodegasBuenaventuraIds = [];
            $isBuenaventura = false;
        }
        $totalCantidad = 0;
    @endphp
    <table class="products-table">
        <thead>
            <tr>
                <th>PRODUCTO</th>
                <th>CONTENEDOR</th>
                <th>CANTIDAD</th>
            </tr>
        </thead>
        <tbody>
        @foreach($salida->products as $prod)
            @php
                $laminas = $prod->pivot->quantity;
                $containerId = $prod->pivot->container_id ?? null;
                $container = null;
                
                if ($containerId) {
                    $container = \App\Models\Container::find($containerId);
                }
                
                // Determinar cantidad a mostrar
                if ($isBuenaventura && $prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                    $cantidadMostrar = floor($laminas / $prod->unidades_por_caja);
                    $unidadMostrar = 'cajas';
                    $totalCantidad += $cantidadMostrar;
                } else {
                    $cantidadMostrar = $laminas;
                    $unidadMostrar = 'láminas';
                    $totalCantidad += $cantidadMostrar;
                }
            @endphp
            <tr>
                <td>{{ $prod->nombre }}@if($prod->medidas) - {{ $prod->medidas }}@endif</td>
                <td style="text-align: center;">{{ $container ? $container->reference : '-' }}</td>
                <td style="text-align: center; font-weight: bold;">{{ number_format($cantidadMostrar, 0) }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2" style="text-align: right; padding-right: 15px;"><strong>TOTAL</strong></td>
            <td style="text-align: center;"><strong>{{ number_format($totalCantidad, 0) }}</strong></td>
        </tr>
        </tbody>
    </table>

    @if($salida->note)
    <div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 11px;">
        <strong>OBSERVACIONES:</strong> {{ $salida->note }}
    </div>
    @endif

    <!-- Información del conductor -->
    @if($salida->driver)
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">CONDUCTOR</div>
            <div class="info-value">{{ $salida->driver->name ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">CÉDULA</div>
            <div class="info-value">{{ $salida->driver->identity ?? '-' }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">PLACA VEHÍCULO</div>
            <div class="info-value">{{ $salida->driver->vehicle_plate ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">TELÉFONO</div>
            <div class="info-value">{{ $salida->driver->phone ?? '-' }}</div>
        </div>
    </div>
    @endif

    <!-- Firmas -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-label">FIRMA CONDUCTOR</div>
            <div style="height: 50px;"></div>
        </div>
        <div class="signature-box">
            <div class="signature-label">FIRMA BODEGA</div>
            <div style="height: 50px;"></div>
        </div>
    </div>

    <div class="footer">
        <div>Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>
</html>
