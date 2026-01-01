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
        padding: 12px;
        word-break: break-word;
        vertical-align: top;
    }
    .transfer-table tbody tr:not(:last-child) td {
        border-bottom: 1px solid #e6e6e6;
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
.actions {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
}
.actions button, .actions a {
    padding: 5px 9px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    display:inline-block;
    text-decoration:none;
    white-space: nowrap;
}
.actions form {
    display: inline-block;
    margin: 0;
}
    .btn-edit {background: #1d7ff0; color: white;}
    .btn-delete {background: #ffb3b3; color: #b30000;}
    .actions button:hover, .actions a:hover { opacity: 0.85; }
    .transfer-table th:nth-child(1) { min-width: 100px; } /* No. Orden */
    .transfer-table th:nth-child(2), .transfer-table th:nth-child(3) { min-width: 120px; } /* Origen, Destino */
    .transfer-table th:nth-child(4) { min-width: 100px; } /* Estado */
    .transfer-table th:nth-child(5) { min-width: 120px; } /* Fecha */
    .transfer-table th:nth-child(6) { min-width: 200px; } /* Producto */
    .transfer-table th:nth-child(7) { min-width: 150px; } /* Contenedor */
    .transfer-table th:nth-child(8) { min-width: 100px; } /* Conductor */
    .transfer-table th:nth-child(9), .transfer-table th:nth-child(10) { min-width: 100px; } /* Cédula, Placa */
    .transfer-table th:nth-child(11) { min-width: 180px; } /* Acciones */
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1400px;">
      @php
          $user = Auth::user();
          $ID_PABLO_ROJAS = 1;
          // Solo admin, secretaria o usuarios de Pablo Rojas pueden crear transferencias
          // Las demás bodegas solo reciben transferencias
          $canCreateTransfer = $user && 
                               $user->rol !== 'funcionario' && 
                               (in_array($user->rol, ['admin', 'secretaria']) || $user->almacen_id == $ID_PABLO_ROJAS);
      @endphp
      @if($canCreateTransfer)
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('transfer-orders.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva transferencia</a>
      </div>
      @endif
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Transferencias entre bodegas</h2>
      
      <!-- Campo de búsqueda -->
      <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
        <div style="position: relative; width: 100%;">
          <input type="text" id="search-transfers" class="form-control" placeholder="Buscar transferencias..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
          <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
        </div>
      </div>
      
      <div class="table-responsive-custom">
      <table class="transfer-table" id="transfers-main-table">
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
                <td>{{ $transfer->from->nombre ?? '-' }}{{ $transfer->from && $transfer->from->ciudad ? ' - ' . $transfer->from->ciudad : '' }}</td>
                <td>{{ $transfer->to->nombre ?? '-' }}{{ $transfer->to && $transfer->to->ciudad ? ' - ' . $transfer->to->ciudad : '' }}</td>
                <td>
                    @if($transfer->status == 'en_transito')
                      <span style="background:#ffc107;color:#212529;border-radius:5px;padding:3px 10px;font-size:13px;">En tránsito</span>
                    @elseif($transfer->status == 'recibido')
                      <span style="background:#4caf50;color:white;border-radius:5px;padding:3px 10px;font-size:13px;">Recibido</span>
                    @else
                      <span style="background:#d1d5db;color:#333;border-radius:5px;padding:3px 10px;font-size:13px;">{{ ucfirst($transfer->status) }}</span>
                    @endif
                </td>
                <td>{{ $transfer->date->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                <td>
                  @foreach($transfer->products as $prod)
                    <div style="font-size: 13px; margin-bottom: 4px;">
                      <strong>{{ $prod->nombre }}</strong>
                      <span style="color: #666;">({{ $prod->pivot->quantity }} {{ $prod->tipo_medida === 'caja' ? 'cajas' : 'unidades' }})</span>
                    </div>
                  @endforeach
                </td>
                <td>
                  @foreach($transfer->products as $prod)
                    <div style="font-size: 13px; margin-bottom: 4px;">
                      @php
                        $containerId = $prod->pivot->container_id ?? null;
                        $container = null;
                        if ($containerId) {
                            if (isset($containers) && $containers->has($containerId)) {
                                $container = $containers->get($containerId);
                            } else {
                                $container = \App\Models\Container::find($containerId);
                            }
                        }
                      @endphp
                      @if($container)
                        <strong>{{ $container->reference }}</strong>
                      @else
                        <span style="color: #999; font-style: italic;">-</span>
                      @endif
                    </div>
                  @endforeach
                </td>
                <td>{{ $transfer->driver->name ?? '-' }}</td>
                <td>{{ $transfer->driver->identity ?? '-' }}</td>
                <td>{{ $transfer->driver->vehicle_plate ?? '-' }}</td>
                <td class="actions" style="white-space:nowrap;">
                    @php $user = Auth::user(); @endphp
                    <div style="display: flex; gap: 6px; align-items: center; justify-content: center; flex-wrap: wrap;">
                        @if($user->rol !== 'funcionario' && in_array($transfer->status, ['en_transito','Pending','pending']) && ($user && ($user->rol == 'admin' || $user->almacen_id == $transfer->warehouse_from_id)))
                            <a href="{{ route('transfer-orders.edit', $transfer) }}" class="btn-edit">Editar</a>
                            <form action="{{ route('transfer-orders.destroy', $transfer) }}" method="POST" style="display:inline; margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-delete">Eliminar</button>
                            </form>
                        @endif
                        <a href="{{ route('transfer-orders.print', $transfer) }}" class="btn btn-outline-secondary" title="Imprimir" style="padding: 5px 9px; vertical-align:middle;" target="_blank"><i class="bi bi-printer"></i></a>
                        @if($user->rol !== 'funcionario' && $transfer->status == 'en_transito' && $user && $user->almacen_id == $transfer->warehouse_to_id)
                            <form action="{{ route('transfer-orders.confirm', $transfer) }}" method="POST" style="display:inline; margin:0;">
                                @csrf
                                <button type="submit" class="btn btn-success" style="padding: 5px 9px; font-size: 12px;">Confirmar recibido</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center text-muted py-5">
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
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('search-transfers');
    const table = document.getElementById('transfers-main-table');
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
