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

        .transfer-table th,
        .transfer-table td {
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
            letter-spacing: 0.1px;
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

        .actions button,
        .actions a {
            padding: 5px 9px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            display: inline-block;
            text-decoration: none;
            white-space: nowrap;
        }

        .actions form {
            display: inline-block;
            margin: 0;
        }

        .btn-edit {
            background: #1d7ff0;
            color: white;
        }

        .btn-delete {
            background: #ffb3b3;
            color: #b30000;
        }

        .actions button:hover,
        .actions a:hover {
            opacity: 0.85;
        }

        .transfer-table th:nth-child(1) {
            min-width: 100px;
        }

        /* No. Orden */
        .transfer-table th:nth-child(2),
        .transfer-table th:nth-child(3) {
            min-width: 120px;
        }

        /* Origen, Destino */
        .transfer-table th:nth-child(4) {
            min-width: 100px;
        }

        /* Estado */
        .transfer-table th:nth-child(5) {
            min-width: 120px;
        }

        /* Fecha */
        .transfer-table th:nth-child(6) {
            min-width: 200px;
        }

        /* Producto */
        .transfer-table th:nth-child(7) {
            min-width: 150px;
        }

        /* Contenedor */
        .transfer-table th:nth-child(8) {
            min-width: 100px;
        }

        /* Conductor */
        .transfer-table th:nth-child(9),
        .transfer-table th:nth-child(10) {
            min-width: 100px;
        }

        /* Cédula, Placa */
        .transfer-table th:nth-child(11) {
            min-width: 180px;
        }

        /* Acciones */
    </style>
    <div class="container-fluid" style="padding-top:32px; min-height:88vh;">
        <div class="mx-auto" style="max-width:1400px;">
            @php
                // El usuario viene del controlador con las relaciones cargadas
                if (!isset($user)) {
                    $user = Auth::user();
                    // Si es funcionario o cliente, cargar la relación almacenes
                    if ($user && in_array($user->rol, ['funcionario', 'clientes']) && !$user->relationLoaded('almacenes')) {
                        $user->load('almacenes');
                    }
                }
                $ID_PABLO_ROJAS = 1;
                // Admin, funcionario o usuarios de Pablo Rojas pueden crear transferencias
                $canCreateTransfer = $user &&
                    (in_array($user->rol, ['admin', 'funcionario']) || $user->almacen_id == $ID_PABLO_ROJAS);
              @endphp
            @if($canCreateTransfer)
                <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
                    <a href="{{ route('transfer-orders.create') }}" class="btn btn-primary rounded-pill px-4"
                        style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva transferencia</a>
                </div>
            @endif
            <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Transferencias entre bodegas</h2>

            <!-- Campo de búsqueda -->
            <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
                <div style="position: relative; width: 100%;">
                    <input type="text" id="search-transfers" class="form-control" placeholder="Buscar transferencias..."
                        style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
                    <i class="bi bi-search"
                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
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
                            @php
                                $products = $transfer->products;
                                $productCount = $products->count();
                                $isFirstProduct = true;
                            @endphp
                            @foreach($products as $index => $prod)
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
                                <tr>
                                    @if($isFirstProduct)
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">{{ $transfer->order_number }}
                                        </td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->from->nombre ?? '-' }}{{ $transfer->from && $transfer->from->ciudad ? ' - ' . $transfer->from->ciudad : '' }}
                                        </td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->to->nombre ?? '-' }}{{ $transfer->to && $transfer->to->ciudad ? ' - ' . $transfer->to->ciudad : '' }}
                                        </td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            @if($transfer->status == 'en_transito')
                                                <span
                                                    style="background:#ffc107;color:#212529;border-radius:5px;padding:3px 10px;font-size:13px;">En
                                                    tránsito</span>
                                            @elseif($transfer->status == 'recibido')
                                                <span
                                                    style="background:#4caf50;color:white;border-radius:5px;padding:3px 10px;font-size:13px;">Recibido</span>
                                            @else
                                                <span
                                                    style="background:#d1d5db;color:#333;border-radius:5px;padding:3px 10px;font-size:13px;">{{ ucfirst($transfer->status) }}</span>
                                            @endif
                                        </td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->date->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                    @endif
                                    <td>
                                        <strong>{{ $prod->nombre }}</strong>
                                        @if($prod->medidas)
                                            <br><span style="color: #666; font-size: 12px;">{{ $prod->medidas }}</span>
                                        @endif
                                        <br><span style="color: #666; font-size: 12px;">
                                            @if($transfer->status === 'recibido' && isset($prod->pivot->good_sheets) && isset($prod->pivot->receive_by))
                                                ({{ $prod->pivot->good_sheets }}
                                                {{ $prod->pivot->receive_by === 'cajas' ? 'cajas' : 'láminas' }})
                                            @else
                                                ({{ $prod->pivot->quantity }}
                                                {{ $prod->tipo_medida === 'caja' ? 'cajas' : 'unidades' }})
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        @if($container)
                                            <strong>{{ $container->reference }}</strong>
                                        @else
                                            <span style="color: #999; font-style: italic;">-</span>
                                        @endif
                                    </td>
                                    @if($isFirstProduct)
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->driver->name ?? '-' }}</td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->driver->identity ?? '-' }}</td>
                                        <td rowspan="{{ $productCount }}" style="vertical-align: middle;">
                                            {{ $transfer->driver->vehicle_plate ?? '-' }}</td>
                                        <td rowspan="{{ $productCount }}" class="actions"
                                            style="white-space:nowrap; vertical-align: middle;">
                                            <div
                                                style="display: flex; gap: 6px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                                @if($user->rol !== 'funcionario' && in_array($transfer->status, ['en_transito', 'Pending', 'pending']) && ($user && ($user->rol == 'admin' || $user->almacen_id == $transfer->warehouse_from_id)))
                                                    <a href="{{ route('transfer-orders.edit', $transfer) }}" class="btn-edit">Editar</a>
                                                    <form action="{{ route('transfer-orders.destroy', $transfer) }}" method="POST"
                                                        style="display:inline; margin:0;">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn-delete">Eliminar</button>
                                                    </form>
                                                @endif
                                                <a href="{{ route('transfer-orders.print', $transfer) }}"
                                                    class="btn btn-outline-secondary" title="Imprimir"
                                                    style="padding: 5px 9px; vertical-align:middle;" target="_blank"><i
                                                        class="bi bi-printer"></i></a>
                                                @php
                                                    $canConfirmTransfer = false;
                                                    if ($transfer->status == 'en_transito' && $user) {
                                                        // Admin y funcionario pueden confirmar desde cualquier lugar
                                                        if (in_array($user->rol, ['admin', 'funcionario'])) {
                                                            $canConfirmTransfer = true;
                                                        } elseif ($user->rol === 'clientes') {
                                                            // Clientes pueden confirmar si la bodega destino está en sus bodegas asignadas
                                                            if (!$user->relationLoaded('almacenes')) {
                                                                $user->load('almacenes');
                                                            }
                                                            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
                                                            $canConfirmTransfer = in_array($transfer->warehouse_to_id, $bodegasAsignadasIds);
                                                        } else {
                                                            $canConfirmTransfer = $user->almacen_id == $transfer->warehouse_to_id;
                                                        }
                                                    }
                                                @endphp
                                                @if($canConfirmTransfer)
                                                    <a href="{{ route('transfer-orders.confirm', $transfer) }}" class="btn btn-success"
                                                        style="padding: 5px 9px; font-size: 12px; text-decoration: none; display: inline-block;">Confirmar
                                                        recibido</a>
                                                @endif
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                                @php $isFirstProduct = false; @endphp
                            @endforeach
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

                <!-- Paginación -->
                <div id="transfers-pagination" class="mt-4">
                    @if(method_exists($transferOrders, 'total') && $transferOrders->total() > $transferOrders->perPage())
                        <div
                            style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                <div style="color: #666; font-size: 14px;">
                                    Mostrando {{ $transferOrders->firstItem() ?? 0 }} - {{ $transferOrders->lastItem() ?? 0 }}
                                    de {{ $transferOrders->total() }} transferencias
                                </div>
                                <div>
                                    {!! $transferOrders->appends(request()->query())->links() !!}
                                </div>
                            </div>
                        </div>
                    @elseif(method_exists($transferOrders, 'total') && $transferOrders->total() > 0)
                        <div
                            style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); text-align: center; color: #666; font-size: 14px;">
                            Mostrando {{ $transferOrders->total() }} transferencia(s)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Búsqueda en tiempo real
            const searchInput = document.getElementById('search-transfers');
            const table = document.getElementById('transfers-main-table');
            if (searchInput && table) {
                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.toLowerCase().trim();
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(function (row) {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchTerm) ? '' : 'none';
                    });
                });
            }

            document.querySelectorAll('form[action*="transfer-orders/"][method="POST"] .btn-delete').forEach(function (btn) {
                btn.closest('form').addEventListener('submit', function (e) {
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
                        if (result.isConfirmed) {
                            e.target.submit();
                        }
                    });
                });
            });
            // Toast global
            @if(session('success'))
                Swal.fire({ icon: 'success', title: '', text: '{{ session('success') }}', toast: true, position: 'top-end', showConfirmButton: false, timer: 2900 });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: '', text: '{{ session('error') }}', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
            @endif
    });
    </script>
@endsection