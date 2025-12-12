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
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('products.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo Producto</a>
      </div>
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold">Inventario de Productos</h2>
      <table class="inventory-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Contenedor</th>
                <th>Stock (Cajas)</th>
                <th>Unidades</th>
                <th>Almacén</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $producto)
<tr>
    <td>{{ $producto->codigo }}</td>
    <td>{{ $producto->nombre }}</td>
    <td>{{ $producto->container ? $producto->container->reference : '-' }}</td>
    <td>
        @if($producto->tipo_medida === 'caja')
            {{ $producto->cajas }}
        @else
            -
        @endif
    </td>
    <td>{{ $producto->stock }}</td>
    <td>{{ $producto->almacen->nombre ?? '-' }}</td>
    <td>{{ $producto->estado ? 'Activo' : 'Inactivo' }}</td>
    <td class="actions">
        <form action="{{ route('products.edit', $producto) }}" method="GET" style="display:inline">
            <button type="submit" class="btn-edit" style="margin-right:7px;">Editar</button>
        </form>
        <form action="{{ route('products.destroy', $producto) }}" method="POST" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit" class="btn-delete">Eliminar</button>
        </form>
    </td>
</tr>
@endforeach
        </tbody>
      </table>
    </div>
</div>
@endsection

<script>
document.addEventListener("DOMContentLoaded", function() {
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
