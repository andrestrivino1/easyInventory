<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Orden de Salida #{{ $salida->salida_number }}</title>
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
    .logo-section img {
    max-width: 50px;
    max-height: 50px;
    display: block;
    }
    .company-info {
    font-weight: bold;
    font-size: 11px;
    padding-left: 3px;
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
    .signatures {
    display: table;
    width: 100%;
    border-collapse: separate;
    border-spacing: 8px 0;
    margin-top: 35px;
    }
    .signature-box {
    display: table-cell;
    width: 50%;
    text-align: center;
    font-size: 8px;
    vertical-align: top;
    border-top: 1px solid #333;
    padding-top: 40px;
    min-height: 80px;
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
            <a href="{{ route('salidas.export', $salida) }}" target="_blank"
                style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;"><i
                    class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
            <button onclick="window.print()"
                style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
        </div>
    @endif

    <!-- Header con logo, título y fotos -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="logo-section">
                        <table>
                            <tr>
                                <td style="width: auto;">
                                    @php
                                        $logoPath = public_path('logo.png');
                                        $logoBase64 = '';
                                        if (file_exists($logoPath)) {
                                            $logoData = file_get_contents($logoPath);
                                            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                                        }
                                    @endphp
                                    @if($logoBase64)
                                        <img src="{{ $logoBase64 }}" alt="Logo">
                                    @else
                                        <img src="{{ asset('logo.png') }}" alt="Logo">
                                    @endif
                                </td>
                                <td class="company-info">
                                    <div class="company-name">VIDRIOS J&P S.A.S.</div>
                                    <div style="font-size: 10px;">NIT: 901.701.161-4</div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="title-section">
                        <div class="main-title">ORDEN DE SALIDA</div>
                    </div>
                </td>
                <td class="header-right">
                    @if($salida->driver && ($salida->driver->photo_path || $salida->driver->vehicle_photo_path))
                        <div class="photos-section">
                            <table>
                                <tr>
                                    @if($salida->driver->photo_path)
                                        @php
                                            $photoPath = storage_path('app/public/' . $salida->driver->photo_path);
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
                                                    <div class="photo-label">Conductor</div>
                                                    <img src="{{ $photoBase64 }}" alt="Conductor">
                                                </div>
                                            </td>
                                        @endif
                                    @endif
                                    @if($salida->driver->vehicle_photo_path)
                                        @php
                                            $vPhotoPath = storage_path('app/public/' . $salida->driver->vehicle_photo_path);
                                            $vPhotoBase64 = '';
                                            if (file_exists($vPhotoPath)) {
                                                $vPhotoData = file_get_contents($vPhotoPath);
                                                $imageInfo = @getimagesize($vPhotoPath);
                                                $mimeType = $imageInfo ? $imageInfo['mime'] : 'image/jpeg';
                                                $vPhotoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($vPhotoData);
                                            }
                                        @endphp
                                        @if($vPhotoBase64)
                                            <td>
                                                <div class="photo-item">
                                                    <div class="photo-label">Vehículo</div>
                                                    <img src="{{ $vPhotoBase64 }}" alt="Vehículo">
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

    <!-- Información de elaboración y aprobación -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">ELABORO</div>
                        <div class="info-value">{{ $currentUser->nombre_completo ?? $currentUser->name ?? '' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">APROBO</div>
                        <div class="info-value">{{ $salida->aprobo ?? '' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Información de orden y bodega -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">ORDEN No.</div>
                        <div class="info-value">{{ $salida->salida_number }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">BODEGA</div>
                        <div class="info-value">
                            @php
                                $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
                                $isBuenaventura = in_array($salida->warehouse_id, $bodegasBuenaventuraIds);
                            @endphp
                            {{ $isBuenaventura ? 'Buenaventura' : ($salida->warehouse->nombre ?? '-') }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Información adicional -->
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">FECHA</div>
                        <div class="info-value">{{ $salida->fecha->format('d/m/Y') }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">A NOMBRE DE</div>
                        <div class="info-value">{{ $salida->a_nombre_de }}</div>
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
                        <div class="info-label">NIT/CÉDULA</div>
                        <div class="info-value">{{ $salida->nit_cedula }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">CIUDAD DESTINO</div>
                        <div class="info-value">{{ $salida->ciudad_destino ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
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

                    // Lógica original: Determinar si mostrar como cajas o láminas
                    if ($isBuenaventura && $prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                        $cantidadMostrar = floor($laminas / $prod->unidades_por_caja);
                        $unidadMostrar = 'cajas';
                    } else {
                        $cantidadMostrar = $laminas;
                        $unidadMostrar = 'láminas';
                    }
                    $totalCantidad += $cantidadMostrar;
                @endphp
                <tr>
                    <td>{{ $prod->nombre }}@if($prod->medidas) - {{ $prod->medidas }}@endif</td>
                    <td style="text-align: center;">{{ $container ? $container->reference : '-' }}</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($cantidadMostrar, 0) }} {{ $unidadMostrar }}</td>
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
    @if($salida->driver_name || $salida->driver)
    <div class="info-grid">
        <table>
            <tr>
                <td>
                    <div class="info-box">
                        <div class="info-label">CONDUCTOR</div>
                        <div class="info-value">{{ $salida->driver_name ?? '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">CÉDULA</div>
                        <div class="info-value">{{ $salida->driver_identity ?? '-' }}</div>
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
                        <div class="info-value">{{ $salida->driver_plate ?? '-' }}</div>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <div class="info-label">TELÉFONO</div>
                        <div class="info-value">{{ $salida->driver_phone ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Firmas -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-label">FIRMA CONDUCTOR</div>
        </div>
        <div class="signature-box">
            <div class="signature-label">FIRMA BODEGA</div>
        </div>
    </div>

    <div class="footer">
        <div>Generado el {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</body>

</html>