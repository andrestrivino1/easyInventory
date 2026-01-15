<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Importación {{ $import->do_code }}</title>
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
        .do-code {
            font-size: 24px;
            color: #0066cc;
            font-weight: bold;
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
        .containers-section {
            margin: 20px 0;
        }
        .containers-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .containers-table th {
            background: #0066cc;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            border: 1px solid #004499;
        }
        .containers-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .containers-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        .status-completed {
            background: #4caf50;
            color: white;
        }
        .status-in-transit {
            background: #0dcaf0;
            color: #212529;
        }
        .documents-section {
            margin: 20px 0;
        }
        .documents-list {
            list-style: none;
            padding: 0;
        }
        .documents-list li {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">VIDRIOS J&P SAS</div>
            <div>Reporte de Importación</div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 10px; color: #666;">Fecha de generación:</div>
            <div style="font-weight: bold;">{{ date('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="title-section">
        <div class="main-title">Reporte de Importación</div>
        <div class="do-code">{{ $import->do_code }}</div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Proveedor</div>
            <div class="info-value">{{ $import->user->nombre_completo ?? $import->user->email }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">N° Comercial Invoice</div>
            <div class="info-value">{{ $import->commercial_invoice_number ?? $import->product_name ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">N° Proforma Invoice</div>
            <div class="info-value">{{ $import->proforma_invoice_number ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">N° Bill of Lading</div>
            <div class="info-value">{{ $import->bl_number ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Origen</div>
            <div class="info-value">{{ $import->origin ?? '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Destino</div>
            <div class="info-value">{{ $import->destination ?? 'Colombia' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Fecha de Salida</div>
            <div class="info-value">{{ $import->departure_date ? \Carbon\Carbon::parse($import->departure_date)->format('d/m/Y') : '-' }}</div>
        </div>
        <div class="info-box">
            <div class="info-label">Fecha Estimada de Llegada</div>
            <div class="info-value">{{ $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date)->format('d/m/Y') : '-' }}</div>
        </div>
        @if($import->actual_arrival_date)
        <div class="info-box">
            <div class="info-label">Fecha Real de Llegada</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($import->actual_arrival_date)->format('d/m/Y') }}</div>
        </div>
        @endif
        <div class="info-box">
            <div class="info-label">Estado</div>
            <div class="info-value">
                @if($import->status === 'pending')
                    <span class="status-badge status-pending">Pendiente</span>
                @elseif($import->status === 'completed')
                    <span class="status-badge status-completed">Completado</span>
                @elseif($import->status === 'in_transit')
                    <span class="status-badge status-in-transit">En tránsito</span>
                @elseif($import->status === 'recibido')
                    <span class="status-badge" style="background: #17a2b8; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-block;">Recibido</span>
                @elseif($import->status === 'pendiente_por_confirmar')
                    <span class="status-badge" style="background: #ff9800; color: white; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 500; display: inline-block;">Pendiente por confirmar</span>
                @else
                    {{ ucfirst($import->status) }}
                @endif
            </div>
        </div>
        <div class="info-box">
            <div class="info-label">Tiempo de Crédito</div>
            <div class="info-value">{{ $import->credit_time ? $import->credit_time . ' días' : '-' }}</div>
        </div>
        @if($import->supplier)
        <div class="info-box">
            <div class="info-label">Proveedor (Supplier)</div>
            <div class="info-value">{{ $import->supplier }}</div>
        </div>
        @endif
        @if($import->shipping_company)
        <div class="info-box">
            <div class="info-label">Naviera / Agente de Carga</div>
            <div class="info-value">{{ $import->shipping_company }}</div>
        </div>
        @endif
        @if($import->etd)
        <div class="info-box">
            <div class="info-label">ETD</div>
            <div class="info-value">{{ $import->etd }}</div>
        </div>
        @endif
        @if($import->free_days_at_dest)
        <div class="info-box">
            <div class="info-label">Días Libres en Destino</div>
            <div class="info-value">{{ $import->free_days_at_dest }} días</div>
        </div>
        @endif
    </div>

    <div class="footer">
        <div>Generado por: VIDRIOS J&P SAS</div>
    </div>
</body>
</html>

