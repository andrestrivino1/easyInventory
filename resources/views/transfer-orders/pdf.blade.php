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
    <div class="no-print" style="text-align:right;margin-top:18px;">
        <a href="{{ route('transfer-orders.export', $transferOrder) }}" target="_blank" style="background:#178aff;color:white;padding:7px 15px;border-radius:5px;text-decoration:none;font-size:14px;font-weight:500;margin-bottom:24px;"><i class="bi bi-file-earmark-pdf" style="margin-right:4px;"></i>Descargar PDF</a>
        <button onclick="window.print()" style="background:#6c757d;color:white;padding:7px 16px;font-size:14px;font-weight:500;border:none;border-radius:5px;margin-left:6px;">Imprimir</button>
    </div>
    <div class="title">Orden de Transferencia #{{ $transferOrder->order_number }}</div>
    <div class="subtitle">Fecha: {{ $transferOrder->date->format('d/m/Y H:i') }}</div>

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
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transferOrder->products as $prod)
            <tr>
                <td>{{ $prod->nombre }}</td>
                <td>{{ $prod->pivot->quantity }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @if($transferOrder->note)
    <div style="margin-top:8px;"><span class="label">Notas:</span> {{ $transferOrder->note }}</div>
    @endif
    <div class="footer">
        Generado por EasyInventory - {{ now()->format('d/m/Y H:i') }}
    </div>
    <div class="firma">
        _______________________________<br>
        Responsable del traslado/recepción
    </div>
</body>
</html>
