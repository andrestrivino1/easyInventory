@extends('layouts.app')

@section('content')
<style>
    .table-responsive-custom {
        overflow-x: auto;
        max-width: 100vw;
        padding-bottom: 6px;
    }
    .transfer-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        font-size: 14px;
        table-layout: auto;
    }
    .transfer-table th, .transfer-table td {
        padding: 7px 7px;
        border-bottom: 1px solid #e6e6e6;
        word-break: break-word;
        vertical-align: middle;
    }
    .transfer-table th {
        background: #0066cc;
        color: white;
        font-size: 14px;
        letter-spacing:0.1px;
        white-space: nowrap;
    }
    .transfer-table td {
        font-size: 13.2px;
    }
    .transfer-table tr:hover {
        background: #f1f7ff;
    }
    .actions button, .actions a {
        padding: 5px 9px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        margin-right: 6px;
        display:inline-block;
        text-decoration:none;
    }
    .btn-edit {background: #1d7ff0; color: white;}
    .btn-delete {background: #ffb3b3; color: #b30000;}
    .actions button:hover, .actions a:hover { opacity: 0.85; }
    .transfer-table th:nth-child(6), .transfer-table td:nth-child(6),
    .transfer-table th:nth-child(7), .transfer-table td:nth-child(7) {
        min-width: 90px;
        max-width: 160px;
    }
    .transfer-table th:nth-child(8), .transfer-table td:nth-child(8) {
        min-width: 70px;
        max-width: 110px;
    }
    .transfer-table th, .transfer-table td {
        max-width: 145px;
    }
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1050px;">
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('transfer-orders.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva transferencia</a>
      </div>
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Transferencias entre almacenes</h2>
      <div class="table-responsive-custom">
      <table class="transfer-table">
        <thead>
            <tr>
                <th>No. Orden</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Estado</th>
                <th>Fecha</th>
                <th>Producto</th>
                <th>Contenedor</th>
                <th>Conductor</th>
                <th>Cédula</th>
                <th>Placa</th>
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
                <td>
                  @foreach($transfer->products as $prod)
                    {{ $prod->nombre }} @if(!$loop->last)<br>@endif
                  @endforeach
                </td>
                <td>
                  @foreach($transfer->products as $prod)
                    {{ $prod->container->reference ?? '-' }} @if(!$loop->last)<br>@endif
                  @endforeach
                </td>
                <td>{{ $transfer->driver->name ?? '-' }}</td>
                <td>{{ $transfer->driver->identity ?? '-' }}</td>
                <td>{{ $transfer->driver->vehicle_plate ?? '-' }}</td>
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
