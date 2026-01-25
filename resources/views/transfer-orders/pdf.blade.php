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
            width: 100%;
            margin-bottom: 8px;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left {
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            width: 30%;
            vertical-align: top;
            text-align: right;
        }
        .logo-section {
            margin-bottom: 5px;
        }
        .logo-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-section td {
            vertical-align: middle;
            padding: 0;
        }
        .logo-section td:first-child {
            padding-right: 0;
            padding-left: 0;
        }
        .logo-section td:last-child {
            padding-left: 0;
            padding-right: 0;
        }
        .logo-section img {
            max-width: 50px;
            max-height: 50px;
            display: block;
            margin-right: 0;
        }
        .company-info {
            font-weight: bold;
            font-size: 11px;
            padding-left: 0;
            padding-right: 0;
            margin-left: -5px;
        }
        .company-info div {
            margin-left: 0;
            padding-left: 0;
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
            text-align: right;
            margin-left: auto;
            width: auto;
        }
        .photos-section table {
            border-collapse: collapse;
            margin-left: auto;
            width: auto;
        }
        .photos-section td {
            vertical-align: top;
            padding: 0 2px;
        }
        .photo-item {
            text-align: center;
            width: 70px;
        }
        .photo-item img {
            max-width: 70px;
            max-height: 70px;
            width: 70px;
            height: 70px;
            border: 1px solid #ddd;
            border-radius: 4px;
            object-fit: cover;
            display: block;
        }
        .photo-label {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            text-align: center;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 4px;
        }
        .info-grid:last-child {
            margin-bottom: 0;
        }
        .info-grid table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 20px 4px;
        }
        .info-grid td {
            width: 50%;
            padding: 0;
            vertical-align: top;
        }
        .info-box {
            border: 1px solid #ddd;
            padding: 6px;
            background: #f9f9f9;
            width: 100%;
            box-sizing: border-box;
            min-height: 32px;
            display: block;
            border-radius: 0;
            margin-bottom: 0;
        }
        .info-box-full {
            padding: 6px;
            display: block;
        }
        .info-box-full .info-label {
            padding: 0;
            margin-bottom: 3px;
        }
        .info-box-full .info-value {
            padding: 0;
        }
        .info-label {
            font-weight: bold;
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
            text-transform: uppercase;
            display: block;
        }
        .info-value {
            font-size: 11px;
            color: #232323;
            display: block;
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
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="logo-section">
                        <table style="border-collapse: collapse; border-spacing: 0; width: auto;">
                            <tr>
                                <td style="padding: 0; margin: 0; vertical-align: middle; width: auto;">
                                    @php
                                        $logoPath = public_path('logo.png');
                                        $logoBase64 = '';
                                        if (file_exists($logoPath)) {
                                            $logoData = file_get_contents($logoPath);
                                            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                                        }
                                    @endphp
                                    @if($logoBase64)
                                        <img src="{{ $logoBase64 }}" alt="Logo" style="max-width: 50px; max-height: 50px; display: inline-block; margin: 0; padding: 0; vertical-align: middle;">
                                    @else
                                        <img src="{{ asset('logo.png') }}" alt="Logo" style="max-width: 50px; max-height: 50px; display: inline-block; margin: 0; padding: 0; vertical-align: middle;">
                                    @endif
                                </td>
                                <td class="company-info" style="padding: 0 0 0 3px; margin: 0; vertical-align: middle; width: auto;">
                                    <div class="company-name" style="margin: 0; padding: 0;">VIDRIOS J&P S.A.S.</div>
                                    <div style="font-size: 10px; margin: 0; padding: 0;">NIT: 901.701.161-4</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <!-- Título principal a la izquierda -->
                    <div class="title-section">
                        <div class="main-title">ORDEN DE CARGUE</div>
                    </div>
                </td>
                <td class="header-right">
                    <!-- Fotos del conductor y vehículo a la derecha -->
                    @if($transferOrder->driver && ($transferOrder->driver->photo_path || $transferOrder->driver->vehicle_photo_path))
                        <div class="photos-section">
                            <table>
                                <tr>
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
                                            <td>
                                                <div class="photo-item">
                                                    <div class="photo-label">Foto del conductor</div>
                                                    <img src="{{ $photoBase64 }}" alt="Foto del conductor">
                                                </div>
                                            </td>
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
                                            <td>
                                                <div class="photo-item">
                                                    <div class="photo-label">Foto del vehículo</div>
                                                    <img src="{{ $vehiclePhotoBase64 }}" alt="Foto del vehículo">
                                                </div>
                                            </td>
                                        @endif
                                    @endif
                                </tr>
                            </table>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <!-- Información de elaboración y propietario de la carga -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">ELABORO</div>
                        <div class="info-value" style="min-height: 15px; border-bottom: 1px solid #ccc;">{{ $currentUser->nombre_completo ?? $currentUser->name ?? '' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">PROPIETARIO DE LA CARGA</div>
                        <div class="info-value" style="min-height: 15px; border-bottom: 1px solid #ccc;">{{ $transferOrder->driver->vehicle_owner ?? ($transferOrder->aprobo ?? '') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Información de orden y bodegas -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">ORDEN DE CARGUE No.</div>
                        <div class="info-value">{{ $transferOrder->order_number }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">FECHA</div>
                        <div class="info-value">{{ $transferOrder->date->format('d/m/Y') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Información de bodegas -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
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
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">CIUDAD DESTINO</div>
                        <div class="info-value">{{ $transferOrder->to->ciudad ?? ($transferOrder->ciudad_destino ?? '-') }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-grid">
        <table>
            <tr>
                <td colspan="2">
                    <div class="info-box info-box-full">
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
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de productos -->
    <table class="products-table" style="width: 96.5%; margin-left: 20px; border-collapse: collapse; margin-top: 10px; margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width: 11%; background: #edf5ff; border: 1px solid #ccc; padding: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: left;">PRODUCTO</th>
                <th style="width: 8%; background: #edf5ff; border: 1px solid #ccc; padding: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: center;">CONTENEDOR</th>
                <th style="width: 8%; background: #edf5ff; border: 1px solid #ccc; padding: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: center;">CAJAS</th>
                <th style="width: 8%; background: #edf5ff; border: 1px solid #ccc; padding: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: center;">UNIDADES</th>
                <th style="width: 8%; background: #edf5ff; border: 1px solid #ccc; padding: 4px; font-size: 8px; font-weight: bold; text-transform: uppercase; text-align: center;">PESO (KG)</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transferOrder->products as $prod)
            @php
                $cantidadIngresada = $prod->pivot->quantity;
                $containerId = $prod->pivot->container_id ?? null;
                $weightPerBox = $prod->pivot->weight_per_box ?? 0;
                $rowWeight = $cantidadIngresada * $weightPerBox;
                $totalTransferWeight = ($totalTransferWeight ?? 0) + $rowWeight;
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
                <td style="width: 5%; border: 1px solid #ccc; padding: 3px; font-size: 9px;">{{ $prod->nombre }}@if($prod->medidas) - {{ $prod->medidas }}@endif</td>
                <td style="width: 8%; border: 1px solid #ccc; padding: 3px; font-size: 9px; text-align: center;">{{ $container ? $container->reference : '-' }}</td>
                <td style="width: 8%; border: 1px solid #ccc; padding: 3px; font-size: 9px; text-align: center;">
                    @if($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0)
                        {{ $cajas }}
                    @else
                        -
                    @endif
                </td>
                <td style="width: 8%; border: 1px solid #ccc; padding: 3px; font-size: 9px; text-align: center; font-weight: bold;">{{ number_format($unidades, 0) }}</td>
                <td style="width: 8%; border: 1px solid #ccc; padding: 3px; font-size: 9px; text-align: center;">{{ number_format($rowWeight, 2) }}</td>
            </tr>
        @endforeach
            <tr class="total-row">
                <td colspan="4" style="text-align: right; border: 1px solid #ccc; padding: 4px;">TOTAL PESO ESTIMADO (KG):</td>
                <td style="text-align: center; border: 1px solid #ccc; padding: 4px;">{{ number_format($totalTransferWeight ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Información del conductor -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">CONDUCTOR</div>
                        <div class="info-value">{{ $transferOrder->driver->name ?? '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">CÉDULA</div>
                        <div class="info-value">{{ $transferOrder->driver->identity ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">PLACA VEHÍCULO</div>
                        <div class="info-value">{{ $transferOrder->driver->vehicle_plate ?? '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">TELÉFONO</div>
                        <div class="info-value">{{ $transferOrder->driver->phone ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
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
