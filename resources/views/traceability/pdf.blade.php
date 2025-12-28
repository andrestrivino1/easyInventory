<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Trazabilidad de Productos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #232323; margin: 0 32px; }
        .title { font-size: 20px; font-weight: bold; margin-top: 25px; margin-bottom: 8px; text-align: center; }
        .subtitle { color: #007bff; font-size: 12px; margin-bottom: 18px; text-align: center; }
        table {border-collapse: collapse;width:100%;margin-bottom:25px; page-break-inside: auto;}
        th, td {border: 1px solid #ccc;padding: 5px;text-align: left;font-size:10px;}
        th {background: #edf5ff; font-weight: bold;}
        tr { page-break-inside: avoid; page-break-after: auto; }
        .badge-entrada { background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
        .badge-salida { background: #dc3545; color: white; padding: 2px 6px; border-radius: 3px; font-size: 9px; }
        .quantity-positive { color: #28a745; font-weight: bold; }
        .quantity-negative { color: #dc3545; font-weight: bold; }
        .footer {margin-top:35px; text-align:right; font-size:13px;color:#777;}
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
        <div class="subtitle">Almacén: {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}</div>
    @else
        <div class="subtitle">Almacén: Todos los almacenes</div>
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
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Código</th>
                <th>Cajas</th>
                <th>Cantidad</th>
                <th>Almacén</th>
                <th>Referencia</th>
                <th>Tipo Ref.</th>
                <th>Destino</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movements as $movement)
            <tr>
                <td>{{ $movement['date']->format('d/m/Y H:i') }}</td>
                <td>
                    @if($movement['type'] === 'entrada')
                        <span class="badge-entrada">{{ $movement['type_label'] }}</span>
                    @else
                        <span class="badge-salida">{{ $movement['type_label'] }}</span>
                    @endif
                </td>
                <td>{{ $movement['product_name'] }}</td>
                <td>{{ $movement['product_code'] }}</td>
                <td style="text-align: center;">
                    @if(isset($movement['boxes']) && $movement['boxes'] !== null)
                        {{ number_format($movement['boxes'], 0) }}
                    @else
                        -
                    @endif
                </td>
                <td class="{{ $movement['quantity'] > 0 ? 'quantity-positive' : 'quantity-negative' }}">
                    {{ $movement['quantity'] > 0 ? '+' : '' }}{{ number_format($movement['quantity'], 0) }}
                </td>
                <td>{{ $movement['warehouse_name'] }}</td>
                <td>{{ $movement['reference'] }}</td>
                <td>{{ $movement['reference_type'] }}</td>
                <td>{{ $movement['destination_warehouse'] ?? '-' }}</td>
                <td>{{ $movement['note'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por EasyInventory - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>

