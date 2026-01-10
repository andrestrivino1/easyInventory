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

    @if($import->containers && $import->containers->count() > 0)
    <div class="containers-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; color: #0066cc;">Contenedores</h3>
        <table class="containers-table">
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>PDF Información</th>
                    <th>PDF Imágenes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($import->containers as $container)
                <tr>
                    <td><strong>{{ $container->reference }}</strong></td>
                    <td>{{ $container->pdf_path ? 'Sí' : 'No' }}</td>
                    <td>{{ $container->image_pdf_path ? 'Sí' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="documents-section">
        <h3 style="font-size: 14px; margin-bottom: 10px; color: #0066cc;">Documentos Adjuntos</h3>
        <ul class="documents-list">
            @if($import->proforma_pdf)
                <li><strong>Proforma Invoice:</strong> Adjunto</li>
            @endif
            @if($import->proforma_invoice_low_pdf)
                <li><strong>Proforma Invoice Low:</strong> Adjunto</li>
            @endif
            @if($import->invoice_pdf)
                <li><strong>Commercial Invoice:</strong> Adjunto</li>
            @endif
            @if($import->commercial_invoice_low_pdf)
                <li><strong>Commercial Invoice Low:</strong> Adjunto</li>
            @endif
            @if($import->packing_list_pdf)
                <li><strong>Packing List:</strong> Adjunto</li>
            @endif
            @if($import->bl_pdf)
                <li><strong>BL (Bill of Lading):</strong> Adjunto</li>
            @endif
            @if($import->apostillamiento_pdf)
                <li><strong>Apostillamiento:</strong> Adjunto</li>
            @endif
            @if($import->other_documents_pdf)
                <li><strong>Otros Documentos:</strong> Adjunto</li>
            @endif
            @if($import->containers && $import->containers->count() > 0)
                @foreach($import->containers as $container)
                    @if($container->pdf_path)
                        <li><strong>Información Contenedor {{ $container->reference }}:</strong> Adjunto</li>
                    @endif
                    @if($container->image_pdf_path)
                        <li><strong>Imágenes Contenedor {{ $container->reference }}:</strong> Adjunto</li>
                    @endif
                @endforeach
            @endif
            @php
                $hasDocuments = $import->proforma_pdf || $import->proforma_invoice_low_pdf || 
                               $import->invoice_pdf || $import->commercial_invoice_low_pdf || 
                               $import->packing_list_pdf || $import->bl_pdf || $import->apostillamiento_pdf ||
                               $import->other_documents_pdf || 
                               ($import->containers && $import->containers->whereNotNull('pdf_path')->count() > 0);
            @endphp
            @if(!$hasDocuments)
                <li>No hay documentos adjuntos</li>
            @endif
        </ul>
        <div style="margin-top: 15px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0066cc; border-radius: 4px;">
            <strong style="color: #0066cc;">Nota:</strong> Todos los PDFs mencionados están disponibles en el sistema y pueden ser descargados desde la vista de importaciones.
        </div>
    </div>

    @php
        $departureDate = $import->departure_date ? \Carbon\Carbon::parse($import->departure_date) : null;
        $arrivalDate = $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date) : null;
        $today = \Carbon\Carbon::now();
        $progress = 0;
        $progressText = '0%';
        
        if ($arrivalDate && $departureDate) {
            $totalDays = $departureDate->diffInDays($arrivalDate);
            if ($totalDays > 0) {
                $elapsedDays = $departureDate->diffInDays($today);
                if ($elapsedDays < 0) {
                    $progress = 0;
                    $progressText = 'No iniciado';
                } elseif ($elapsedDays >= $totalDays) {
                    $progress = 100;
                    $progressText = '100% - Completado';
                } else {
                    $progress = ($elapsedDays / $totalDays) * 100;
                    $progressText = round($progress) . '%';
                }
            }
        }
    @endphp

    @if($arrivalDate)
    <div style="margin: 20px 0;">
        <h3 style="font-size: 14px; margin-bottom: 10px; color: #0066cc;">Progreso del Envío</h3>
        <div style="background: #e9ecef; border-radius: 10px; height: 25px; overflow: hidden; position: relative;">
            <div style="height: 100%; background: linear-gradient(90deg, #28a745 0%, #20c997 100%); border-radius: 10px; width: {{ min($progress, 100) }}%; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">
                {{ $progressText }}
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <div>Generado por: VIDRIOS J&P SAS</div>
    </div>
</body>
</html>

