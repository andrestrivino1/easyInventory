@extends('layouts.app')

@section('content')
<style>
    .itr-table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.08); font-size: 14px; }
    .itr-table th, .itr-table td { padding: 12px; vertical-align: middle; border-bottom: 1px solid #e6e6e6; }
    .itr-table th { background: #0066cc; color: white; font-weight: 600; white-space: nowrap; }
    .itr-table tr:hover { background: #f1f7ff; }
    .itr-table tr.alert-vencimiento { background: #fff3cd !important; }
    .itr-table tr.alert-vencimiento:hover { background: #ffe69c !important; }
    .itr-table tr.alert-vencimiento td.col-vencimiento { font-weight: 700; color: #856404; }
    .btn-date { background: #17a2b8; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
    .btn-date:hover { background: #138496; color: white; }
    .btn-evidence { background: #6c757d; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
    .btn-evidence:hover { color: white; background: #5a6268; }
    .btn-history { background: transparent; border: none; color: #6c757d; cursor: pointer; padding: 2px 6px; font-size: 14px; }
    .btn-history:hover { color: #0066cc; }
    .evidence-links { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .evidence-links a { font-size: 12px; color: #0066cc; text-decoration: none; padding: 4px 10px; background: #e8f0fe; border-radius: 6px; }
    .evidence-links a:hover { background: #d2e3fc; color: #004494; }
    .modal-content { border-radius: 12px; }
    .modal-header { background: #0066cc; color: white; border-radius: 12px 12px 0 0; }
</style>

<div class="container-fluid" style="padding-top: 32px; min-height: 88vh;">
    <div class="mx-auto" style="max-width: 1600px;">
        <h2 class="mb-4" style="text-align: center; color: #333; font-weight: bold;">ITR (Desembalaje)</h2>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="itr-table">
                <thead>
                    <tr>
                        <th>DO</th>
                        <th>No Bill of Lading</th>
                        <th>Fecha llegada</th>
                        <th>Fecha vencimiento</th>
                        <th>Fecha retiro contenedor</th>
                        <th>Fecha vaciado contenedor</th>
                        <th>Fecha devolución contenedor</th>
                        <th>Evidencias</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($itrs as $itr)
                        @php
                            $alertRow = $itr->isApproachingDueDate() || $itr->isOverdue();
                        @endphp
                        <tr class="{{ $alertRow ? 'alert-vencimiento' : '' }}">
                            <td>{{ $itr->do_code }}</td>
                            <td>{{ $itr->bl_number ?? '-' }}</td>
                            <td>{{ $itr->fecha_llegada->format('d/m/Y') }}</td>
                            <td class="{{ $alertRow ? 'col-vencimiento' : '' }}">
                                {{ $itr->fecha_vencimiento->format('d/m/Y') }}
                                @if($itr->isApproachingDueDate())
                                    <span class="badge bg-warning text-dark ms-1">Llegando a límite</span>
                                @elseif($itr->isOverdue())
                                    <span class="badge bg-danger ms-1">Vencido</span>
                                @endif
                            </td>
                            <td>
                                <span class="date-display" data-id="{{ $itr->id }}" data-field="fecha_retiro_contenedor">
                                    {{ $itr->fecha_retiro_contenedor ? $itr->fecha_retiro_contenedor->format('d/m/Y') : '-' }}
                                </span>
                                @if($itr->dateHistories->where('field_name', 'fecha_retiro_contenedor')->count() > 0)
                                    <button type="button" class="btn-history" title="Ver historial" onclick="openHistory({{ $itr->id }}, 'fecha_retiro_contenedor')"><i class="bi bi-clock-history"></i></button>
                                @endif
                                <button type="button" class="btn-date ms-1" onclick="openDateModal({{ $itr->id }}, 'fecha_retiro_contenedor', '{{ $itr->fecha_retiro_contenedor ? $itr->fecha_retiro_contenedor->format('Y-m-d') : '' }}')">Editar</button>
                            </td>
                            <td>
                                <span class="date-display" data-id="{{ $itr->id }}" data-field="fecha_vaciado_contenedor">
                                    {{ $itr->fecha_vaciado_contenedor ? $itr->fecha_vaciado_contenedor->format('d/m/Y') : '-' }}
                                </span>
                                @if($itr->dateHistories->where('field_name', 'fecha_vaciado_contenedor')->count() > 0)
                                    <button type="button" class="btn-history" title="Ver historial" onclick="openHistory({{ $itr->id }}, 'fecha_vaciado_contenedor')"><i class="bi bi-clock-history"></i></button>
                                @endif
                                <button type="button" class="btn-date ms-1" onclick="openDateModal({{ $itr->id }}, 'fecha_vaciado_contenedor', '{{ $itr->fecha_vaciado_contenedor ? $itr->fecha_vaciado_contenedor->format('Y-m-d') : '' }}')">Editar</button>
                            </td>
                            <td>
                                <span class="date-display" data-id="{{ $itr->id }}" data-field="fecha_devolucion_contenedor">
                                    {{ $itr->fecha_devolucion_contenedor ? $itr->fecha_devolucion_contenedor->format('d/m/Y') : '-' }}
                                </span>
                                @if($itr->dateHistories->where('field_name', 'fecha_devolucion_contenedor')->count() > 0)
                                    <button type="button" class="btn-history" title="Ver historial" onclick="openHistory({{ $itr->id }}, 'fecha_devolucion_contenedor')"><i class="bi bi-clock-history"></i></button>
                                @endif
                                <button type="button" class="btn-date ms-1" onclick="openDateModal({{ $itr->id }}, 'fecha_devolucion_contenedor', '{{ $itr->fecha_devolucion_contenedor ? $itr->fecha_devolucion_contenedor->format('Y-m-d') : '' }}')">Editar</button>
                            </td>
                            <td>
                                <div class="evidence-links">
                                    @if($itr->evidencia_tiquete_retiro_pdf)
                                        <a href="{{ route('itrs.download-evidence', ['itr' => $itr->id, 'type' => 'tiquete_retiro']) }}" target="_blank">Tiquete retiro</a>
                                    @else
                                        <span class="text-muted small">T. retiro -</span>
                                    @endif
                                    @if($itr->evidencia_tiquete_devolucion_pdf)
                                        <a href="{{ route('itrs.download-evidence', ['itr' => $itr->id, 'type' => 'tiquete_devolucion']) }}" target="_blank">Tiquete devolución</a>
                                    @else
                                        <span class="text-muted small">T. devol. -</span>
                                    @endif
                                    @if($itr->evidencia_fotos_pdf)
                                        <a href="{{ route('itrs.download-evidence', ['itr' => $itr->id, 'type' => 'fotos']) }}" target="_blank">Fotos</a>
                                    @else
                                        <span class="text-muted small">Fotos -</span>
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <button type="button" class="btn-evidence btn-sm" onclick="openEvidenceModal({{ $itr->id }})">Subir evidencias (PDF)</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No hay registros ITR. Los ITR se crean cuando el funcionario confirma el arribo de una importación.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($itrs->hasPages())
            <div class="mt-4 d-flex justify-content-center">
                {{ $itrs->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal fecha --}}
<div id="dateModal" class="itr-modal-overlay" style="display: none;">
    <div class="itr-modal-box itr-modal-date">
        <div class="itr-modal-header">
            <h5 class="itr-modal-title" id="dateModalTitle"><i class="bi bi-calendar-event me-2"></i>Editar fecha</h5>
            <button type="button" class="itr-modal-close" onclick="closeDateModal()" title="Cerrar">&times;</button>
        </div>
        <div class="itr-modal-body">
            <input type="hidden" id="dateModalItrId" value="">
            <input type="hidden" id="dateModalField" value="">
            <label for="dateModalValue" class="itr-modal-label">Seleccione la fecha</label>
            <div class="itr-date-input-wrap">
                <i class="bi bi-calendar3"></i>
                <input type="date" id="dateModalValue" class="itr-date-input">
            </div>
        </div>
        <div class="itr-modal-footer">
            <button type="button" class="itr-btn itr-btn-secondary" onclick="closeDateModal()">Cancelar</button>
            <button type="button" class="itr-btn itr-btn-primary" id="dateModalSave"><i class="bi bi-check-lg me-1"></i>Guardar</button>
        </div>
    </div>
</div>

{{-- Modal evidencias --}}
<div id="evidenceModal" class="itr-modal-overlay" style="display: none;">
    <div class="itr-modal-box itr-modal-evidence">
        <div class="itr-modal-header">
            <h5 class="itr-modal-title"><i class="bi bi-cloud-upload me-2"></i>Subir evidencias (PDF)</h5>
            <button type="button" class="itr-modal-close" onclick="closeEvidenceModal()" title="Cerrar">&times;</button>
        </div>
        <div class="itr-modal-body">
            <input type="hidden" id="evidenceItrId" value="">
            <p class="itr-evidence-hint">Suba uno o más PDF. Tamaño máximo por archivo: 15 MB.</p>
            <div class="itr-evidence-cards">
                <div class="itr-evidence-card">
                    <div class="itr-evidence-card-icon"><i class="bi bi-receipt"></i></div>
                    <label class="itr-evidence-card-label">Tiquete retiro contenedor</label>
                    <input type="file" class="itr-evidence-file" accept=".pdf" id="fileTiqueteRetiro">
                    <span class="itr-evidence-filename" id="nameTiqueteRetiro"></span>
                </div>
                <div class="itr-evidence-card">
                    <div class="itr-evidence-card-icon"><i class="bi bi-receipt-cutoff"></i></div>
                    <label class="itr-evidence-card-label">Tiquete devolución contenedor</label>
                    <input type="file" class="itr-evidence-file" accept=".pdf" id="fileTiqueteDevolucion">
                    <span class="itr-evidence-filename" id="nameTiqueteDevolucion"></span>
                </div>
                <div class="itr-evidence-card">
                    <div class="itr-evidence-card-icon"><i class="bi bi-images"></i></div>
                    <label class="itr-evidence-card-label">Fotos contenedor (PDF)</label>
                    <input type="file" class="itr-evidence-file" accept=".pdf" id="fileFotos">
                    <span class="itr-evidence-filename" id="nameFotos"></span>
                </div>
            </div>
        </div>
        <div class="itr-modal-footer">
            <button type="button" class="itr-btn itr-btn-secondary" onclick="closeEvidenceModal()">Cerrar</button>
            <button type="button" class="itr-btn itr-btn-primary" id="evidenceUploadBtn"><i class="bi bi-upload me-1"></i>Subir seleccionados</button>
        </div>
    </div>
</div>

{{-- Modal historial --}}
<div id="historyModal" class="itr-modal-overlay" style="display: none;">
    <div class="itr-modal-box">
        <div class="itr-modal-header">
            <h5 class="itr-modal-title"><i class="bi bi-clock-history me-2"></i>Historial de cambios</h5>
            <button type="button" class="itr-modal-close" onclick="closeHistoryModal()" title="Cerrar">&times;</button>
        </div>
        <div class="itr-modal-body" id="historyModalBody">
            <p class="text-muted">Cargando...</p>
        </div>
    </div>
</div>

<style>
.itr-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.45); z-index: 1050; display: flex; align-items: center; justify-content: center; padding: 20px; }
.itr-modal-box { background: #fff; border-radius: 14px; max-width: 520px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); display: flex; flex-direction: column; }
.itr-modal-evidence { max-width: 560px; }
.itr-modal-header { padding: 16px 20px; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: #fff; display: flex; justify-content: space-between; align-items: center; }
.itr-modal-title { margin: 0; font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; }
.itr-modal-close { background: rgba(255,255,255,0.2); border: none; color: #fff; font-size: 22px; cursor: pointer; line-height: 1; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.itr-modal-close:hover { background: rgba(255,255,255,0.3); }
.itr-modal-body { padding: 24px; overflow-y: auto; }
.itr-modal-footer { padding: 16px 20px; border-top: 1px solid #e8e8e8; background: #fafafa; display: flex; gap: 10px; justify-content: flex-end; }
.itr-modal-label { display: block; font-weight: 600; color: #333; margin-bottom: 10px; font-size: 14px; }
.itr-date-input-wrap { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: #f5f7fa; border: 2px solid #e0e4e8; border-radius: 10px; }
.itr-date-input-wrap:focus-within { border-color: #0066cc; background: #fff; }
.itr-date-input-wrap i { font-size: 1.3rem; color: #0066cc; }
.itr-date-input { border: none; background: none; font-size: 1rem; flex: 1; outline: none; }
.itr-btn { padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; border: none; display: inline-flex; align-items: center; }
.itr-btn-primary { background: #0066cc; color: #fff; }
.itr-btn-primary:hover { background: #0052a3; color: #fff; }
.itr-btn-secondary { background: #e0e4e8; color: #444; }
.itr-btn-secondary:hover { background: #d0d4d8; color: #222; }
.itr-evidence-hint { color: #666; font-size: 13px; margin-bottom: 18px; }
.itr-evidence-cards { display: flex; flex-direction: column; gap: 14px; }
.itr-evidence-card { border: 2px dashed #d0d4d8; border-radius: 12px; padding: 16px; background: #fafbfc; transition: border-color 0.2s, background 0.2s; }
.itr-evidence-card:hover, .itr-evidence-card:focus-within { border-color: #0066cc; background: #f0f6ff; }
.itr-evidence-card-icon { width: 44px; height: 44px; border-radius: 10px; background: #0066cc; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 10px; }
.itr-evidence-card-label { display: block; font-weight: 600; color: #333; font-size: 13px; margin-bottom: 8px; cursor: pointer; }
.itr-evidence-file { font-size: 13px; width: 100%; cursor: pointer; }
.itr-evidence-filename { display: block; font-size: 12px; color: #28a745; margin-top: 6px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var csrfToken = document.querySelector('meta[name="csrf-token"]');
    var token = csrfToken ? csrfToken.getAttribute('content') : '{{ csrf_token() }}';
    var baseUrl = '{{ url("itrs") }}';

    function showModal(id) { document.getElementById(id).style.display = 'flex'; }
    function hideModal(id) { document.getElementById(id).style.display = 'none'; }
    function closeDateModal() { hideModal('dateModal'); }
    function closeEvidenceModal() { hideModal('evidenceModal'); }
    function closeHistoryModal() { hideModal('historyModal'); }

    window.closeDateModal = closeDateModal;
    window.closeEvidenceModal = closeEvidenceModal;
    window.closeHistoryModal = closeHistoryModal;

    window.openDateModal = function(itrId, field, currentValue) {
        var labels = { 'fecha_retiro_contenedor': 'Fecha retiro contenedor', 'fecha_vaciado_contenedor': 'Fecha vaciado contenedor', 'fecha_devolucion_contenedor': 'Fecha devolución contenedor' };
        document.getElementById('dateModalTitle').textContent = labels[field] || field;
        document.getElementById('dateModalItrId').value = itrId;
        document.getElementById('dateModalField').value = field;
        document.getElementById('dateModalValue').value = currentValue || '';
        showModal('dateModal');
    };

    document.getElementById('dateModalSave').addEventListener('click', function() {
        var itrId = document.getElementById('dateModalItrId').value;
        var field = document.getElementById('dateModalField').value;
        var value = document.getElementById('dateModalValue').value;
        if (!value) { alert('Seleccione una fecha.'); return; }
        var formData = new FormData();
        formData.append('_token', token);
        formData.append('field', field);
        formData.append('value', value);
        fetch(baseUrl + '/' + itrId + '/update-date', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        }).then(function(r) {
            if (!r.ok) throw new Error(r.status === 419 ? 'Sesión expirada. Recarga la página.' : (r.status === 403 ? 'Sin permiso.' : 'Error del servidor.'));
            return r.json();
        }).then(function(data) {
            if (data.success) {
                var cell = document.querySelector('.date-display[data-id="' + itrId + '"][data-field="' + field + '"]');
                if (cell) cell.textContent = data.formatted;
                closeDateModal();
                location.reload();
            } else {
                alert(data.message || 'Error al actualizar.');
            }
        }).catch(function(e) { alert(e.message || 'Error de conexión.'); });
    });

    window.openEvidenceModal = function(itrId) {
        document.getElementById('evidenceItrId').value = itrId;
        ['fileTiqueteRetiro', 'fileTiqueteDevolucion', 'fileFotos'].forEach(function(id, i) {
            var inp = document.getElementById(id);
            inp.value = '';
            document.getElementById(['nameTiqueteRetiro', 'nameTiqueteDevolucion', 'nameFotos'][i]).textContent = '';
        });
        showModal('evidenceModal');
    };
    ['fileTiqueteRetiro', 'fileTiqueteDevolucion', 'fileFotos'].forEach(function(id, i) {
        document.getElementById(id).addEventListener('change', function() {
            var name = this.files.length ? this.files[0].name : '';
            document.getElementById(['nameTiqueteRetiro', 'nameTiqueteDevolucion', 'nameFotos'][i]).textContent = name ? '\u2713 ' + name : '';
        });
    });

    document.getElementById('evidenceUploadBtn').addEventListener('click', function() {
        var itrId = document.getElementById('evidenceItrId').value;
        var files = [
            { input: document.getElementById('fileTiqueteRetiro'), type: 'tiquete_retiro' },
            { input: document.getElementById('fileTiqueteDevolucion'), type: 'tiquete_devolucion' },
            { input: document.getElementById('fileFotos'), type: 'fotos' }
        ];
        var toUpload = [];
        files.forEach(function(f) {
            if (f.input.files.length) toUpload.push({ fd: (function() { var d = new FormData(); d.append('_token', token); d.append('type', f.type); d.append('file', f.input.files[0]); return d; })(), type: f.type });
        });
        if (toUpload.length === 0) { alert('Seleccione al menos un archivo PDF.'); return; }
        var done = 0;
        toUpload.forEach(function(u) {
            fetch(baseUrl + '/' + itrId + '/upload-evidence', {
                method: 'POST',
                body: u.fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
            }).then(function(r) {
                if (!r.ok) throw new Error(r.status === 419 ? 'Sesión expirada.' : r.status === 403 ? 'Sin permiso.' : 'Error.');
                return r.json();
            }).then(function(data) { done++; if (done === toUpload.length) { closeEvidenceModal(); location.reload(); } })
            .catch(function(e) { alert(e.message || 'Error al subir.'); done++; });
        });
    });

    window.openHistory = function(itrId, field) {
        document.getElementById('historyModalBody').innerHTML = '<p class="text-muted">Cargando...</p>';
        showModal('historyModal');
        fetch(baseUrl + '/' + itrId + '/date-history/' + field, { headers: { 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.histories || data.histories.length === 0) {
                    document.getElementById('historyModalBody').innerHTML = '<p class="text-muted">No hay historial.</p>';
                    return;
                }
                var html = '<table class="table table-sm"><thead><tr><th>Fecha anterior</th><th>Nueva fecha</th><th>Usuario</th><th>Cuándo</th></tr></thead><tbody>';
                data.histories.forEach(function(h) {
                    html += '<tr><td>' + (h.old_value || '-') + '</td><td>' + (h.new_value || '-') + '</td><td>' + (h.user ? h.user.name : '-') + '</td><td>' + (h.created_at ? new Date(h.created_at).toLocaleString('es') : '-') + '</td></tr>';
                });
                html += '</tbody></table>';
                document.getElementById('historyModalBody').innerHTML = html;
            })
            .catch(function() {
                document.getElementById('historyModalBody').innerHTML = '<p class="text-danger">Error al cargar historial.</p>';
            });
    };
});
</script>
@endsection
