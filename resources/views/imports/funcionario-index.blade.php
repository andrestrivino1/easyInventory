@extends('layouts.app')
@section('content')
<style>
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        padding: 25px;
        margin: 20px auto;
        max-width: 1400px;
    }
    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
    }
    .table-header h2 {
        margin: 0;
        color: #222;
        font-size: 24px;
        font-weight: 700;
    }
    .search-box {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 6px;
        width: 300px;
        font-size: 14px;
    }
    .table-wrapper {
        overflow-x: auto;
        width: 100%;
        -webkit-overflow-scrolling: touch;
        margin-top: 20px;
    }
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .table-wrapper::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    table {
        width: 100%;
        min-width: 1800px;
        border-collapse: collapse;
    }
    thead {
        background: #4a8af4;
        color: white;
    }
    th {
        padding: 10px 8px;
        text-align: left;
        font-weight: 600;
        font-size: 11px;
        white-space: nowrap;
    }
    td {
        padding: 10px 8px;
        border-bottom: 1px solid #e0e0e0;
        font-size: 11px;
    }
    th:nth-child(1),
    td:nth-child(1) {
        min-width: 90px;
        max-width: 90px;
    }
    th:nth-child(2),
    td:nth-child(2) {
        min-width: 120px;
        max-width: 120px;
    }
    th:nth-child(3),
    td:nth-child(3),
    th:nth-child(4),
    td:nth-child(4),
    th:nth-child(5),
    td:nth-child(5) {
        min-width: 110px;
        max-width: 110px;
    }
    th:nth-child(6),
    td:nth-child(6),
    th:nth-child(7),
    td:nth-child(7) {
        min-width: 100px;
        max-width: 100px;
    }
    th:nth-child(8),
    td:nth-child(8),
    th:nth-child(9),
    td:nth-child(9),
    th:nth-child(10),
    td:nth-child(10) {
        min-width: 100px;
        max-width: 100px;
    }
    th:nth-child(11),
    td:nth-child(11) {
        min-width: 120px;
        max-width: 120px;
    }
    th:nth-child(12),
    td:nth-child(12) {
        min-width: 80px;
        max-width: 80px;
    }
    th:nth-child(13),
    td:nth-child(13) {
        min-width: 200px;
        max-width: 200px;
    }
    th:nth-child(14),
    td:nth-child(14) {
        min-width: 120px;
        max-width: 120px;
    }
    tbody tr:hover {
        background: #f5f5f5;
    }
    .row-nationalized {
        background-color: #d4edda !important;
    }
    .row-nationalized:hover {
        background-color: #c3e6cb !important;
    }
    .badge-nationalized {
        background: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        display: inline-block;
        margin-top: 4px;
    }
    .badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }
    .badge-pending {
        background: #ffc107;
        color: #000;
    }
    .badge-completed {
        background: #28a745;
        color: white;
    }
    .badge-received {
        background: #17a2b8;
        color: white;
    }
    .file-link {
        display: inline-block;
        margin: 2px 4px;
        padding: 4px 8px;
        background: #e3f2fd;
        color: #1565c0;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
    }
    .file-link:hover {
        background: #bbdefb;
    }
    .btn-update-arrival {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
    }
    .btn-update-arrival:hover {
        background: #218838;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .modal-content {
        background-color: white;
        margin: 10% auto;
        padding: 25px;
        border-radius: 12px;
        width: 500px;
        max-width: 90%;
    }
    .modal-header {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
    }
    .modal-header h3 {
        margin: 0;
        color: #222;
    }
    .form-group {
        margin-bottom: 18px;
    }
    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
        color: #333;
    }
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
    }
    .modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }
    .btn-cancel {
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-save {
        background: #4a8af4;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
    }
    .progress-container {
        margin: 8px 0;
    }
    .progress-bar-wrapper {
        background: #e9ecef;
        border-radius: 8px;
        height: 16px;
        overflow: hidden;
        position: relative;
        margin-top: 4px;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        border-radius: 8px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 9px;
        font-weight: 600;
    }
</style>

<div class="table-container">
    <div class="table-header">
        <h2>Importaciones - Vista Funcionario</h2>
    </div>
    
    <!-- Filtros y Búsqueda -->
    <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
        <form method="GET" action="{{ route('imports.funcionario-index') }}" id="filter-form" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
            <!-- Campo de búsqueda -->
            <div style="flex: 1; min-width: 250px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Buscar</label>
                <div style="position: relative;">
                    <input type="text" name="search" id="searchInput" class="form-control" value="{{ request('search') }}" placeholder="Buscar importaciones..." style="padding-left: 40px; border-radius: 6px; border: 2px solid #e0e0e0; width: 100%;">
                    <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
                </div>
            </div>
            
            <!-- Filtro de Fechas -->
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Fecha Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" style="width: 100%; padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0;">
            </div>
            
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Fecha Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" style="width: 100%; padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0;">
            </div>
            
            <!-- Filtro de Porcentaje -->
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Porcentaje Mínimo</label>
                <input type="number" name="progress_min" value="{{ request('progress_min') }}" min="0" max="100" placeholder="Ej: 90" style="width: 100%; padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0;">
            </div>
            
            <!-- Filtro de Créditos -->
            <div style="flex: 1; min-width: 150px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Tiempo de Crédito</label>
                <select name="credit_time" style="width: 100%; padding: 8px; border-radius: 6px; border: 2px solid #e0e0e0;">
                    <option value="">Todos</option>
                    <option value="sin_credito" {{ request('credit_time') == 'sin_credito' ? 'selected' : '' }}>Sin crédito</option>
                    <option value="15" {{ request('credit_time') == '15' ? 'selected' : '' }}>15 días</option>
                    <option value="30" {{ request('credit_time') == '30' ? 'selected' : '' }}>30 días</option>
                    <option value="45" {{ request('credit_time') == '45' ? 'selected' : '' }}>45 días</option>
                </select>
            </div>
            
            <!-- Botones -->
            <div style="display: flex; gap: 10px;">
                <button type="submit" style="background: #0066cc; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; white-space: nowrap;">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="{{ route('imports.funcionario-index') }}" style="background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center;">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
    <table id="importsTable">
        <thead>
            <tr>
                <th>DO</th>
                <th>Usuario</th>
                <th>N° Comercial Invoice</th>
                <th>N° Proforma Invoice</th>
                <th>N° Bill of Lading</th>
                <th>Origen</th>
                <th>Destino</th>
                    <th>Fecha Salida</th>
                    <th>Fecha Llegada</th>
                    <th>Fecha Creación</th>
                    <th>Naviera/Agencia</th>
                <th>Días Libres Destino</th>
                <th>Estado</th>
                <th>Archivos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($imports as $import)
                @php
                    $departureDate = \Carbon\Carbon::parse($import->departure_date);
                    $arrivalDate = $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date) : null;
                    $today = \Carbon\Carbon::now();
                    $progress = 0;
                    $progressText = '0%';
                    
                    if ($arrivalDate) {
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
            <tr class="{{ $import->nationalized ? 'row-nationalized' : '' }}">
                <td><strong>{{ $import->do_code }}</strong></td>
                <td style="word-break: break-word;">
                    @php
                        $userName = $import->user->nombre_completo ?? $import->user->email;
                        echo strlen($userName) > 20 ? substr($userName, 0, 17) . '...' : $userName;
                    @endphp
                </td>
                <td style="word-break: break-word;">
                    @php
                        $invoice = $import->commercial_invoice_number ?? $import->product_name ?? '-';
                        echo strlen($invoice) > 15 ? substr($invoice, 0, 12) . '...' : $invoice;
                    @endphp
                </td>
                <td style="word-break: break-word;">
                    @php
                        $proforma = $import->proforma_invoice_number ?? '-';
                        echo strlen($proforma) > 15 ? substr($proforma, 0, 12) . '...' : $proforma;
                    @endphp
                </td>
                <td style="word-break: break-word;">
                    @php
                        $bl = $import->bl_number ?? '-';
                        echo strlen($bl) > 15 ? substr($bl, 0, 12) . '...' : $bl;
                    @endphp
                </td>
                <td>{{ $import->origin ?? '-' }}</td>
                <td>{{ $import->destination ?? 'Colombia' }}</td>
                <td style="white-space: nowrap;">{{ $import->departure_date ? $departureDate->format('d/m/Y') : '-' }}</td>
                <td style="white-space: nowrap;">{{ $import->arrival_date ? $arrivalDate->format('d/m/Y') : '-' }}</td>
                <td style="white-space: nowrap; font-size: 10px;">{{ $import->created_at ? $import->created_at->format('d/m/Y H:i') : '-' }}</td>
                <td style="word-break: break-word;">
                    @php
                        $naviera = $import->shipping_company ?? '-';
                        echo strlen($naviera) > 15 ? substr($naviera, 0, 12) . '...' : $naviera;
                    @endphp
                </td>
                <td>{{ $import->free_days_at_dest ?? '-' }}</td>
                <td>
                    <div>
                        @if($import->nationalized)
                            {{-- Si está nacionalizada, siempre mostrar como completado --}}
                            <span class="badge badge-completed">Completado</span>
                            <span class="badge-nationalized">
                                <i class="bi bi-check-circle"></i> Nacionalizada
                            </span>
                        @elseif($import->status == 'pending')
                            <span class="badge badge-pending">Pendiente</span>
                        @elseif($import->status == 'completed')
                            <span class="badge badge-completed">Completado</span>
                        @elseif($import->status == 'recibido')
                            <span class="badge badge-received">Recibido</span>
                        @elseif($import->status == 'pendiente_por_confirmar')
                            <span class="badge" style="background: #ff9800; color: white;">Pendiente por confirmar</span>
                        @else
                            <span class="badge">{{ $import->status }}</span>
                        @endif
                    </div>
                    @if($arrivalDate)
                    <div class="progress-container">
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill" style="width: {{ min($progress, 100) }}%;">
                                {{ $progressText }}
                            </div>
                        </div>
                    </div>
                    @endif
                </td>
                <td>
                    <div class="files-container" style="display: flex; flex-direction: column; gap: 8px; max-width: 100%;">
                        @php
                            $hasContainers = $import->containers && $import->containers->count() > 0;
                            $hasDocuments = $import->proforma_invoice_low_pdf || 
                                         $import->commercial_invoice_low_pdf || 
                                         $import->packing_list_pdf || 
                                         $import->bl_pdf || 
                                         $import->apostillamiento_pdf ||
                                         $import->other_documents_pdf;
                        @endphp
                        
                        @if($hasContainers)
                            <div class="files-section">
                                <div class="files-section-title" style="font-size: 10px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Contenedores</div>
                                @foreach($import->containers as $container)
                                    <div class="container-files" style="background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 4px; padding: 4px 6px; margin-bottom: 4px;">
                                        <div class="container-ref" style="font-size: 10px; font-weight: 600; color: #495057; margin-bottom: 3px;">{{ $container->reference }}</div>
                                        <div class="files-grid" style="display: flex; flex-wrap: wrap; gap: 3px;">
                                            @if($container->pdf_path)
                                                <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="PDF con información del contenedor">
                                                    <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Info PDF
                                                </a>
                                            @endif
                                            @if($container->image_pdf_path)
                                                <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_image_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="PDF con imágenes del contenedor">
                                                    <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Imágenes PDF
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        @if($hasDocuments)
                            <div class="files-section documents-section" style="border-top: 1px solid #e0e0e0; padding-top: 6px; margin-top: 4px;">
                                <div class="files-section-title" style="font-size: 10px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">Documentos</div>
                                <div class="files-grid" style="display: flex; flex-wrap: wrap; gap: 3px;">
                                    @if($import->proforma_invoice_low_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'proforma_invoice_low_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Proforma Invoice Low">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Proforma Low
                                        </a>
                                    @endif
                                    @if($import->commercial_invoice_low_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'commercial_invoice_low_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Commercial Invoice Low">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Invoice Low
                                        </a>
                                    @endif
                                    @if($import->packing_list_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'packing_list_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Packing List">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Packing List
                                        </a>
                                    @endif
                                    @if($import->bl_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'bl_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Bill of Lading">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> BL
                                        </a>
                                    @endif
                                    @if($import->apostillamiento_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'apostillamiento_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Apostillamiento">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Apostillamiento
                                        </a>
                                    @endif
                                    @if($import->other_documents_pdf)
                                        <a href="{{ route('imports.view', [$import->id, 'other_documents_pdf']) }}" target="_blank" class="file-viewer" style="display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; background: #e3f2fd; color: #1565c0; border-radius: 4px; text-decoration: none; font-size: 10px; font-weight: 500;" title="Otros Documentos">
                                            <i class="bi bi-file-pdf" style="font-size: 11px;"></i> Otros
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                        
                        @if(!$hasContainers && !$hasDocuments)
                            <span style="color: #999; font-size: 11px;">Sin archivos</span>
                        @endif
                    </div>
                </td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        @if($import->nationalized)
                            {{-- Si está nacionalizada, no mostrar opciones de actualizar fecha --}}
                            <span style="color: #28a745; font-size: 12px; margin-bottom: 5px;">
                                <i class="bi bi-check-circle"></i> Nacionalizada
                            </span>
                        @elseif($import->status == 'pendiente_por_confirmar')
                            <button class="btn-update-estimated" onclick="openUpdateEstimatedModal({{ $import->id }}, '{{ $import->do_code }}', '{{ $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date)->format('Y-m-d') : '' }}')" style="background: #ff9800; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                <i class="bi bi-calendar-event me-1"></i>Actualizar Fecha Estimada
                            </button>
                            <button class="btn-update-arrival" onclick="openUpdateModal({{ $import->id }}, '{{ $import->do_code }}')">
                                Marcar Recibido
                            </button>
                        @elseif($import->status != 'recibido')
                            <button class="btn-update-arrival" onclick="openUpdateModal({{ $import->id }}, '{{ $import->do_code }}')">
                                Marcar Recibido
                            </button>
                        @else
                            <span style="color: #28a745; font-size: 12px; margin-bottom: 5px;">
                                Recibido: {{ $import->actual_arrival_date ? \Carbon\Carbon::parse($import->actual_arrival_date)->format('d/m/Y') : 'N/A' }}
                            </span>
                        @endif
                        <a href="{{ route('imports.report', $import->id) }}" class="btn-report" style="background: #4a8af4; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; text-align: center; display: inline-block;" target="_blank">
                            <i class="bi bi-file-pdf me-1"></i>Reporte PDF
                        </a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="14" style="text-align: center; padding: 30px; color: #666;">
                    No hay importaciones registradas
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    
    <!-- Paginación -->
    @if(method_exists($imports, 'links'))
    <div style="margin-top: 20px; display: flex; justify-content: center;">
        {{ $imports->appends(request()->query())->links() }}
    </div>
    @endif
    </div>
</div>

<!-- Modal para actualizar fecha de llegada -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Actualizar Fecha de Llegada</h3>
        </div>
        <form id="updateArrivalForm" method="POST">
            @csrf
            <div class="form-group">
                <label for="actual_arrival_date">Fecha Real de Llegada *</label>
                <input type="date" name="actual_arrival_date" id="actual_arrival_date" required />
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeUpdateModal()">Cancelar</button>
                <button type="submit" class="btn-save">Marcar como Recibido</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para actualizar fecha estimada de llegada -->
<div id="updateEstimatedModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Actualizar Fecha Estimada de Llegada</h3>
        </div>
        <form id="updateEstimatedArrivalForm" method="POST" action="">
            @csrf
            <div class="form-group">
                <label for="estimated_arrival_date">Nueva Fecha Estimada de Llegada *</label>
                <input type="date" name="arrival_date" id="estimated_arrival_date" required />
                <small style="color: #666; font-size: 11px; display: block; margin-top: 5px;">
                    Al actualizar la fecha estimada, la importación volverá al estado "Pendiente".
                </small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeUpdateEstimatedModal()">Cancelar</button>
                <button type="submit" class="btn-save" style="background: #ff9800;">Actualizar Fecha</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Mostrar mensaje informativo sobre PDFs omitidos si existe
document.addEventListener('DOMContentLoaded', function() {
    @if(session('pdfs_omitted_info'))
        @php
            $omittedInfo = session('pdfs_omitted_info');
            $omittedList = is_array($omittedInfo['omitted_list']) ? $omittedInfo['omitted_list'] : [];
        @endphp
        Swal.fire({
            icon: 'warning',
            title: 'PDFs Omitidos en el Reporte',
            html: `
                <div style="text-align: left;">
                    <p><strong>DO:</strong> {{ $omittedInfo['do_code'] ?? 'N/A' }}</p>
                    <p><strong>Total de PDFs:</strong> {{ $omittedInfo['total_pdfs'] ?? 0 }}</p>
                    <p><strong>PDFs incluidos:</strong> {{ $omittedInfo['included_pdfs'] ?? 0 }} (1 reporte + {{ ($omittedInfo['included_pdfs'] ?? 1) - 1 }} PDFs adjuntos)</p>
                    <p><strong>PDFs omitidos:</strong> <span style="color: #dc3545; font-weight: bold;">{{ $omittedInfo['omitted_pdfs'] ?? 0 }}</span></p>
                    <p style="margin-top: 10px;"><strong>Razón:</strong> {{ $omittedInfo['reason'] ?? 'Compresión no soportada' }}</p>
                    @if(!empty($omittedList))
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                        <strong>PDFs que no pudieron incluirse:</strong>
                        <ul style="margin: 8px 0 0 20px; padding: 0;">
                            @foreach(array_slice($omittedList, 0, 10) as $omittedPdf)
                                <li style="margin: 4px 0;">{{ $omittedPdf }}</li>
                            @endforeach
                            @if(count($omittedList) > 10)
                                <li style="margin: 4px 0; color: #666;">... y {{ count($omittedList) - 10 }} más</li>
                            @endif
                        </ul>
                    </div>
                    @endif
                    <p style="margin-top: 15px; font-size: 12px; color: #666;">
                        <strong>Nota:</strong> Algunos PDFs no pudieron ser incluidos porque utilizan una técnica de compresión no soportada por la versión gratuita de la librería FPDI. 
                        Para incluir estos PDFs, considere convertirlos a un formato compatible o usar una librería de pago.
                    </p>
                </div>
            `,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#4a8af4',
            width: '600px',
            allowOutsideClick: false
        }).then(() => {
            // Limpiar la información después de que el usuario confirme el mensaje
            fetch('{{ route("imports.clear-omitted-info") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).catch(error => console.error('Error al limpiar información:', error));
        });
    @endif
});

// Búsqueda en tiempo real (solo si no hay filtros aplicados)
const searchInput = document.getElementById('searchInput');
const table = document.getElementById('importsTable');
const filterForm = document.getElementById('filter-form');

// Si hay filtros en la URL, desactivar búsqueda en tiempo real
const urlParams = new URLSearchParams(window.location.search);
const hasFilters = urlParams.has('date_from') || urlParams.has('date_to') || 
                   urlParams.has('progress_min') || 
                   urlParams.has('credit_time');

if (searchInput && table && !hasFilters) {
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

// Si hay filtros, el formulario se enviará al hacer búsqueda
if (hasFilters && searchInput) {
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            filterForm.submit();
        }
    });
}

// Modal functions
function openUpdateModal(importId, doCode) {
    // Usar la función route de Laravel para generar la URL correctamente
    // Generar la URL usando route con un ID temporal (0) y luego reemplazarlo
    const routeTemplate = '{{ route("imports.update-arrival", 0) }}';
    // Reemplazar el ID temporal (0) con el ID real, usando una expresión regular para asegurar que solo reemplace el ID en la ruta
    const url = routeTemplate.replace(/\/0(\/update-arrival)/, '/' + importId + '$1');
    document.getElementById('updateArrivalForm').action = url;
    document.getElementById('updateModal').style.display = 'block';
    document.getElementById('actual_arrival_date').value = new Date().toISOString().split('T')[0];
}

function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}

// Modal functions for estimated arrival date
function openUpdateEstimatedModal(importId, doCode, currentDate) {
    // Usar la función route de Laravel para generar la URL correctamente
    // Generar la URL usando route con un ID temporal (0) y luego reemplazarlo
    const routeTemplate = '{{ route("imports.update-estimated-arrival", 0) }}';
    // Reemplazar el ID temporal (0) con el ID real, usando una expresión regular para asegurar que solo reemplace el ID en la ruta
    const url = routeTemplate.replace(/\/0(\/update-estimated-arrival)/, '/' + importId + '$1');
    document.getElementById('updateEstimatedArrivalForm').action = url;
    document.getElementById('updateEstimatedModal').style.display = 'block';
    if (currentDate) {
        document.getElementById('estimated_arrival_date').value = currentDate;
    } else {
        // Si no hay fecha actual, usar la fecha de hoy
        document.getElementById('estimated_arrival_date').value = new Date().toISOString().split('T')[0];
    }
}

function closeUpdateEstimatedModal() {
    document.getElementById('updateEstimatedModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('updateModal');
    const estimatedModal = document.getElementById('updateEstimatedModal');
    if (event.target == modal) {
        closeUpdateModal();
    }
    if (event.target == estimatedModal) {
        closeUpdateEstimatedModal();
    }
}

// Manejar envío del formulario
document.getElementById('updateArrivalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = this.action;
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Fecha de llegada actualizada correctamente',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            closeUpdateModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al actualizar la fecha',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al procesar la solicitud',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    });
});
</script>
@endsection
