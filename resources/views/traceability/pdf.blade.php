<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Trazabilidad de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; color: #232323; margin: 0 15px; }
        .title { font-size: 18px; font-weight: bold; margin-top: 20px; margin-bottom: 6px; text-align: center; }
        .subtitle { color: #007bff; font-size: 10px; margin-bottom: 12px; text-align: center; }
        table {border-collapse: collapse;width:100%;margin-bottom:20px; page-break-inside: auto; font-size: 8px;}
        th, td {border: 1px solid #ccc;padding: 3px;text-align: left;font-size:8px; word-wrap: break-word;}
        th {background: #edf5ff; font-weight: bold; font-size: 8px;}
        tr { page-break-inside: avoid; page-break-after: auto; }
        .badge-entrada { background: #28a745; color: white; padding: 1px 4px; border-radius: 2px; font-size: 7px; }
        .badge-salida { background: #dc3545; color: white; padding: 1px 4px; border-radius: 2px; font-size: 7px; }
        .quantity-positive { color: #28a745; font-weight: bold; }
        .quantity-negative { color: #dc3545; font-weight: bold; }
        .footer {margin-top:30px; text-align:right; font-size:10px;color:#777;}
        .col-fecha { width: 7%; }
        .col-tipo { width: 4%; }
        .col-producto { width: 10%; }
        .col-codigo { width: 6%; }
        .col-medidas { width: 5%; }
        .col-cajas { width: 4%; }
        .col-cantidad { width: 5%; }
        .col-almacen { width: 7%; }
        .col-referencia { width: 6%; }
        .col-tipo-ref { width: 5%; }
        .col-destino { width: 7%; }
        .col-conductor { width: 7%; }
        .col-cedula { width: 6%; }
        .col-placa { width: 5%; }
        .col-observacion { width: 7%; }
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
    <div class="title">Trazabilidad de Productos</div>
    <div class="subtitle">Fecha: {{ date('d/m/Y H:i') }}</div>
    @if($selectedProductId)
        <div class="subtitle">Producto: {{ $products->where('id', $selectedProductId)->first()->nombre ?? '' }}</div>
    @else
        <div class="subtitle">Producto: Todos los productos</div>
    @endif
    @if($selectedWarehouseId)
        <div class="subtitle">Bodega: {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}</div>
    @else
        <div class="subtitle">Bodega: Todos los bodegas</div>
    @endif
    @if($dateFrom || $dateTo)
        <div class="subtitle">
            Rango: {{ $dateFrom ? date('d/m/Y', strtotime($dateFrom)) : 'Inicio' }} - 
            {{ $dateTo ? date('d/m/Y', strtotime($dateTo)) : 'Fin' }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th class="col-fecha">Fecha</th>
                <th class="col-tipo">Tipo</th>
                <th class="col-producto">Producto</th>
                <th class="col-codigo">Código</th>
                <th class="col-medidas">Medidas</th>
                <th class="col-cajas">Cajas</th>
                <th class="col-cantidad">Cantidad</th>
                <th class="col-almacen">Bodega</th>
                <th class="col-referencia">Referencia</th>
                <th class="col-tipo-ref">Tipo Ref.</th>
                <th class="col-destino">Destino</th>
                <th class="col-conductor">Conductor</th>
                <th class="col-cedula">Cédula</th>
                <th class="col-placa">Placa</th>
                <th class="col-observacion">Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td class="col-fecha">{{ $movement['date']->format('d/m/Y H:i') }}</td>
                <td class="col-tipo" style="text-align: center;">
                    @if($movement['type'] === 'entrada')
                        <span class="badge-entrada">{{ $movement['type_label'] }}</span>
                    @else
                        <span class="badge-salida">{{ $movement['type_label'] }}</span>
                    @endif
                </td>
                <td class="col-producto">{{ $movement['product_name'] }}</td>
                <td class="col-codigo">{{ $movement['product_code'] }}</td>
                <td class="col-medidas">{{ $movement['product_medidas'] ?? '-' }}</td>
                <td class="col-cajas" style="text-align: center;">
                    @if(isset($movement['boxes']) && $movement['boxes'] !== null)
                        {{ number_format($movement['boxes'], 0) }}
                    @else
                        -
                    @endif
                </td>
                <td class="col-cantidad {{ $movement['quantity'] > 0 ? 'quantity-positive' : 'quantity-negative' }}" style="text-align: center;">
                    {{ $movement['quantity'] > 0 ? '+' : '' }}{{ number_format($movement['quantity'], 0) }}
                </td>
                <td class="col-almacen">{{ $movement['warehouse_name'] }}</td>
                <td class="col-referencia">{{ $movement['reference'] }}</td>
                <td class="col-tipo-ref">{{ $movement['reference_type'] }}</td>
                <td class="col-destino">{{ $movement['destination_warehouse'] ?? '-' }}</td>
                <td class="col-conductor">{{ $movement['driver_name'] ?? '-' }}</td>
                <td class="col-cedula">{{ $movement['driver_identity'] ?? '-' }}</td>
                <td class="col-placa">{{ $movement['driver_vehicle_plate'] ?? '-' }}</td>
                <td class="col-observacion">{{ $movement['note'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por EasyInventory - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>

