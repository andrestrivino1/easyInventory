<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Transferencia</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #232323; margin: 0 32px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; }
        .subtitle { color: #007bff; font-size: 16px; margin-bottom: 18px; }
        .label { color: #666; font-size: 13px; font-weight: bold; }
        table {border-collapse: collapse;width:100%;margin-bottom:25px;}
        th, td {border: 1px solid #ccc;padding: 8px;text-align: left;font-size:14px;}
        th {background: #edf5ff;}
        .badge { display:inline-block; padding:2px 11px; border-radius:5px; font-size:13px; font-weight: bold;}
        .transito {background:#ffc107;color:#212529;}
        .recibido {background:#4caf50;color:white;}
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
        <a href="{{ route('transfer-orders.export', $transferOrder) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;margin-bottom:24px;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    @endif
    <div class="title">Orden de Transferencia #{{ $transferOrder->order_number }}</div>
    <div class="subtitle">Fecha: {{ $transferOrder->date->setTimezone(config('app.timezone'))->format('d/m/Y h:i A') }}</div>

    <table style="margin-bottom:15px;">
        <tr>
            <td class="label">Origen:</td>
            <td>{{ $transferOrder->from->nombre }}</td>
            <td class="label">Destino:</td>
            <td>{{ $transferOrder->to->nombre }}</td>
        </tr>
        <tr>
            <td class="label">Estado:</td>
            <td colspan="3">
                @if($transferOrder->status == 'en_transito')
                    <span class="badge transito">En tránsito</span>
                @elseif($transferOrder->status == 'recibido')
                    <span class="badge recibido">Recibido</span>
                @else
                    <span class="badge">{{ ucfirst($transferOrder->status) }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Medidas</th>
                <th>Contenedor</th>
                <th>Cajas</th>
                <th>Unidades</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transferOrder->products as $prod)
            @php
                // pivot->quantity almacena la cantidad ingresada según el tipo de medida
                // Si es tipo "caja", quantity = cantidad de cajas
                // Si es tipo "unidad", quantity = cantidad de unidades
                $cantidadIngresada = $prod->pivot->quantity;
                
                if ($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                    // Producto medido en cajas
                    $cajas = $cantidadIngresada; // La cantidad ingresada ya son cajas
                    $unidades = $cantidadIngresada * $prod->unidades_por_caja; // Convertir cajas a unidades
                } else {
                    // Producto medido en unidades
                    $cajas = null; // No aplica
                    $unidades = $cantidadIngresada; // La cantidad ingresada ya son unidades
                }
            @endphp
            @php
                $container = null;
                if ($prod->pivot->container_id && isset($containers)) {
                    $container = $containers->get($prod->pivot->container_id);
                } elseif ($prod->pivot->container_id) {
                    $container = \App\Models\Container::find($prod->pivot->container_id);
                }
            @endphp
            <tr>
                <td>{{ $prod->nombre }}</td>
                <td>{{ $prod->medidas ?? '-' }}</td>
                <td>{{ $container ? $container->reference : '-' }}</td>
                <td>
                    @if($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0)
                        {{ $cajas }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $unidades }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <table style="margin-bottom:15px;">
        <tr>
            <td class="label">Conductor:</td>
            <td>{{ $transferOrder->driver->name ?? '-' }}</td>
            <td class="label">Cédula:</td>
            <td>{{ $transferOrder->driver->identity ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Placa Vehículo:</td>
            <td>{{ $transferOrder->driver->vehicle_plate ?? '-' }}</td>
            <td class="label">Teléfono:</td>
            <td>{{ $transferOrder->driver->phone ?? '-' }}</td>
        </tr>
    </table>
    @if($transferOrder->note)
    <div style="margin-top:8px;"><span class="label">Notas:</span> {{ $transferOrder->note }}</div>
    @endif
    <table style="width:100%;margin-top:45px;text-align:center;border:0;">
        <tr>
            <td style="border:none;">
                <div class="firma">
                    Patio de despacho
                </div>
            </td>
            <td style="border:none;">
                <div class="firma">
                    Firma conductor
                </div>
            </td>
            <td style="border:none;">
                <div class="firma">
                    Firma recibido
                </div>
            </td>
        </tr>
    </table>
    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por EasyInventory - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>
