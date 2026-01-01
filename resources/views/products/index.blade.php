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
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
        @forelse($productos as $producto)
        <tr>
            <td>{{ $producto->codigo }}</td>
            <td><strong>{{ $producto->nombre }}</strong></td>
            <td>{{ $producto->medidas ?? '-' }}</td>
            <td>
                @if($producto->estado)
                    <span style="color: #28a745; font-weight: 500;">Activo</span>
                @else
                    <span style="color: #dc3545; font-weight: 500;">Inactivo</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align: center; padding: 40px; color: #999;">
                <i class="bi bi-box" style="font-size: 3em; display: block; margin-bottom: 10px;"></i>
                <div>No hay productos registrados</div>
            </td>
        </tr>
        @endforelse
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
    
});
</script>
