@extends('layouts.app')

@section('content')
<style>
    .imports-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        font-size: 14px;
    }
    .imports-table th {
        background: #0066cc;
        color: white;
        text-align: left;
        padding: 16px 18px;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
    .imports-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #ebebeb;
        vertical-align: middle;
        font-size: 14px;
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
        gap: 6px;
        align-items: center;
    }
    .actions button,
    .actions a {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
        font-weight: 500;
        transition: opacity 0.2s;
    }
    .actions button:hover,
    .actions a:hover {
        opacity: 0.9;
    }
    .btn-edit {
        background: #198754;
        color: white;
    }
    .btn-download {
        background: #0d6efd;
        color: white;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        display: inline-block;
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
    .progress-container {
        margin: 8px 0;
    }
    .progress-bar-wrapper {
        background: #e9ecef;
        border-radius: 10px;
        height: 20px;
        overflow: hidden;
        position: relative;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
        border-radius: 10px;
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 11px;
        font-weight: 600;
    }
    .file-viewer {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 3px 8px;
        background: #e3f2fd;
        color: #1565c0;
        border-radius: 4px;
        text-decoration: none;
        font-size: 10px;
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
        @if(session('success') || session('error') || session('warning'))
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
        });
        </script>
        @endif

        <div class="d-flex justify-content-end align-items-center mb-3">
            <a href="{{ route('imports.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>{{ __('common.nueva_importacion') }}</a>
        </div>

        <h2 class="mb-4 text-center" style="color:#333;font-weight:bold">{{ __('common.mis_importaciones') }}</h2>
        
        <!-- Campo de búsqueda -->
        <div class="mb-4" style="max-width: 400px; margin: 0 auto 24px; text-align: center;">
            <div style="position: relative; width: 100%;">
                <input type="text" id="search-imports" class="form-control" placeholder="{{ __('common.buscar_importaciones') }}" style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
            </div>
        </div>
        
        <table class="imports-table" id="imports-table">
            <thead>
                <tr>
                    <th>{{ __('common.do_code') }}</th>
                    <th>{{ __('common.numero_comercial_invoice') }}</th>
                    <th>{{ __('common.numero_proforma_invoice') }}</th>
                    <th>{{ __('common.numero_bill_of_lading') }}</th>
                    <th>{{ __('common.origen') }}</th>
                    <th>{{ __('common.destino') }}</th>
                    <th>{{ __('common.fecha_salida_table') }}</th>
                    <th>{{ __('common.fecha_llegada_table') }}</th>
                    <th>{{ __('common.estado') }}</th>
                    <th>{{ __('common.creditos') }}</th>
                    <th>{{ __('common.archivos') }}</th>
                    <th>{{ __('common.acciones') }}</th>
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
                                $progressText = '100% - ' . __('common.completado');
                            } else {
                                $progress = ($elapsedDays / $totalDays) * 100;
                                $progressText = round($progress) . '%';
                            }
                        }
                    }
                @endphp
                <tr>
                    <td><strong>{{ $import->do_code }}</strong></td>
                    <td>{{ $import->commercial_invoice_number ?? $import->product_name ?? '-' }}</td>
                    <td>{{ $import->proforma_invoice_number ?? '-' }}</td>
                    <td>{{ $import->bl_number ?? '-' }}</td>
                    <td>{{ $import->origin ?? '-' }}</td>
                    <td>{{ $import->destination ?? __('common.colombia') }}</td>
                    <td>{{ $import->departure_date ? $departureDate->format('d/m/Y') : '-' }}</td>
                    <td>{{ $import->arrival_date ? $arrivalDate->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div>
                            @if($import->status === 'pending')
                                <span class="status-badge status-pending">{{ __('common.pendiente') }}</span>
                            @elseif($import->status === 'completed')
                                <span class="status-badge status-completed">{{ __('common.completado') }}</span>
                            @elseif($import->status === 'in_transit')
                                <span class="status-badge status-in-transit">{{ __('common.en_transito') }}</span>
                            @elseif($import->status === 'recibido')
                                <span class="status-badge" style="background: #17a2b8; color: white;">{{ __('common.recibido') }}</span>
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
                                    <div class="files-section-title">{{ __('common.contenedores_label') }}</div>
                                    @foreach($import->containers as $container)
                                        <div class="container-files">
                                            <div class="container-ref">{{ $container->reference }}</div>
                                            <div class="files-grid">
                                                @if($container->pdf_path)
                                                    <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.pdf_informacion_contenedor') }}">
                                                        <i class="bi bi-file-pdf"></i> {{ __('common.info_pdf') }}
                                                    </a>
                                                @endif
                                                @if($container->image_pdf_path)
                                                    <a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_image_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.pdf_imagenes_contenedor') }}">
                                                        <i class="bi bi-file-pdf"></i> {{ __('common.imagenes_pdf') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            
                            @if($hasDocuments)
                                <div class="files-section documents-section">
                                    <div class="files-section-title">{{ __('common.documentos_label') }}</div>
                                    <div class="files-grid">
                                        @if($import->proforma_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'proforma_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.proforma_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.proforma') }}
                                            </a>
                                        @endif
                                        @if($import->proforma_invoice_low_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'proforma_invoice_low_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.proforma_invoice_low_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.proforma_low') }}
                                            </a>
                                        @endif
                                        @if($import->invoice_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'invoice_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.comercial_invoice_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.invoice') }}
                                            </a>
                                        @endif
                                        @if($import->commercial_invoice_low_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'commercial_invoice_low_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.commercial_invoice_low_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.invoice_low') }}
                                            </a>
                                        @endif
                                        @if($import->packing_list_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'packing_list_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.packing_list_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.packing_list') }}
                                            </a>
                                        @endif
                                        @if($import->bl_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'bl_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.bl_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.bl') }}
                                            </a>
                                        @endif
                                        @if($import->apostillamiento_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'apostillamiento_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.apostillamiento_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.apostillamiento') }}
                                            </a>
                                        @endif
                                        @if($import->other_documents_pdf)
                                            <a href="{{ route('imports.view', [$import->id, 'other_documents_pdf']) }}" target="_blank" class="file-viewer" title="{{ __('common.otros_documentos_pdf') }}">
                                                <i class="bi bi-file-pdf"></i> {{ __('common.otros') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                            
                            @if(!$hasContainers && !$hasDocuments)
                                <span style="color: #999; font-size: 11px;">{{ __('common.sin_archivos') }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="actions">
                        <a href="{{ route('imports.edit', $import->id) }}" class="btn-edit">{{ __('common.editar') }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-5">
                        <i class="bi bi-upload text-secondary" style="font-size:2.2em;"></i><br>
                        <div class="mt-2">No hay importaciones registradas.</div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-imports');
    const table = document.getElementById('imports-table');
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>
