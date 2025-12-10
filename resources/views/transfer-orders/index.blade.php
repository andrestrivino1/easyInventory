@extends('layouts.app')

@section('content')
<style>
    .transfer-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .transfer-table th {
        background: #0066cc;
        color: white;
        text-align: left;
        padding: 12px;
        font-size: 15px;
    }
    .transfer-table td {
        padding: 12px;
        border-bottom: 1px solid #e6e6e6;
    }
    .transfer-table tr:hover {
        background: #f1f7ff;
    }
    .actions button, .actions a {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        margin-right: 6px;
        display:inline-block;
        text-decoration:none;
    }
    .btn-edit {background: #1d7ff0; color: white;}
    .btn-delete {background: #ffb3b3; color: #b30000;}
    .actions button:hover, .actions a:hover { opacity: 0.85; }
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1050px;">
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('transfer-orders.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva transferencia</a>
      </div>
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Transferencias entre almacenes</h2>
      <table class="transfer-table">
        <thead>
            <tr>
                <th>No. Orden</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th style="min-width:140px;max-width:180px">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($transferOrders as $transfer)
            <tr>
                <td>{{ $transfer->order_number }}</td>
                <td>{{ $transfer->from->nombre ?? '-' }}</td>
                <td>{{ $transfer->to->nombre ?? '-' }}</td>
                <td>
                    @if($transfer->status == 'en_transito')
                      <span style="background:#ffc107;color:#212529;border-radius:5px;padding:3px 10px;font-size:13px;">En tránsito</span>
                    @elseif($transfer->status == 'recibido')
                      <span style="background:#4caf50;color:white;border-radius:5px;padding:3px 10px;font-size:13px;">Recibido</span>
                    @else
                      <span style="background:#d1d5db;color:#333;border-radius:5px;padding:3px 10px;font-size:13px;">{{ ucfirst($transfer->status) }}</span>
                    @endif
                </td>
                <td>{{ $transfer->date->format('d/m/Y H:i') }}</td>
                <td class="actions" style="min-width:140px;max-width:180px;white-space:nowrap;">
                    @php $user = Auth::user(); @endphp
                    @if(in_array($transfer->status, ['en_transito','Pending','pending']) && ($user && ($user->rol == 'admin' || $user->almacen_id == $transfer->warehouse_from_id)))
                        <a href="{{ route('transfer-orders.edit', $transfer) }}" class="btn-edit" style="margin-right:8px;">Editar</a>
                        <form action="{{ route('transfer-orders.destroy', $transfer) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete" style="margin-right:8px;">Eliminar</button>
                        </form>
                    @endif
                    <a href="{{ route('transfer-orders.print', $transfer) }}" class="btn btn-outline-primary" title="Imprimir" style="margin-right:6px;vertical-align:middle;" target="_blank"><i class="bi bi-printer"></i></a>
                    <a href="{{ route('transfer-orders.export', $transfer) }}" class="btn btn-outline-secondary" title="Exportar PDF" style="vertical-align:middle;" target="_blank"><i class="bi bi-file-earmark-pdf"></i></a>
                    @if($transfer->status == 'en_transito' && $user && $user->almacen_id == $transfer->warehouse_to_id)
                        <form action="{{ route('transfer-orders.confirm', $transfer) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-success" style="margin-left:8px;">Confirmar recibido</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                  <i class="bi bi-arrow-left-right text-secondary" style="font-size:2.2em;"></i><br>
                  <div class="mt-2">No existen transferencias registradas.</div>
                </td>
            </tr>
        @endforelse
        </tbody>
      </table>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[action*="transfer-orders/"][method="POST"] .btn-delete').forEach(function(btn) {
        btn.closest('form').addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Eliminar transferencia?',
                text: '¡Esta acción no puede deshacerse!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#e12d39',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if(result.isConfirmed) {
                    e.target.submit();
                }
            });
        });
    });
    // Toast global
    @if(session('success'))
    Swal.fire({icon:'success',title:'',text:'{{ session('success') }}',toast:true,position:'top-end',showConfirmButton:false,timer:2900 });
    @endif
    @if(session('error'))
    Swal.fire({icon:'error',title:'',text:'{{ session('error') }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500 });
    @endif
});
</script>
@endsection
