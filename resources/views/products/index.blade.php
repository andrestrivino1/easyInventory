@extends('layouts.app')

@section('content')
<style>
    .inventory-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .inventory-table th {
        background: #0066cc;
        color: white;
        text-align: left;
        padding: 12px;
        font-size: 15px;
    }
    .inventory-table td {
        padding: 12px;
    }
    .inventory-table tbody tr:not(:last-child) td {
        border-bottom: 1px solid #e6e6e6;
    }
    .inventory-table tr:hover {
        background: #f1f7ff;
    }
    .low-stock {
        background: #ffdddd;
        color: #d60000;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 5px;
    }
    .ok-stock {
        background: #ddffdd;
        color: #007b00;
        font-weight: bold;
        padding: 4px 8px;
        border-radius: 5px;
    }
    .actions button {
        padding: 6px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        margin-right: 6px;
    }
    .btn-edit {
        background: #1d7ff0;
        color: white;
    }
    .btn-delete {
        background: #ffb3b3;
        color: #b30000;
    }
    .btn-view {
        background: #e6e6e6;
        color: #333;
    }
    .actions button:hover {
        opacity: 0.85;
    }
</style>

<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1300px;">
      @if(session('success') || session('error') || session('warning'))
<script>
document.addEventListener('DOMContentLoaded', function() {
  @if(session('success'))
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: '{{ session('success') }}',
      timer: 3300,
      timerProgressBar: true,
      showConfirmButton: false
    });
  @endif
  @if(session('error'))
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'error',
      title: '{{ session('error') }}',
      timer: 3800,
      timerProgressBar: true,
      showConfirmButton: false
    });
  @endif
  @if(session('warning'))
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'warning',
      title: '{{ session('warning') }}',
      timer: 3500,
      timerProgressBar: true,
      showConfirmButton: false
    });
  @endif
});
</script>
@endif
      @if(isset($canCreateProducts) && $canCreateProducts)
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('products.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo Producto</a>
      </div>
      @endif
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold">Inventario de Productos</h2>
      
      <!-- Campo de búsqueda -->
      <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
        <div style="position: relative; width: 100%;">
          <input type="text" id="search-products" class="form-control" placeholder="Buscar productos..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
          <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
        </div>
      </div>
      
      <table class="inventory-table" id="products-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Medidas</th>
                <th>Contenedor</th>
                <th>Cajas</th>
                <th>Laminas</th>
                <th>Bodega</th>
                <th>Estado</th>
                @if(isset($canCreateProducts) && $canCreateProducts)
                <th>Acciones</th>
                @endif
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $producto)
@php
    // Asegurar que el producto tenga los datos más recientes
    $producto->refresh();
    // Calcular cajas para verificar bajo stock
    $cajasReales = null;
    $bajoStock = false;
    if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
        $cajasReales = floor($producto->stock / $producto->unidades_por_caja);
        $bajoStock = $cajasReales <= 5 && $cajasReales >= 0; // Bajo stock si tiene 5 o menos cajas
    }
@endphp
<tr @if($bajoStock) style="background-color: #fff3cd; border-left: 4px solid #ffc107;" @endif>
    <td>{{ $producto->codigo }}</td>
    <td>{{ $producto->nombre }}</td>
    <td>{{ $producto->medidas ?? '-' }}</td>
    <td>
        @php
            $cantidadesPorContenedor = isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($producto->id) 
                ? $productosCantidadesPorContenedor->get($producto->id) 
                : collect();
        @endphp
        @php
            $cantidadesPorContenedor = isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($producto->id) 
                ? $productosCantidadesPorContenedor->get($producto->id) 
                : collect();
        @endphp
        @if(isset($ID_PABLO_ROJAS) && $producto->almacen_id == $ID_PABLO_ROJAS && $cantidadesPorContenedor->count() > 1)
            {{-- Si hay múltiples contenedores, mostrar cada uno en una línea --}}
            <div style="display: flex; flex-direction: column; gap: 4px;">
                @foreach($cantidadesPorContenedor as $containerData)
                    <span style="font-size: 13px;">{{ $containerData['container_reference'] }}</span>
                @endforeach
            </div>
        @else
            {{-- Si hay un solo contenedor o no hay desglose, mostrar el contenedor normal --}}
            @php
                // Contenedores directos del producto (solo para Pablo Rojas)
                $directContainers = $producto->containers;
                // Contenedores de origen desde transferencias recibidas
                $transferContainers = isset($productosContenedoresOrigen) && $productosContenedoresOrigen->has($producto->id) 
                    ? $productosContenedoresOrigen->get($producto->id) 
                    : collect();
                // Combinar ambos (sin duplicados)
                $allContainers = $directContainers->merge($transferContainers)->unique('id');
            @endphp
            @if($allContainers->count() > 0)
                <span style="font-size: 13px;">{{ $allContainers->first()->reference }}</span>
            @else
                <span style="color: #999; font-style: italic;">-</span>
            @endif
        @endif
    </td>
    <td>
        @php
            $cantidadesPorContenedor = isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($producto->id) 
                ? $productosCantidadesPorContenedor->get($producto->id) 
                : collect();
            // Reutilizar el cálculo de cajas ya hecho arriba
        @endphp
        @if($producto->tipo_medida === 'caja' && $cajasReales !== null)
            @if(isset($ID_PABLO_ROJAS) && $producto->almacen_id == $ID_PABLO_ROJAS && $cantidadesPorContenedor->count() > 1)
                {{-- Si hay múltiples contenedores, mostrar solo las cantidades sin el nombre del contenedor --}}
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    @foreach($cantidadesPorContenedor as $containerData)
                        <div style="font-size: 13px;">
                            {{ number_format($containerData['cajas'], 0) }} cajas
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Si hay un solo contenedor o no hay desglose, mostrar el total --}}
                <strong @if($bajoStock) style="color: #d32f2f; font-weight: bold;" @endif>{{ number_format($cajasReales, 0) }} cajas</strong>
                @if($bajoStock)
                    <span style="color: #d32f2f; font-size: 11px; margin-left: 5px;" title="Bajo stock: 5 o menos cajas">⚠️</span>
                @endif
            @endif
        @else
            -
        @endif
    </td>
    <td>
        {{-- Para Pablo Rojas: mostrar stock real y cantidades por contenedor como referencia --}}
        {{-- Para otras bodegas: solo mostrar stock real (ya descontado por salidas) --}}
        @if(isset($ID_PABLO_ROJAS) && $producto->almacen_id == $ID_PABLO_ROJAS && $cantidadesPorContenedor->count() > 1)
            {{-- Si hay múltiples contenedores, mostrar solo las cantidades sin el nombre del contenedor --}}
            <div style="display: flex; flex-direction: column; gap: 4px;">
                @foreach($cantidadesPorContenedor as $containerData)
                    <div style="font-size: 13px;">
                        {{ number_format($containerData['laminas'], 0) }} láminas
                    </div>
                @endforeach
            </div>
        @else
            {{-- Si hay un solo contenedor o no hay desglose, mostrar el total --}}
            <strong>{{ number_format($producto->stock, 0) }} láminas</strong>
        @endif
    </td>
    <td>{{ $producto->almacen->nombre ?? '-' }}</td>
    <td>{{ $producto->estado ? 'Activo' : 'Inactivo' }}</td>
    @if(isset($canCreateProducts) && $canCreateProducts)
    <td class="actions">
        @if(isset($ID_PABLO_ROJAS) && $producto->almacen_id == $ID_PABLO_ROJAS)
            <form action="{{ route('products.edit', $producto) }}" method="GET" style="display:inline">
                <button type="submit" class="btn-edit" style="margin-right:7px;">Editar</button>
            </form>
        @endif
        @if(isset($productosConTransferencias) && $productosConTransferencias->get($producto->id))
            <span style="color: #999; font-size: 12px; font-style: italic;" title="Este producto tiene historial de transferencias recibidas. Solo puede desactivarse, no eliminarse.">
                <i class="bi bi-info-circle"></i> Desactivar
            </span>
        @elseif(isset($ID_PABLO_ROJAS) && $producto->almacen_id == $ID_PABLO_ROJAS)
            <form action="{{ route('products.destroy', $producto) }}" method="POST" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn-delete">Eliminar</button>
            </form>
        @endif
    </td>
    @endif
</tr>
@endforeach
        </tbody>
      </table>
    </div>
</div>
@endsection

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Búsqueda en tiempo real
    const searchInput = document.getElementById('search-products');
    const table = document.getElementById('products-table');
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Confirmación de eliminación
    document.querySelectorAll('form[action*="products"').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (form.querySelector('.btn-delete')) {
                e.preventDefault();
                Swal.fire({
                  title: '¿Seguro que deseas eliminar este producto?',
                  text: 'Esta acción no se puede deshacer.',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#d33',
                  cancelButtonColor: '#3085d6',
                  confirmButtonText: 'Sí, eliminar',
                  cancelButtonText: 'Cancelar',
                  reverseButtons: true
                }).then((result) => {
                  if (result.isConfirmed) {
                    form.submit();
                  }
                });
            }
        });
    });
});
</script>
