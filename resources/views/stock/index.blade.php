@extends('layouts.app')

@section('content')
<style>
    .table-responsive-custom {
        overflow-x: auto;
        max-width: 100vw;
        padding-bottom: 6px;
    }
    .stock-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        font-size: 14px;
        table-layout: auto;
        margin-bottom: 30px;
    }
    .stock-table th, .stock-table td {
        padding: 12px;
        word-break: break-word;
        text-align: center;
        vertical-align: middle;
    }
    .stock-table tbody tr:not(:last-child) td {
        border-bottom: 1px solid #e6e6e6;
    }
    .stock-table th {
        background: #0066cc;
        color: white;
        font-size: 14px;
        letter-spacing: 0.1px;
        white-space: nowrap;
    }
    .stock-table td {
        font-size: 13.2px;
    }
    .stock-table tr:hover {
        background: #f1f7ff;
    }
    .section-title {
        font-size: 18px;
        font-weight: bold;
        color: #333;
        margin-top: 30px;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #0066cc;
    }
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        margin-bottom: 25px;
    }
    .filter-section label {
        font-weight: bold;
        color: #333;
        margin-right: 10px;
    }
    .filter-section select {
        padding: 8px 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        min-width: 200px;
    }
</style>

<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1400px;">
        <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Inventario de Stock</h2>
        
        <!-- Filtro por almacén y botones de exportación -->
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                @php
                    $user = Auth::user();
                    $isAdmin = $user && in_array($user->rol, ['admin', 'secretaria']);
                    $isFuncionario = $user && $user->rol === 'funcionario';
                    $canExport = $user && $user->rol === 'admin'; // Solo admin puede descargar
                @endphp
                @if($isAdmin && !$isFuncionario)
                <form method="GET" action="{{ route('stock.index') }}" style="display: flex; align-items: center; gap: 15px;">
                    <label for="warehouse_id">Filtrar por Almacén:</label>
                    <select name="warehouse_id" id="warehouse_id" onchange="this.form.submit()">
                        <option value="">Todos los almacenes</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $selectedWarehouseId == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->nombre }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @else
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label>Almacén:</label>
                    <span style="font-weight: 500; color: #333;">{{ $isFuncionario ? 'Buenaventura' : ($user->almacen->nombre ?? 'N/A') }}</span>
                </div>
                @endif
                @if($canExport)
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('stock.export-pdf', request()->query()) }}" class="btn btn-primary" style="padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: 500;">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Descargar PDF
                    </a>
                    <a href="{{ route('stock.export-excel', request()->query()) }}" class="btn btn-success" style="padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white;">
                        <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Sección de Productos -->
        <div class="section-title">
            <i class="bi bi-box-seam me-2"></i>Productos
            @if($selectedWarehouseId)
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                </span>
            @endif
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Almacén</th>
                        <th>Laminas</th>
                        <th>Tipo Medida</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>{{ $product->codigo }}</td>
                        <td><strong>{{ $product->nombre }}</strong></td>
                        <td>{{ $product->almacen->nombre ?? '-' }}</td>
                        <td><strong>{{ number_format($product->stock, 0) }}</strong></td>
                        <td>{{ ucfirst($product->tipo_medida) }}</td>
                        <td>{{ $product->estado ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-box text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay productos registrados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sección de Contenedores -->
        @if($selectedWarehouseId == $ID_BUENAVENTURA || !$selectedWarehouseId)
        <div class="section-title">
            <i class="bi bi-box me-2"></i>Contenedores
            @if($selectedWarehouseId)
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                </span>
            @endif
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Referencia</th>
                        <th>Productos</th>
                        <th>Total Cajas</th>
                        <th>Total Láminas</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($containers as $container)
                    @php
                        $totalBoxes = 0;
                        $totalSheets = 0;
                        foreach($container->products as $product) {
                            $totalBoxes += $product->pivot->boxes;
                            $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                        }
                    @endphp
                    <tr>
                        <td><strong>{{ $container->reference }}</strong></td>
                        <td>
                            @if($container->products->count() > 0)
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    @foreach($container->products as $product)
                                        <div style="font-size: 13px; line-height: 1.5;">
                                            <strong>{{ $product->nombre }}</strong> 
                                            <span style="color: #666;">({{ $product->pivot->boxes }} cajas × {{ $product->pivot->sheets_per_box }} láminas)</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span style="color: #999; font-style: italic;">Sin productos</span>
                            @endif
                        </td>
                        <td><strong>{{ $totalBoxes }}</strong></td>
                        <td><strong>{{ number_format($totalSheets, 0) }}</strong></td>
                        <td>{{ $container->note ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="bi bi-box text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay contenedores registrados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif

        <!-- Sección de Transferencias -->
        <div class="section-title">
            <i class="bi bi-arrow-left-right me-2"></i>Transferencias
            @if($selectedWarehouseId)
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                </span>
            @endif
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>No. Orden</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th>Conductor</th>
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
                        <td>{{ $transfer->date->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                        <td>
                            @foreach($transfer->products as $prod)
                                <div style="font-size: 13px; margin-bottom: 4px;">
                                    <strong>{{ $prod->nombre }}</strong>
                                    <span style="color: #666;">({{ $prod->pivot->quantity }} {{ $prod->tipo_medida === 'caja' ? 'cajas' : 'unidades' }})</span>
                                </div>
                            @endforeach
                        </td>
                        <td>{{ $transfer->driver->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-arrow-left-right text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay transferencias registradas.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

