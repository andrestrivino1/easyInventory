@extends('layouts.app')

@section('content')
<style>
    .warehouse-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .warehouse-table th {
        background: #0066cc;
        color: white;
        text-align: left;
        padding: 12px;
        font-size: 15px;
    }
    .warehouse-table td {
        padding: 12px;
        border-bottom: 1px solid #e6e6e6;
    }
    .warehouse-table tr:hover {
        background: #f1f7ff;
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
    .actions button:hover { opacity: 0.85; }
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:850px;">
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
      timer: 3500,
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
      timer: 3300,
      timerProgressBar: true,
      showConfirmButton: false
    });
  @endif
});
</script>
@endif
      <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
        <a href="{{ route('warehouses.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva bodega</a>
      </div>
      <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Bodegas</h2>
      
      <!-- Campo de búsqueda -->
      <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
        <div style="position: relative; width: 100%;">
          <input type="text" id="search-warehouses" class="form-control" placeholder="Buscar bodegas..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
          <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
        </div>
      </div>
      
      <table class="warehouse-table" id="warehouses-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse($warehouses as $warehouse)
            <tr>
                <td>{{ $warehouse->nombre }}</td>
                <td>{{ $warehouse->direccion ?? '-' }}</td>
                <td class="actions">
                    <form action="{{ route('warehouses.edit', $warehouse) }}" method="GET" style="display:inline">
                        <button type="submit" class="btn-edit" style="margin-right:7px;">Editar</button>
                    </form>
                    <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" style="display:inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-delete">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-muted py-5">
                  <i class="bi bi-buildings text-secondary" style="font-size:2.2em;"></i><br>
                  <div class="mt-2">No existen bodegas registradas.</div>
                </td>
            </tr>
        @endforelse
        </tbody>
      </table>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-warehouses');
    const table = document.getElementById('warehouses-table');
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
});
</script>
