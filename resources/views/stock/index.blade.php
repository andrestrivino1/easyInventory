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
                    $isAdmin = $user && $user->rol === 'admin';
                    $isFuncionario = $user && $user->rol === 'funcionario';
                    $isCliente = $user && $user->rol === 'clientes';
                    $canExport = $user && $user->rol === 'admin'; // Solo admin puede descargar
                @endphp
                @if($isAdmin)
                <form method="GET" action="{{ route('stock.index') }}" id="filter-form" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label for="warehouse_id">Bodega:</label>
                        <select name="warehouse_id" id="warehouse_id" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px;">
                            <option value="">Todas los bodegas</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ $selectedWarehouseId == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->nombre }}{{ $warehouse->ciudad ? ' - ' . $warehouse->ciudad : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" style="background: #0066cc; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500;">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                    <a href="{{ route('stock.index') }}" style="background: #6c757d; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none;">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </form>
                @elseif(($isFuncionario || $isCliente) && $warehouses->count() > 1)
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
                        @if(($isFuncionario || $isCliente) && $warehouses->count() > 0)
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
        <div id="products-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="bi bi-box-seam me-2"></i>Productos
                @if($selectedWarehouseId)
                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                        - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                    </span>
                @endif
            </div>
            @if($canExport)
            <a href="{{ route('stock.export-excel-products', request()->query()) }}" class="btn btn-sm btn-success" style="padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white; font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
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
                            // Bajo stock: cuando tiene 3 o menos cajas (mínimo recomendado es 4 cajas)
                            $bajoStock = $cajasReales <= 3 && $cajasReales >= 0;
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
                                // Bajo stock: cuando tiene 3 o menos cajas (mínimo recomendado es 4 cajas)
                                $bajoStock = $cajasReales <= 3 && $cajasReales >= 0;
                            }
                        }
                    @endphp
                    <tr @if($bajoStock) style="background-color: #fff3e0; border-left: 4px solid #ff9800;" @endif>
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
                                {{-- Si el producto tiene contenedor asignado (puede ser uno o varios unificados), mostrar todas las referencias --}}
                                <span style="font-size: 13px;" title="{{ $product->container_reference }}">{{ $product->container_reference }}</span>
                            @else
                                @php
                                    $cantidadesPorContenedor = isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($product->id) 
                                        ? $productosCantidadesPorContenedor->get($product->id) 
                                        : collect();
                                @endphp
                                @if($cantidadesPorContenedor->count() > 0)
                                    {{-- Mostrar todas las referencias de contenedores unificadas --}}
                                    @php
                                        $containerRefs = $cantidadesPorContenedor->pluck('container_reference')->unique()->filter()->implode(', ');
                                    @endphp
                                    <span style="font-size: 13px;" title="{{ $containerRefs }}">{{ $containerRefs ?: '-' }}</span>
                                @else
                                    <span style="color: #999; font-style: italic;">-</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if(isset($product->cajas_en_contenedor))
                                {{-- Si el producto tiene contenedor asignado, mostrar cajas del contenedor específico --}}
                                <strong @if($product->cajas_en_contenedor <= 3) style="color: #ff9800; font-weight: bold;" @endif>
                                    {{ number_format($product->cajas_en_contenedor, 0) }} cajas
                                </strong>
                                @if($product->cajas_en_contenedor <= 3)
                                    <span style="color: #ff9800; font-size: 11px; margin-left: 5px;" title="Bajo stock: 3 o menos cajas (mínimo recomendado: 4 cajas)">⚠️</span>
                                @endif
                            @else
                                @if($product->tipo_medida === 'caja' && $cajasReales !== null)
                                    <strong @if($bajoStock) style="color: #ff9800; font-weight: bold;" @endif>{{ number_format($cajasReales, 0) }} cajas</strong>
                                    @if($bajoStock)
                                        <span style="color: #ff9800; font-size: 11px; margin-left: 5px;" title="Bajo stock: 3 o menos cajas (mínimo recomendado: 4 cajas)">⚠️</span>
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
        
        <!-- Paginación -->
        <div id="products-pagination">
        @if(method_exists($products, 'total') && $products->total() > $products->perPage())
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="color: #666; font-size: 14px;">
                    Mostrando {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} de {{ $products->total() }} productos
                </div>
                <div>
                    {!! $products->appends(request()->query())->links() !!}
                </div>
            </div>
        </div>
        @elseif(method_exists($products, 'total') && $products->total() > 0)
        <div style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            Mostrando {{ $products->total() }} producto(s)
        </div>
        @endif
        </div>
        </div>

        <!-- Sección de Contenedores -->
        @php
            $bodegasQueRecibenIds = is_array($bodegasQueRecibenContenedores) ? $bodegasQueRecibenContenedores : [];
        @endphp
        @if(!$selectedWarehouseId || in_array($selectedWarehouseId, $bodegasQueRecibenIds))
        <div id="containers-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="bi bi-box me-2"></i>Contenedores
                @if($selectedWarehouseId)
                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                        - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                    </span>
                @endif
            </div>
            @if($canExport)
            <a href="{{ route('stock.export-excel-containers', request()->query()) }}" class="btn btn-sm btn-success" style="padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white; font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
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
                        <th>Bodega</th>
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
                            @if($container->warehouse)
                                <span style="font-weight: 500;">{{ $container->warehouse->nombre }}{{ $container->warehouse->ciudad ? ' - ' . $container->warehouse->ciudad : '' }}</span>
                            @else
                                <span style="color: #666; font-style: italic;">-</span>
                            @endif
                        </td>
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-box text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No hay contenedores registrados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación Contenedores -->
        <div id="containers-pagination">
        @if(method_exists($containers, 'total') && $containers->total() > $containers->perPage())
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="color: #666; font-size: 14px;">
                    Mostrando {{ $containers->firstItem() ?? 0 }} - {{ $containers->lastItem() ?? 0 }} de {{ $containers->total() }} contenedores
                </div>
                <div>
                    {!! $containers->appends(request()->query())->links() !!}
                </div>
            </div>
        </div>
        @elseif(method_exists($containers, 'total') && $containers->total() > 0)
        <div style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            Mostrando {{ $containers->total() }} contenedor(es)
        </div>
        @endif
        </div>
        </div>
        @endif

        <!-- Sección de Transferencias -->
        <div id="transfers-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="bi bi-arrow-left-right me-2"></i>Transferencias
                @if($selectedWarehouseId)
                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                        - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                    </span>
                @endif
            </div>
            @if($canExport)
            <a href="{{ route('stock.export-excel-transfers', request()->query()) }}" class="btn btn-sm btn-success" style="padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white; font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            @endif
        </div>
        <!-- Filtro de fechas para transferencias -->
        <form method="GET" action="{{ route('stock.index') }}" id="transfers-date-filter" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            @foreach(request()->except(['transfers_date_from', 'transfers_date_to', 'transfers_page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $k => $v)
                        <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="transfers_date_from" style="font-weight: 500; color: #333; margin: 0;">Desde:</label>
                <input type="date" name="transfers_date_from" id="transfers_date_from" value="{{ request('transfers_date_from') }}" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="transfers_date_to" style="font-weight: 500; color: #333; margin: 0;">Hasta:</label>
                <input type="date" name="transfers_date_to" id="transfers_date_to" value="{{ request('transfers_date_to') }}" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="submit" style="background: #0066cc; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 13px;">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            @if(request('transfers_date_from') || request('transfers_date_to'))
            <a href="{{ route('stock.index', array_merge(request()->except(['transfers_date_from', 'transfers_date_to', 'transfers_page']))) }}" style="background: #6c757d; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; font-size: 13px;">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
            @endif
        </form>
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
                        <td>
                            @if($transfer->from)
                                {{ $transfer->from->nombre }}{{ $transfer->from->ciudad ? ' - ' . $transfer->from->ciudad : '' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($transfer->to)
                                {{ $transfer->to->nombre }}{{ $transfer->to->ciudad ? ' - ' . $transfer->to->ciudad : '' }}
                            @else
                                -
                            @endif
                        </td>
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
        
        <!-- Paginación Transferencias -->
        <div id="transfers-pagination">
        @if(method_exists($transferOrders, 'total') && $transferOrders->total() > $transferOrders->perPage())
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="color: #666; font-size: 14px;">
                    Mostrando {{ $transferOrders->firstItem() ?? 0 }} - {{ $transferOrders->lastItem() ?? 0 }} de {{ $transferOrders->total() }} transferencias
                </div>
                <div>
                    {!! $transferOrders->appends(request()->query())->links() !!}
                </div>
            </div>
        </div>
        @elseif(method_exists($transferOrders, 'total') && $transferOrders->total() > 0)
        <div style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            Mostrando {{ $transferOrders->total() }} transferencia(s)
        </div>
        @endif
        </div>
        </div>

        <!-- Sección de Salidas -->
        <div id="salidas-section">
        <div class="section-title" style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <i class="bi bi-box-arrow-up-right me-2"></i>Salidas
                @if($selectedWarehouseId)
                    <span style="font-size: 14px; font-weight: normal; color: #666;">
                        - {{ $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '' }}
                    </span>
                @endif
            </div>
            @if($canExport)
            <a href="{{ route('stock.export-excel-salidas', request()->query()) }}" class="btn btn-sm btn-success" style="padding: 6px 12px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white; font-size: 12px;">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            @endif
        </div>
        <!-- Filtro de fechas para salidas -->
        <form method="GET" action="{{ route('stock.index') }}" id="salidas-date-filter" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            @foreach(request()->except(['salidas_date_from', 'salidas_date_to', 'salidas_page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $k => $v)
                        <input type="hidden" name="{{ $key }}[{{ $k }}]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="salidas_date_from" style="font-weight: 500; color: #333; margin: 0;">Desde:</label>
                <input type="date" name="salidas_date_from" id="salidas_date_from" value="{{ request('salidas_date_from') }}" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <label for="salidas_date_to" style="font-weight: 500; color: #333; margin: 0;">Hasta:</label>
                <input type="date" name="salidas_date_to" id="salidas_date_to" value="{{ request('salidas_date_to') }}" style="padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px;">
            </div>
            <button type="submit" style="background: #0066cc; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; font-size: 13px;">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
            @if(request('salidas_date_from') || request('salidas_date_to'))
            <a href="{{ route('stock.index', array_merge(request()->except(['salidas_date_from', 'salidas_date_to', 'salidas_page']))) }}" style="background: #6c757d; color: white; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; font-size: 13px;">
                <i class="bi bi-x-circle"></i> Limpiar
            </a>
            @endif
        </form>
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
                        <td>
                            @if($salida->warehouse)
                                {{ $salida->warehouse->nombre }}{{ $salida->warehouse->ciudad ? ' - ' . $salida->warehouse->ciudad : '' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $salida->fecha->format('d/m/Y') }}</td>
                        <td>{{ $salida->a_nombre_de }}</td>
                        <td>{{ $salida->nit_cedula }}</td>
                        <td>
                            @php
                                $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
                                $isBuenaventura = in_array($salida->warehouse_id, $bodegasBuenaventuraIds);
                            @endphp
                            @foreach($salida->products as $prod)
                                <div style="font-size: 13px; margin-bottom: 4px;">
                                    <strong>{{ $prod->nombre }}</strong>
                                    @if($isBuenaventura && $prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0)
                                        @php
                                            $cajas = floor($prod->pivot->quantity / $prod->unidades_por_caja);
                                        @endphp
                                        <span style="color: #666;">({{ $cajas }} cajas)</span>
                                    @else
                                        <span style="color: #666;">({{ $prod->pivot->quantity }} láminas)</span>
                                    @endif
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
        
        <!-- Paginación Salidas -->
        <div id="salidas-pagination">
        @if(method_exists($salidas, 'total') && $salidas->total() > $salidas->perPage())
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="color: #666; font-size: 14px;">
                    Mostrando {{ $salidas->firstItem() ?? 0 }} - {{ $salidas->lastItem() ?? 0 }} de {{ $salidas->total() }} salidas
                </div>
                <div>
                    {!! $salidas->appends(request()->query())->links() !!}
                </div>
            </div>
        </div>
        @elseif(method_exists($salidas, 'total') && $salidas->total() > 0)
        <div style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            Mostrando {{ $salidas->total() }} salida(s)
        </div>
        @endif
        </div>
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
    
    // Usar delegación de eventos para paginación AJAX (funciona con contenido dinámico)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link || !link.href) return;
        
        const url = new URL(link.href);
        const pathname = url.pathname;
        const currentPath = window.location.pathname;
        
        // Verificar que es una petición a la misma página de stock
        if (pathname !== currentPath) return;
        
        // Verificar si es un link de paginación
        const isProductsPage = url.searchParams.has('products_page');
        const isContainersPage = url.searchParams.has('containers_page');
        const isTransfersPage = url.searchParams.has('transfers_page');
        const isSalidasPage = url.searchParams.has('salidas_page');
        
        if (!isProductsPage && !isContainersPage && !isTransfersPage && !isSalidasPage) return;
        
        e.preventDefault();
        
        // Determinar qué sección actualizar
        let sectionId, pageParam;
        if (isProductsPage) {
            sectionId = 'products-section';
            pageParam = 'products_page';
        } else if (isContainersPage) {
            sectionId = 'containers-section';
            pageParam = 'containers_page';
        } else if (isTransfersPage) {
            sectionId = 'transfers-section';
            pageParam = 'transfers_page';
        } else if (isSalidasPage) {
            sectionId = 'salidas-section';
            pageParam = 'salidas_page';
        }
        
        const section = document.getElementById(sectionId);
        if (!section) return;
        
        // Mostrar indicador de carga
        const loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'loading-' + sectionId;
        loadingIndicator.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px; border-radius: 8px; z-index: 9999;';
        loadingIndicator.innerHTML = '<div style="text-align: center;"><i class="bi bi-hourglass-split" style="font-size: 24px; display: block; margin-bottom: 10px;"></i>Cargando...</div>';
        document.body.appendChild(loadingIndicator);
        
        // Hacer petición AJAX
        fetch(link.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Crear un elemento temporal para parsear el HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Extraer solo la sección correspondiente del HTML recibido
            const newSection = tempDiv.querySelector('#' + sectionId);
            if (newSection) {
                // Reemplazar la sección completa
                section.outerHTML = newSection.outerHTML;
                
                // Re-inicializar búsquedas para la nueva sección
                initializeSearch();
                
                // Scroll suave hacia la sección actualizada
                const updatedSection = document.getElementById(sectionId);
                if (updatedSection) {
                    updatedSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            
            // Actualizar URL sin recargar
            window.history.pushState({}, '', link.href);
        })
        .catch(error => {
            console.error('Error en paginación AJAX:', error);
            // Si falla, hacer navegación normal
            window.location.href = link.href;
        })
        .finally(() => {
            // Remover indicador de carga
            const loading = document.getElementById('loading-' + sectionId);
            if (loading) {
                loading.remove();
            }
        });
    });
    
    function initializeSearch() {
        // Re-inicializar búsquedas después de actualizar contenido
        const searchProducts = document.getElementById('search-products-stock');
        const productsTable = document.getElementById('products-stock-table');
        if (searchProducts && productsTable) {
            // Remover listeners anteriores si existen
            const newSearchProducts = searchProducts.cloneNode(true);
            searchProducts.parentNode.replaceChild(newSearchProducts, searchProducts);
            
            newSearchProducts.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = productsTable.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
        
        const searchContainers = document.getElementById('search-containers');
        const containersTable = document.getElementById('containers-table');
        if (searchContainers && containersTable) {
            const newSearchContainers = searchContainers.cloneNode(true);
            searchContainers.parentNode.replaceChild(newSearchContainers, searchContainers);
            
            newSearchContainers.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = containersTable.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
        
        const searchTransfers = document.getElementById('search-transfers');
        const transfersTable = document.getElementById('transfers-table');
        if (searchTransfers && transfersTable) {
            const newSearchTransfers = searchTransfers.cloneNode(true);
            searchTransfers.parentNode.replaceChild(newSearchTransfers, searchTransfers);
            
            newSearchTransfers.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = transfersTable.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
        
        const searchSalidas = document.getElementById('search-salidas');
        const salidasTable = document.getElementById('salidas-table');
        if (searchSalidas && salidasTable) {
            const newSearchSalidas = searchSalidas.cloneNode(true);
            searchSalidas.parentNode.replaceChild(newSearchSalidas, searchSalidas);
            
            newSearchSalidas.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const rows = salidasTable.querySelectorAll('tbody tr');
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    }
});
</script>

