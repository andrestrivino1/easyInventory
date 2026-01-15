<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Cargue</title>
    <style>
        @page {
            margin: 6mm;
            size: A4 portrait;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9px; 
            color: #232323; 
            margin: 0;
            padding: 3px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
        }
        .header-left {
            flex: 1;
        }
        .header-right {
            flex: 0 0 auto;
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: flex-start;
            margin-left: 15px;
            min-width: 100px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 5px;
        }
        .logo-section img {
            max-width: 50px;
            max-height: 50px;
        }
        .company-info {
            font-weight: bold;
            font-size: 11px;
        }
        .company-info .company-name {
            font-size: 13px;
            margin-bottom: 2px;
        }
        .title-section {
            text-align: left;
            margin: 5px 0;
        }
        .main-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .photos-section {
            display: flex;
            flex-direction: row;
            gap: 8px;
            align-items: flex-start;
            justify-content: flex-end;
            width: 100%;
        }
        .photo-item {
            text-align: center;
            width: auto;
            max-width: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .photo-item img {
            max-width: 70px;
            max-height: 70px;
            width: 70px;
            height: 70px;
            border: 1px solid #ddd;
            border-radius: 4px;
            object-fit: cover;
        }
        .photo-label {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            text-align: center;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 5px;
            background: #f9f9f9;
        }
        .info-label {
            font-weight: bold;
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 11px;
            color: #232323;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        .products-table th {
            background: #edf5ff;
            border: 1px solid #ccc;
            padding: 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .products-table td {
            border: 1px solid #ccc;
            padding: 3px;
            font-size: 9px;
        }
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        .signature-section {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 35px;
        }
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            font-size: 8px;
            vertical-align: top;
            border-top: 1px solid #333;
            padding-top: 40px;
            min-height: 80px;
        }
        .signature-line {
            display: none;
        }
        .signature-label {
            font-weight: bold;
            margin-top: 5px;
        }
        .footer {
            margin-top: 3px;
            text-align: right;
            font-size: 8px;
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

    <!-- Header con logo, título y fotos -->
    <div class="header">
        <div class="header-left">
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
                    <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 50px; max-height: 50px;">
                @else
                    <img src="{{ asset('logo.png') }}" alt="Logo" style="max-width: 50px; max-height: 50px;">
                @endif
                <div class="company-info">
                    <div class="company-name">VIDRIOS J&P S.A.S.</div>
                    <div style="font-size: 10px;">NIT: 901.701.161-4</div>
                </div>
            </div>
            <!-- Título principal a la izquierda -->
            <div class="title-section">
                <div class="main-title">ORDEN DE CARGUE</div>
            </div>
        </div>
        <div class="header-right">
            <!-- Fotos del conductor y vehículo a la derecha -->
            @if($transferOrder->driver && ($transferOrder->driver->photo_path || $transferOrder->driver->vehicle_photo_path))
            <div class="photos-section">
                @if($transferOrder->driver->photo_path)
                    @php
                        $photoPath = storage_path('app/public/' . $transferOrder->driver->photo_path);
                        $photoBase64 = '';
                        if (file_exists($photoPath)) {
                            $photoData = file_get_contents($photoPath);
                            $imageInfo = @getimagesize($photoPath);
                            $mimeType = $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
                            $photoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($photoData);
                        }
                    @endphp
                    @if($photoBase64)
                        <div class="photo-item">
                            <div class="photo-label">Foto del conductor</div>
                            <img src="{{ $photoBase64 }}" alt="Foto del conductor">
                        </div>
                    @endif
                @endif
                @if($transferOrder->driver->vehicle_photo_path)
                    @php
                        $vehiclePhotoPath = storage_path('app/public/' . $transferOrder->driver->vehicle_photo_path);
                        $vehiclePhotoBase64 = '';
                        if (file_exists($vehiclePhotoPath)) {
                            $vehiclePhotoData = file_get_contents($vehiclePhotoPath);
                            $imageInfo = @getimagesize($vehiclePhotoPath);
                            $mimeType = $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
                            $vehiclePhotoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($vehiclePhotoData);
                        }
                    @endphp
                    @if($vehiclePhotoBase64)
                        <div class="photo-item">
                            <div class="photo-label">Foto del vehículo</div>
                            <img src="{{ $vehiclePhotoBase64 }}" alt="Foto del vehículo">
                        </div>
                    @endif
                @endif
            </div>
            @endif
        </div>
    </div>

    <!-- Información de elaboración y propietario de la carga -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ELABORO</div>
            <div class="info-value" style="min-height: 15px; border-bottom: 1px solid #ccc;">{{ $currentUser->nombre_completo ?? $currentUser->name ?? '' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">PROPIETARIO DE LA CARGA</div>
            <div class="info-value" style="min-height: 15px; border-bottom: 1px solid #ccc;">{{ $transferOrder->driver->vehicle_owner ?? ($transferOrder->aprobo ?? '') }}</div>
        </div>
    </div>

    <!-- Información de orden y bodegas -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">ORDEN DE CARGUE No.</div>
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
                    <span style="background:#ffc107;color:#212529;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:bold;">En tránsito</span>
                @elseif($transferOrder->status == 'recibido')
                    <span style="background:#4caf50;color:white;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:bold;">Recibido</span>
                @else
                    <span style="padding:2px 8px;border-radius:5px;font-size:10px;font-weight:bold;">{{ ucfirst($transferOrder->status) }}</span>
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
    <div style="margin-top: 10px; padding: 8px; background: #f9f9f9; border: 1px solid #ddd;">
        <div class="info-label">NOTAS</div>
        <div style="font-size: 10px; margin-top: 3px;">{{ $transferOrder->note }}</div>
    </div>
    @endif

    <!-- Firmas -->
    <div class="signature-section" style="margin-top: 30px;">
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
        Generado por VIDRIOS J&P S.A.S. - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>
