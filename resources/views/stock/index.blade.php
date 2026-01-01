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
                @if($isAdmin)
                <form method="GET" action="{{ route('stock.index') }}" style="display: flex; align-items: center; gap: 15px;">
                    <label for="warehouse_id">Filtrar por Bodega:</label>
                    <select name="warehouse_id" id="warehouse_id" onchange="this.form.submit()">
                        <option value="">Todos los bodegas</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $selectedWarehouseId == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->nombre }}{{ $warehouse->ciudad ? ' - ' . $warehouse->ciudad : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @elseif($isFuncionario && $warehouses->count() > 1)
                <form method="GET" action="{{ route('stock.index') }}" style="display: flex; align-items: center; gap: 15px;">
                    <label for="warehouse_id">Filtrar por Bodega:</label>
                    <select name="warehouse_id" id="warehouse_id" onchange="this.form.submit()">
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ $selectedWarehouseId == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->nombre }}{{ $warehouse->ciudad ? ' - ' . $warehouse->ciudad : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @else
                <div style="display: flex; align-items: center; gap: 15px;">
                    <label>Bodega:</label>
                    <span style="font-weight: 500; color: #333;">
                        @if($isFuncionario && $warehouses->count() > 0)
                            {{ $warehouses->first()->nombre }}{{ $warehouses->first()->ciudad ? ' - ' . $warehouses->first()->ciudad : '' }}
                        @else
                            {{ $user->almacen->nombre ?? 'N/A' }}{{ $user->almacen && $user->almacen->ciudad ? ' - ' . $user->almacen->ciudad : '' }}
                        @endif
                    </span>
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
        <!-- Búsqueda de productos -->
        <div class="mb-3" style="max-width: 400px;">
            <div style="position: relative;">
                <input type="text" id="search-products-stock" class="form-control" placeholder="Buscar productos..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;"></i>
            </div>
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table" id="products-stock-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Bodega</th>
                        <th>Medidas</th>
                        <th>Contenedor</th>
                        <th>Cajas</th>
                        <th>Laminas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    @php
                        $bodegasQueRecibenIds = is_array($bodegasQueRecibenContenedores) ? $bodegasQueRecibenContenedores : [];
                        // Si el producto tiene contenedor asignado (siempre que venga de container_product), usar valores del contenedor
                        $productHasContainer = isset($product->container_reference);
                        
                        // Si el producto tiene contenedor asignado, usar valores del contenedor
                        if ($productHasContainer) {
                            $stockEnBodega = $product->laminas_en_contenedor ?? 0;
                            $cajasReales = $product->cajas_en_contenedor ?? 0;
                            $bajoStock = $cajasReales <= 5 && $cajasReales >= 0;
                        } else {
                            // Obtener stock de este producto en la bodega seleccionada (o suma total si no hay bodega seleccionada)
                            $stockEnBodega = 0;
                            if (isset($productosStockPorBodega) && $productosStockPorBodega->has($product->id)) {
                                $stockPorBodega = $productosStockPorBodega->get($product->id);
                                if ($selectedWarehouseId) {
                                    $stockEnBodega = $stockPorBodega->get($selectedWarehouseId, 0);
                                } else {
                                    // Si no hay bodega seleccionada, sumar el stock de todas las bodegas
                                    $stockEnBodega = $stockPorBodega->sum();
                                }
                            }
                            // Calcular cajas para verificar bajo stock
                            $cajasReales = null;
                            $bajoStock = false;
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $cajasReales = floor($stockEnBodega / $product->unidades_por_caja);
                                $bajoStock = $cajasReales <= 5 && $cajasReales >= 0; // Bajo stock si tiene 5 o menos cajas
                            }
                        }
                    @endphp
                    <tr @if($bajoStock) style="background-color: #fff3cd; border-left: 4px solid #ffc107;" @endif>
                        <td>{{ $product->codigo }}</td>
                        <td><strong>{{ $product->nombre }}</strong></td>
                        <td>
                            @php
                                // Si el producto tiene contenedor asignado, mostrar la bodega del contenedor
                                $warehouseIdToShow = null;
                                if (isset($product->container_warehouse_id)) {
                                    $warehouseIdToShow = $product->container_warehouse_id;
                                } elseif ($selectedWarehouseId) {
                                    $warehouseIdToShow = $selectedWarehouseId;
                                }
                            @endphp
                            
                            @if($warehouseIdToShow)
                                @php
                                    $warehouse = $warehouses->where('id', $warehouseIdToShow)->first();
                                @endphp
                                @if($warehouse)
                                    <span style="font-weight: 500;">{{ $warehouse->nombre }}{{ $warehouse->ciudad ? ' - ' . $warehouse->ciudad : '' }}</span>
                                @else
                                    <span style="color: #666; font-style: italic;">-</span>
                                @endif
                            @else
                                <span style="color: #666; font-style: italic;">Todas</span>
                            @endif
                        </td>
                        <td>{{ $product->medidas ?? '-' }}</td>
                        <td>
                            @if(isset($product->container_reference))
                                {{-- Si el producto tiene contenedor asignado, mostrar su referencia --}}
                                <span style="font-size: 13px;">{{ $product->container_reference }}</span>
                            @else
                                @php
                                    $cantidadesPorContenedor = isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($product->id) 
                                        ? $productosCantidadesPorContenedor->get($product->id) 
                                        : collect();
                                @endphp
                                @if($cantidadesPorContenedor->count() > 0)
                                    <span style="font-size: 13px;">{{ $cantidadesPorContenedor->first()['container_reference'] ?? '-' }}</span>
                                @else
                                    <span style="color: #999; font-style: italic;">-</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if(isset($product->cajas_en_contenedor))
                                {{-- Si el producto tiene contenedor asignado, mostrar cajas del contenedor específico --}}
                                <strong @if($product->cajas_en_contenedor <= 5) style="color: #d32f2f; font-weight: bold;" @endif>
                                    {{ number_format($product->cajas_en_contenedor, 0) }} cajas
                                </strong>
                                @if($product->cajas_en_contenedor <= 5)
                                    <span style="color: #d32f2f; font-size: 11px; margin-left: 5px;" title="Bajo stock: 5 o menos cajas">⚠️</span>
                                @endif
                            @else
                                @if($product->tipo_medida === 'caja' && $cajasReales !== null)
                                    <strong @if($bajoStock) style="color: #d32f2f; font-weight: bold;" @endif>{{ number_format($cajasReales, 0) }} cajas</strong>
                                    @if($bajoStock)
                                        <span style="color: #d32f2f; font-size: 11px; margin-left: 5px;" title="Bajo stock: 5 o menos cajas">⚠️</span>
                                    @endif
                                @else
                                    -
                                @endif
                            @endif
                        </td>
                        <td>
                            @if(isset($product->laminas_en_contenedor))
                                {{-- Si el producto tiene contenedor asignado, mostrar láminas del contenedor específico --}}
                                <strong>{{ number_format($product->laminas_en_contenedor, 0) }} láminas</strong>
                            @else
                                <strong>{{ number_format($stockEnBodega, 0) }} láminas</strong>
                            @endif
                        </td>
                        <td>{{ $product->estado ? 'Activo' : 'Inactivo' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-box text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay productos registrados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sección de Contenedores -->
        @php
            $bodegasQueRecibenIds = is_array($bodegasQueRecibenContenedores) ? $bodegasQueRecibenContenedores : [];
        @endphp
        @if(!$selectedWarehouseId || in_array($selectedWarehouseId, $bodegasQueRecibenIds))
        <div class="section-title">
            <i class="bi bi-box me-2"></i>Contenedores
            @if($selectedWarehouseId)
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                </span>
            @endif
        </div>
        <!-- Búsqueda de contenedores -->
        <div class="mb-3" style="max-width: 400px;">
            <div style="position: relative;">
                <input type="text" id="search-containers" class="form-control" placeholder="Buscar contenedores..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;"></i>
            </div>
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table" id="containers-table">
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
                                            @if($product->medidas)
                                                <span style="color: #666; font-weight: normal;">- {{ $product->medidas }}</span>
                                            @endif
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
        <!-- Búsqueda de transferencias -->
        <div class="mb-3" style="max-width: 400px;">
            <div style="position: relative;">
                <input type="text" id="search-transfers" class="form-control" placeholder="Buscar transferencias..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;"></i>
            </div>
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table" id="transfers-table">
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

        <!-- Sección de Salidas -->
        <div class="section-title">
            <i class="bi bi-box-arrow-up-right me-2"></i>Salidas
            @if($selectedWarehouseId)
                <span style="font-size: 14px; font-weight: normal; color: #666;">
                    - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                </span>
            @endif
        </div>
        <!-- Búsqueda de salidas -->
        <div class="mb-3" style="max-width: 400px;">
            <div style="position: relative;">
                <input type="text" id="search-salidas" class="form-control" placeholder="Buscar salidas..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px;"></i>
            </div>
        </div>
        <div class="table-responsive-custom">
            <table class="stock-table" id="salidas-table">
                <thead>
                    <tr>
                        <th>No. Salida</th>
                        <th>Bodega</th>
                        <th>Fecha</th>
                        <th>A nombre de</th>
                        <th>NIT/Cédula</th>
                        <th>Productos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salidas as $salida)
                    <tr>
                        <td>{{ $salida->salida_number }}</td>
                        <td>{{ $salida->warehouse->nombre ?? '-' }}</td>
                        <td>{{ $salida->fecha->format('d/m/Y') }}</td>
                        <td>{{ $salida->a_nombre_de }}</td>
                        <td>{{ $salida->nit_cedula }}</td>
                        <td>
                            @foreach($salida->products as $prod)
                                <div style="font-size: 13px; margin-bottom: 4px;">
                                    <strong>{{ $prod->nombre }}</strong>
                                    <span style="color: #666;">({{ $prod->pivot->quantity }} láminas)</span>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-box-arrow-up-right text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay salidas registradas.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Búsqueda en tabla de productos
    const searchProducts = document.getElementById('search-products-stock');
    const productsTable = document.getElementById('products-stock-table');
    if (searchProducts && productsTable) {
        searchProducts.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = productsTable.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Búsqueda en tabla de contenedores
    const searchContainers = document.getElementById('search-containers');
    const containersTable = document.getElementById('containers-table');
    if (searchContainers && containersTable) {
        searchContainers.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = containersTable.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Búsqueda en tabla de transferencias
    const searchTransfers = document.getElementById('search-transfers');
    const transfersTable = document.getElementById('transfers-table');
    if (searchTransfers && transfersTable) {
        searchTransfers.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = transfersTable.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
    
    // Búsqueda en tabla de salidas
    const searchSalidas = document.getElementById('search-salidas');
    const salidasTable = document.getElementById('salidas-table');
    if (searchSalidas && salidasTable) {
        searchSalidas.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = salidasTable.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});
</script>

