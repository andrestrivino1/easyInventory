@extends('layouts.app')

@section('content')
<style>
    .table-responsive-custom {
        overflow-x: auto;
        max-width: 100%;
        padding-bottom: 6px;
    }
    .traceability-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        font-size: 13px;
        table-layout: auto;
        min-width: 1400px;
    }
    .traceability-table th, .traceability-table td {
        padding: 10px 8px;
        word-break: break-word;
        vertical-align: top;
    }
    .traceability-table th {
        white-space: nowrap;
    }
    .traceability-table td {
        max-width: 200px;
    }
    .traceability-table tbody tr:not(:last-child) td {
        border-bottom: 1px solid #e6e6e6;
    }
    .traceability-table th {
        background: #0066cc;
        color: white;
        font-size: 14px;
        letter-spacing: 0.1px;
        white-space: nowrap;
    }
    .traceability-table td {
        font-size: 13.2px;
    }
    .traceability-table tr:hover {
        background: #f1f7ff;
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
        margin-bottom: 5px;
        display: block;
        font-size: 13px;
    }
    .filter-section select,
    .filter-section input {
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
        min-width: 180px;
    }
    .filter-row {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
        margin-bottom: 15px;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 180px;
    }
    .filter-group button {
        white-space: nowrap;
    }
    .export-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    .badge-entrada {
        background: #28a745;
        color: white;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: bold;
    }
    .badge-salida {
        background: #dc3545;
        color: white;
        padding: 3px 10px;
        border-radius: 5px;
        font-size: 12px;
        font-weight: bold;
    }
    .quantity-positive {
        color: #28a745;
        font-weight: bold;
    }
    .quantity-negative {
        color: #dc3545;
        font-weight: bold;
    }
</style>

<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1400px;">
        <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Trazabilidad de Productos</h2>
        
        <!-- Filtros y botones de exportación -->
        <div class="filter-section">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 15px;">
                <form method="GET" action="{{ route('traceability.index') }}" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; flex: 1;">
                    <div class="filter-group">
                        <label for="product_id">Producto:</label>
                        <select name="product_id" id="product_id">
                            <option value="">Todos los productos</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ $selectedProductId == $product->id ? 'selected' : '' }}>
                                    {{ $product->nombre }} ({{ $product->codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @php
                        $user = Auth::user();
                        $isAdmin = $user && in_array($user->rol, ['admin', 'secretaria']);
                        $isFuncionario = $user && $user->rol === 'funcionario';
                        $canExport = $user && $user->rol === 'admin'; // Solo admin puede descargar
                    @endphp
                    @if($isAdmin && !$isFuncionario)
                    <div class="filter-group">
                        <label for="warehouse_id">Almacén:</label>
                        <select name="warehouse_id" id="warehouse_id">
                            <option value="">Todos los almacenes</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ $selectedWarehouseId == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="filter-group">
                        <label>Almacén:</label>
                        <span style="font-weight: 500; color: #333; padding: 8px 12px; background: #f5f5f5; border-radius: 4px; display: inline-block;">{{ $isFuncionario ? 'Buenaventura' : ($user->almacen->nombre ?? 'N/A') }}</span>
                        <input type="hidden" name="warehouse_id" value="{{ $isFuncionario ? 1 : $user->almacen_id }}">
                    </div>
                    @endif
                    <div class="filter-group">
                        <label for="date_from">Fecha Desde:</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}">
                    </div>
                    <div class="filter-group">
                        <label for="date_to">Fecha Hasta:</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}">
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary" style="padding: 8px 16px; border-radius: 6px; font-weight: 500; white-space: nowrap;">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                    </div>
                </form>
                @if($canExport)
                <div style="display: flex; gap: 10px; align-items: flex-end;">
                    <a href="{{ route('traceability.export-pdf', request()->query()) }}" class="btn btn-primary" style="padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: 500; white-space: nowrap;">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Descargar PDF
                    </a>
                    <a href="{{ route('traceability.export-excel', request()->query()) }}" class="btn btn-success" style="padding: 8px 16px; text-decoration: none; border-radius: 6px; font-weight: 500; background: #28a745; color: white; white-space: nowrap;">
                        <i class="bi bi-file-earmark-excel me-2"></i>Descargar Excel
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Tabla de movimientos -->
        <div class="table-responsive-custom">
            <table class="traceability-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Producto</th>
                        <th>Código</th>
                        <th>Cajas</th>
                        <th>Laminas</th>
                        <th>Almacén</th>
                        <th>Referencia</th>
                        <th>Tipo Referencia</th>
                        <th>Destino</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement['date']->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($movement['type'] === 'entrada')
                                <span class="badge-entrada">{{ $movement['type_label'] }}</span>
                            @else
                                <span class="badge-salida">{{ $movement['type_label'] }}</span>
                            @endif
                        </td>
                        <td><strong>{{ $movement['product_name'] }}</strong></td>
                        <td>{{ $movement['product_code'] }}</td>
                        <td style="text-align: center;">
                            @if(isset($movement['boxes']) && $movement['boxes'] !== null)
                                {{ number_format($movement['boxes'], 0) }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="{{ $movement['quantity'] > 0 ? 'quantity-positive' : 'quantity-negative' }}">
                            {{ $movement['quantity'] > 0 ? '+' : '' }}{{ number_format($movement['quantity'], 0) }}
                        </td>
                        <td>{{ $movement['warehouse_name'] }}</td>
                        <td><strong>{{ $movement['reference'] }}</strong></td>
                        <td>{{ $movement['reference_type'] }}</td>
                        <td>{{ $movement['destination_warehouse'] ?? '-' }}</td>
                        <td>{{ $movement['note'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="bi bi-search text-secondary" style="font-size:2.2em;"></i><br>
                            <div class="mt-2">No se encontraron movimientos con los filtros seleccionados.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        @if($movements->total() > $movements->perPage())
        <div style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="color: #666; font-size: 14px;">
                    Mostrando {{ $movements->firstItem() ?? 0 }} - {{ $movements->lastItem() ?? 0 }} de {{ $movements->total() }} registros
                </div>
                <div>
                    {!! $movements->appends(request()->query())->links() !!}
                </div>
            </div>
        </div>
        @elseif($movements->total() > 0)
        <div style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); margin-top: 20px; text-align: center; color: #666; font-size: 14px;">
            Mostrando {{ $movements->total() }} registro(s)
        </div>
        @endif
    </div>
</div>
@endsection

