<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Stock</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; color: #232323; margin: 0 32px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; text-align: center; }
        .subtitle { color: #007bff; font-size: 14px; margin-bottom: 18px; text-align: center; }
        .label { color: #666; font-size: 13px; font-weight: bold; }
        table {border-collapse: collapse;width:100%;margin-bottom:25px; page-break-inside: auto;}
        th, td {border: 1px solid #ccc;padding: 6px;text-align: left;font-size:11px;}
        th {background: #edf5ff; font-weight: bold;}
        tr { page-break-inside: avoid; page-break-after: auto; }
        .section-title { font-size: 16px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; color: #0066cc; }
        .footer {margin-top:35px; text-align:right; font-size:13px;color:#777;}
        @media print {
            body { margin:0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div style="width:100%;text-align:center;margin-top:14px;margin-bottom:18px;">
        <img src="{{ (isset($isExport) && $isExport) ? base_path('public/logo.png') : asset('logo.png') }}" style="max-width:180px;max-height:80px;">
    </div>
    <div class="title">Inventario de Stock</div>
    <div class="subtitle">Fecha: {{ date('d/m/Y H:i') }}</div>
    @if($selectedWarehouseId)
        <div class="subtitle">Bodega: {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}</div>
    @else
        <div class="subtitle">Bodega: Todos los bodegas</div>
    @endif

    <!-- Sección de Productos -->
    <div class="section-title">PRODUCTOS</div>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Bodega</th>
                <th>Medidas</th>
                <th>Cajas</th>
                <th>Láminas</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr>
                <td>{{ $product->codigo }}</td>
                <td>{{ $product->nombre }}</td>
                <td>{{ $product->almacen->nombre ?? '-' }}</td>
                <td>{{ $product->medidas ?? '-' }}</td>
                <td>
                    @if($product->tipo_medida === 'caja' && $product->cajas !== null)
                        {{ number_format($product->cajas, 0) }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ number_format($product->stock, 0) }}</td>
                <td>{{ $product->estado ? 'Activo' : 'Inactivo' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Sección de Contenedores -->
    @if($selectedWarehouseId == $ID_PABLO_ROJAS || !$selectedWarehouseId)
    <div class="section-title">CONTENEDORES</div>
    <table>
        <thead>
            <tr>
                <th>Referencia</th>
                <th>Productos</th>
                <th>Total Cajas</th>
                <th>Total Láminas</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($containers as $container)
            @php
                $totalBoxes = 0;
                $totalSheets = 0;
                foreach($container->products as $product) {
                    $totalBoxes += $product->pivot->boxes;
                    $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                }
            @endphp
            <tr>
                <td><strong>{{ $container->reference }}</strong></td>
                <td>
                    @foreach($container->products as $product)
                        {{ $product->nombre }} ({{ $product->pivot->boxes }} cajas × {{ $product->pivot->sheets_per_box }} láminas)@if(!$loop->last)<br>@endif
                    @endforeach
                </td>
                <td><strong>{{ $totalBoxes }}</strong></td>
                <td><strong>{{ number_format($totalSheets, 0) }}</strong></td>
                <td>{{ $container->note ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Sección de Transferencias -->
    <div class="section-title">TRANSFERENCIAS</div>
    <table>
        <thead>
            <tr>
                <th>No. Orden</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Productos</th>
                <th>Conductor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transferOrders as $transfer)
            <tr>
                <td>{{ $transfer->order_number }}</td>
                <td>{{ $transfer->from->nombre ?? '-' }}</td>
                <td>{{ $transfer->to->nombre ?? '-' }}</td>
                <td>{{ ucfirst($transfer->status) }}</td>
                <td>{{ $transfer->date->format('d/m/Y H:i') }}</td>
                <td>
                    @foreach($transfer->products as $prod)
                        {{ $prod->nombre }} ({{ $prod->pivot->quantity }} {{ $prod->tipo_medida === 'caja' ? 'cajas' : 'unidades' }})@if(!$loop->last)<br>@endif
                    @endforeach
                </td>
                <td>{{ $transfer->driver->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por EasyInventory - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>

