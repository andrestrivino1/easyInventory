<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Transferencia</title>
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
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 12px;
            background: #f9f9f9;
        }
        .info-label {
            font-weight: bold;
            font-size: 11px;
            color: #666;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 13px;
            color: #232323;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .products-table th {
            background: #edf5ff;
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .products-table td {
            border: 1px solid #ccc;
            padding: 8px;
            font-size: 12px;
        }
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
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
        <a href="{{ route('transfer-orders.export', $transferOrder) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    @endif

    <!-- Header con logo y datos de empresa -->
    <div class="header">
        <div class="logo-section">
            @php
                $logoPath = public_path('logo.png');
                $logoBase64 = '';
                if (file_exists($logoPath)) {
                    $logoData = file_get_contents($logoPath);
                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
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
        <div class="main-title">ORDEN DE TRANSFERENCIA</div>
        <div style="font-size: 13px; margin-top: 5px;">VIDRIOS JYP SAS</div>
        <div style="font-size: 12px; color: #666;">NIT: 901.701.161-4</div>
    </div>

    <!-- Información de elaboración y aprobación -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ELABORO</div>
            <div class="info-value" style="min-height: 20px; border-bottom: 1px solid #ccc;">{{ $currentUser->nombre_completo ?? $currentUser->name ?? '' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">APROBO</div>
            <div class="info-value" style="min-height: 20px; border-bottom: 1px solid #ccc;">{{ $transferOrder->aprobo ?? '' }}</div>
        </div>
    </div>

    <!-- Información de orden y bodegas -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ORDEN DE TRANSFERENCIA No.</div>
            <div class="info-value">{{ $transferOrder->order_number }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">FECHA</div>
            <div class="info-value">{{ $transferOrder->date->format('d/m/Y') }}</div>
        </div>
    </div>

    <!-- Información de bodegas -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">BODEGA ORIGEN</div>
            <div class="info-value">
                @php
                    $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
                    $isBuenaventuraFrom = in_array($transferOrder->warehouse_from_id, $bodegasBuenaventuraIds);
                @endphp
                {{ $isBuenaventuraFrom ? 'Buenaventura' : ($transferOrder->from->nombre ?? '-') }}
            </div>
        </div>
        <div class="info-box">
            <div class="info-label">BODEGA DESTINO</div>
            <div class="info-value">
                @php
                    $isBuenaventuraTo = in_array($transferOrder->warehouse_to_id, $bodegasBuenaventuraIds);
                @endphp
                {{ $isBuenaventuraTo ? 'Buenaventura' : ($transferOrder->to->nombre ?? '-') }}
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">CIUDAD DESTINO</div>
            <div class="info-value">{{ $transferOrder->to->ciudad ?? ($transferOrder->ciudad_destino ?? '-') }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">ESTADO</div>
            <div class="info-value">
                @if($transferOrder->status == 'en_transito')
                    <span style="background:#ffc107;color:#212529;padding:2px 11px;border-radius:5px;font-size:13px;font-weight:bold;">En tránsito</span>
                @elseif($transferOrder->status == 'recibido')
                    <span style="background:#4caf50;color:white;padding:2px 11px;border-radius:5px;font-size:13px;font-weight:bold;">Recibido</span>
                @else
                    <span style="padding:2px 11px;border-radius:5px;font-size:13px;font-weight:bold;">{{ ucfirst($transferOrder->status) }}</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <table class="products-table">
        <thead>
            <tr>
                <th>PRODUCTO</th>
                <th>CONTENEDOR</th>
                <th>CAJAS</th>
                <th>UNIDADES</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transferOrder->products as $prod)
            @php
                $cantidadIngresada = $prod->pivot->quantity;
                $containerId = $prod->pivot->container_id ?? null;
                $container = null;
                
                if ($containerId) {
                    $container = \App\Models\Container::find($containerId);
                }
                
                if ($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                    $cajas = $cantidadIngresada;
                    $unidades = $cantidadIngresada * $prod->unidades_por_caja;
                } else {
                    $cajas = null;
                    $unidades = $cantidadIngresada;
                }
            @endphp
            <tr>
                <td>{{ $prod->nombre }}@if($prod->medidas) - {{ $prod->medidas }}@endif</td>
                <td style="text-align: center;">{{ $container ? $container->reference : '-' }}</td>
                <td style="text-align: center;">
                    @if($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0)
                        {{ $cajas }}
                    @else
                        -
                    @endif
                </td>
                <td style="text-align: center; font-weight: bold;">{{ number_format($unidades, 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <!-- Información del conductor -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">CONDUCTOR</div>
            <div class="info-value">{{ $transferOrder->driver->name ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">CÉDULA</div>
            <div class="info-value">{{ $transferOrder->driver->identity ?? '-' }}</div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">PLACA VEHÍCULO</div>
            <div class="info-value">{{ $transferOrder->driver->vehicle_plate ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">TELÉFONO</div>
            <div class="info-value">{{ $transferOrder->driver->phone ?? '-' }}</div>
        </div>
    </div>

    @if($transferOrder->note)
    <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
        <div class="info-label">NOTAS</div>
        <div style="font-size: 12px; margin-top: 5px;">{{ $transferOrder->note }}</div>
    </div>
    @endif

    <!-- Firmas -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-label">Patio de despacho</div>
                </div>
        <div class="signature-box">
            <div class="signature-label">Firma conductor</div>
                </div>
        <div class="signature-box">
            <div class="signature-label">Firma recibido</div>
                    </div>
                </div>

    <div class="footer">
        Generado por EasyInventory - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>
