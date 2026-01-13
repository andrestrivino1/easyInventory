@extends('layouts.app')

@section('content')
<style>
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
    .imports-table {
        width: 100%;
        min-width: 1800px;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        font-size: 12px;
    }
    .imports-table th {
        background: #0066cc;
        color: white;
        text-align: left;
        padding: 10px 8px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }
    .imports-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #ebebeb;
        vertical-align: middle;
        font-size: 11px;
    }
    .imports-table th:nth-child(1),
    .imports-table td:nth-child(1) {
        min-width: 90px;
        max-width: 90px;
    }
    .imports-table th:nth-child(2),
    .imports-table td:nth-child(2) {
        min-width: 120px;
        max-width: 120px;
    }
    .imports-table th:nth-child(3),
    .imports-table td:nth-child(3),
    .imports-table th:nth-child(4),
    .imports-table td:nth-child(4),
    .imports-table th:nth-child(5),
    .imports-table td:nth-child(5) {
        min-width: 110px;
        max-width: 110px;
    }
    .imports-table th:nth-child(6),
    .imports-table td:nth-child(6),
    .imports-table th:nth-child(7),
    .imports-table td:nth-child(7) {
        min-width: 100px;
        max-width: 100px;
    }
    .imports-table th:nth-child(8),
    .imports-table td:nth-child(8),
    .imports-table th:nth-child(9),
    .imports-table td:nth-child(9),
    .imports-table th:nth-child(10),
    .imports-table td:nth-child(10) {
        min-width: 100px;
        max-width: 100px;
    }
    .imports-table th:nth-child(11),
    .imports-table td:nth-child(11) {
        min-width: 120px;
        max-width: 120px;
    }
    .imports-table th:nth-child(12),
    .imports-table td:nth-child(12) {
        min-width: 80px;
        max-width: 80px;
    }
    .imports-table th:nth-child(13),
    .imports-table td:nth-child(13) {
        min-width: 100px;
        max-width: 100px;
    }
    .imports-table th:nth-child(14),
    .imports-table td:nth-child(14) {
        min-width: 120px;
        max-width: 120px;
    }
    .imports-table th:nth-child(15),
    .imports-table td:nth-child(15) {
        min-width: 200px;
        max-width: 200px;
    }
    .imports-table th:nth-child(16),
    .imports-table td:nth-child(16) {
        min-width: 120px;
        max-width: 120px;
    }
    .imports-table tbody tr:last-child td {
        border-bottom: none;
    }
    .imports-table tr:hover {
        background: #f2f8ff;
    }
    .actions {
        white-space: nowrap;
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .actions button,
    .actions a {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        transition: opacity 0.2s;
        white-space: nowrap;
    }
    .actions button:hover,
    .actions a:hover {
        opacity: 0.9;
    }
    .actions form {
        display: inline-flex;
        margin: 0;
    }
    .btn-download {
        background: #198754;
        color: white;
    }
    .btn-report {
        background: #0d6efd;
        color: white;
    }
    .btn-delete {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-delete:hover {
        background: #c82333;
    }
    .status-badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 500;
        display: inline-block;
    }
    .status-pending {
        background: #ffc107;
        color: #212529;
    }
    .row-nationalized {
        background-color: #d4edda !important;
    }
    .row-nationalized:hover {
        background-color: #c3e6cb !important;
    }
    .btn-nationalize {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-nationalize:hover {
        background: #218838;
        opacity: 0.9;
    }
    .btn-nationalized {
        background: #6c757d;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        cursor: default;
    }
    .status-completed {
        background: #4caf50;
        color: white;
    }
    .status-in-transit {
        background: #0dcaf0;
        color: #212529;
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
    .file-viewer {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        padding: 2px 6px;
        background: #e3f2fd;
        color: #1565c0;
        border-radius: 3px;
        text-decoration: none;
        font-size: 9px;
        font-weight: 500;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .file-viewer:hover {
        background: #bbdefb;
        color: #0d47a1;
    }
    .file-viewer i {
        font-size: 11px;
    }
    .files-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        max-width: 100%;
    }
    .files-section {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .files-section-title {
        font-size: 10px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .files-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
    }
    .container-files {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 4px 6px;
        margin-bottom: 4px;
    }
    .container-ref {
        font-size: 10px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
    }
    .documents-section {
        border-top: 1px solid #e0e0e0;
        padding-top: 6px;
        margin-top: 4px;
    }
</style>

<div class="container-fluid" style="padding-top:32px; padding-bottom:40px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1400px;">
        @if(session('success') || session('error') || session('warning') || session('pdfs_omitted_info'))
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: '{{ session('success') }}',
                    timer: 3300,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            @endif
            @if(session('error'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'error',
                    title: '{{ session('error') }}',
                    timer: 3800,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            @endif
            @if(session('warning'))
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: '{{ session('warning') }}',
                    timer: 3500,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            @endif
            @if(session('pdfs_omitted_info'))
                @php
                    $omittedInfo = session('pdfs_omitted_info');
                    $omittedList = is_array($omittedInfo['omitted_list']) ? $omittedInfo['omitted_list'] : [];
                    $omittedListText = implode(', ', array_slice($omittedList, 0, 5));
                    if (count($omittedList) > 5) {
                        $omittedListText .= ' y ' . (count($omittedList) - 5) . ' más';
                    }
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
        </script>
        @endif

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
            <h2 class="mb-0" style="color:#333;font-weight:bold; flex: 1; min-width: 200px;">Gestión de Importaciones</h2>
            
            <!-- Botones para descargar (solo admin) -->
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="{{ route('imports.export-excel', request()->query()) }}" class="btn btn-primary" style="background: #0d6efd; border-color: #0d6efd; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;" onmouseover="this.style.background='#0b5ed7'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#0d6efd'; this.style.transform='translateY(0)';">
                    <i class="bi bi-file-earmark-excel" style="font-size: 18px;"></i>
                    <span>Descargar Excel</span>
                </a>
                <a href="{{ route('imports.export-all-reports') }}" class="btn btn-primary" style="background: #198754; border-color: #198754; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s;" onmouseover="this.style.background='#157347'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#198754'; this.style.transform='translateY(0)';">
                    <i class="bi bi-file-earmark-pdf" style="font-size: 18px;"></i>
                    <span>Descargar Informe General (Todos los DO)</span>
                </a>
            </div>
        </div>
        
        <!-- Filtros y Búsqueda -->
        <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 24px;">
            <form method="GET" action="{{ route('imports.index') }}" id="filter-form" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <!-- Campo de búsqueda -->
                <div style="flex: 1; min-width: 250px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px;">Buscar</label>
                    <div style="position: relative;">
                        <input type="text" name="search" id="search-imports" class="form-control" value="{{ request('search') }}" placeholder="Buscar importaciones..." style="padding-left: 40px; border-radius: 6px; border: 2px solid #e0e0e0; width: 100%;">
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
                    <a href="{{ route('imports.index') }}" style="background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center;">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <div class="table-wrapper">
        <table class="imports-table" id="imports-table">
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
                    <th>Créditos</th>
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
                    <td style="text-align: center;">{{ $import->free_days_at_dest ?? '-' }}</td>
                    <td>
                        <div>
                            @if($import->status === 'pending')
                                <span class="status-badge status-pending">Pendiente</span>
                            @elseif($import->status === 'completed')
                                <span class="status-badge status-completed">Completado</span>
                            @elseif($import->status === 'in_transit')
                                <span class="status-badge status-in-transit">En tránsito</span>
                            @elseif($import->status === 'recibido')
                                <span class="status-badge" style="background: #17a2b8; color: white;">Recibido</span>
                            @else
                                <span class="status-badge">{{ ucfirst($import->status) }}</span>
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
                        @if($import->credit_time)
                            <strong style="color: #198754;">{{ $import->credit_time }} {{ __('common.dias') }}</strong>
                            @php
                                $creditExpiration = $import->getCreditExpirationDate();
                                $daysUntilExpiration = $import->getDaysUntilCreditExpiration();
                                $isExpired = $import->isCreditExpired();
                                $isExpiringSoon = $import->isCreditExpiringSoon();
                            @endphp
                            @if($creditExpiration)
                                <div style="margin-top: 4px; font-size: 11px;">
                                    @if($isExpired)
                                        <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 4px; display: inline-block; font-weight: 600;">
                                            ⚠️ {{ __('common.credito_vencido') }} ({{ abs($daysUntilExpiration) }} {{ __('common.dias') }})
                                        </span>
                                    @elseif($isExpiringSoon)
                                        <span style="background: #ffc107; color: #212529; padding: 2px 8px; border-radius: 4px; display: inline-block; font-weight: 600;">
                                            ⚠️ {{ __('common.credito_por_vencer') }} ({{ $daysUntilExpiration }} {{ __('common.dias') }})
                                        </span>
                                    @else
                                        <span style="color: #666; font-size: 10px;">
                                            {{ __('common.vencimiento') }}: {{ $creditExpiration->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        @else
                            <span style="color: #999; font-style: italic;">{{ __('common.sin_credito') }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="files-container">
                            @php
                                $hasContainers = $import->containers && $import->containers->count() > 0;
                                $hasDocuments = $import->proforma_pdf || $import->proforma_invoice_low_pdf || 
                                             $import->invoice_pdf || $import->commercial_invoice_low_pdf || 
                                             $import->packing_list_pdf || $import->bl_pdf || $import->apostillamiento_pdf ||
                                             $import->other_documents_pdf;
                            @endphp
                            
                            @if($hasContainers)
                                <div class="files-section">
                                    <div class="files-section-title">Contenedores</div>
                                    @foreach($import->containers as $container)
                                        <div class="container-files">
                                            <div class="container-ref">{{ $container->reference }}</div>
                                            <div class="files-grid">
                                                @if($container->pdf_path)
                                                    <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_pdf']) }}" target="_blank" class="file-viewer" title="PDF con información del contenedor">
                                                        <i class="bi bi-file-pdf"></i> Info PDF
                                                    </a>
                                                @endif
                                                @if($container->image_pdf_path)
                                                    <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_image_pdf']) }}" target="_blank" class="file-viewer" title="PDF con imágenes del contenedor">
                                                        <i class="bi bi-file-pdf"></i> Imágenes PDF
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if($hasDocuments)
                                <div class="files-section documents-section">
                                    <div class="files-section-title">Documentos</div>
                                    <div class="files-grid">
                                        @if($import->proforma_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'proforma_pdf']) }}" target="_blank" class="file-viewer" title="Proforma Invoice">
                                                <i class="bi bi-file-pdf"></i> Proforma
                                            </a>
                                        @endif
                                        @if($import->proforma_invoice_low_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'proforma_invoice_low_pdf']) }}" target="_blank" class="file-viewer" title="Proforma Invoice Low">
                                                <i class="bi bi-file-pdf"></i> Proforma Low
                                            </a>
                                        @endif
                                        @if($import->invoice_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'invoice_pdf']) }}" target="_blank" class="file-viewer" title="Commercial Invoice">
                                                <i class="bi bi-file-pdf"></i> Invoice
                                            </a>
                                        @endif
                                        @if($import->commercial_invoice_low_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'commercial_invoice_low_pdf']) }}" target="_blank" class="file-viewer" title="Commercial Invoice Low">
                                                <i class="bi bi-file-pdf"></i> Invoice Low
                                            </a>
                                        @endif
                                        @if($import->packing_list_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'packing_list_pdf']) }}" target="_blank" class="file-viewer" title="Packing List">
                                                <i class="bi bi-file-pdf"></i> Packing List
                                            </a>
                                        @endif
                                        @if($import->bl_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'bl_pdf']) }}" target="_blank" class="file-viewer" title="Bill of Lading">
                                                <i class="bi bi-file-pdf"></i> BL
                                            </a>
                                        @endif
                                        @if($import->apostillamiento_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'apostillamiento_pdf']) }}" target="_blank" class="file-viewer" title="Apostillamiento">
                                                <i class="bi bi-file-pdf"></i> Apostillamiento
                                            </a>
                                        @endif
                                        @if($import->other_documents_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'other_documents_pdf']) }}" target="_blank" class="file-viewer" title="Otros Documentos">
                                                <i class="bi bi-file-pdf"></i> Otros
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
                    <td class="actions">
                        @if($import->status === 'completed' && $progress >= 100)
                            @if($import->nationalized)
                                <button class="btn-nationalized" disabled>
                                    <i class="bi bi-check-circle"></i> Nacionalizada
                                </button>
                            @else
                                <form action="{{ route('imports.nationalize', $import->id) }}" method="POST" style="display: inline-flex; margin: 0;">
                                    @csrf
                                    <button type="submit" class="btn-nationalize" onclick="return confirm('¿Deseas marcar esta importación como nacionalizada?');">
                                        <i class="bi bi-check-circle"></i> Nacionalizar
                                    </button>
                                </form>
                            @endif
                        @endif
                        <a href="{{ route('imports.report', $import->id) }}" class="btn-report" target="_blank">
                            <i class="bi bi-file-pdf me-1"></i>Reporte
                        </a>
                        <form action="{{ route('imports.destroy', $import->id) }}" method="POST" class="delete-form" data-do-code="{{ $import->do_code }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">
                                <i class="bi bi-trash me-1"></i>Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center text-muted py-5">
                        <i class="bi bi-upload text-secondary" style="font-size:2.2em;"></i><br>
                        <div class="mt-2">No hay importaciones registradas.</div>
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
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda en tiempo real (solo si no hay filtros aplicados)
    const searchInput = document.getElementById('search-imports');
    const table = document.getElementById('imports-table');
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

    // Confirmación para eliminar importaciones
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const doCode = form.getAttribute('data-do-code');
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar la importación ${doCode}? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
});
</script>
