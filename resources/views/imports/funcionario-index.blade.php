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
    table {
        width: 100%;
        border-collapse: collapse;
    }
    thead {
        background: #4a8af4;
        color: white;
    }
    th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
    }
    td {
        padding: 12px;
        border-bottom: 1px solid #e0e0e0;
        font-size: 14px;
    }
    tbody tr:hover {
        background: #f5f5f5;
    }
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
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
</style>

<div class="table-container">
    <div class="table-header">
        <h2>Importaciones - Vista Funcionario</h2>
        <input type="text" id="searchInput" class="search-box" placeholder="Buscar importaciones...">
    </div>

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
                <th>Estado</th>
                <th>Archivos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($imports as $import)
            <tr>
                <td><strong>{{ $import->do_code }}</strong></td>
                <td>{{ $import->user->nombre_completo ?? $import->user->email }}</td>
                <td>{{ $import->commercial_invoice_number ?? $import->product_name ?? '-' }}</td>
                <td>{{ $import->proforma_invoice_number ?? '-' }}</td>
                <td>{{ $import->bl_number ?? '-' }}</td>
                <td>{{ $import->origin ?? '-' }}</td>
                <td>{{ $import->destination ?? 'Colombia' }}</td>
                <td>{{ $import->departure_date ? \Carbon\Carbon::parse($import->departure_date)->format('d/m/Y') : '-' }}</td>
                <td>{{ $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date)->format('d/m/Y') : '-' }}</td>
                <td>
                    @if($import->status == 'pending')
                        <span class="badge badge-pending">Pendiente</span>
                    @elseif($import->status == 'completed')
                        <span class="badge badge-completed">Completado</span>
                    @elseif($import->status == 'recibido')
                        <span class="badge badge-received">Recibido</span>
                    @else
                        <span class="badge">{{ $import->status }}</span>
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
                        @if($import->status != 'recibido')
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
                <td colspan="12" style="text-align: center; padding: 30px; color: #666;">
                    No hay importaciones registradas
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
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

// Búsqueda en tiempo real
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#importsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Modal functions
function openUpdateModal(importId, doCode) {
    document.getElementById('updateArrivalForm').action = `/imports/${importId}/update-arrival`;
    document.getElementById('updateModal').style.display = 'block';
    document.getElementById('actual_arrival_date').value = new Date().toISOString().split('T')[0];
}

function closeUpdateModal() {
    document.getElementById('updateModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('updateModal');
    if (event.target == modal) {
        closeUpdateModal();
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
