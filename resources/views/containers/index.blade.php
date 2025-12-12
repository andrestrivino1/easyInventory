@extends('layouts.app')

@section('content')
<style>
.container-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}
.container-table th {
    background: #0066cc;
    color: white;
    text-align: left;
    padding: 12px;
    font-size: 15px;
}
.container-table td {
    padding: 12px;
    border-bottom: 1px solid #e6e6e6;
}
.container-table tr:hover {
    background: #f1f7ff;
}
.actions button, .actions a {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    margin-right: 6px;
    display:inline-block;
    text-decoration:none;
}
.btn-edit { background: #1d7ff0; color: white; }
.btn-delete { background: #ffb3b3; color: #b30000; }
.actions button:hover, .actions a:hover { opacity: 0.85; }
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:800px;">
        <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
            <a href="{{ route('containers.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo contenedor</a>
        </div>
        <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Contenedores registrados</h2>
        @if(session('success') || session('error') || session('warning'))
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <table class="container-table">
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Producto</th>
                    <th>Cajas</th>
                    <th>Láminas</th>
                    <th>Observación</th>
                    <th style="min-width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($containers as $container)
                <tr>
                    <td>{{ $container->reference }}</td>
                    <td>{{ $container->product_name }}</td>
                    <td>{{ $container->boxes }}</td>
                    <td>{{ $container->boxes * $container->sheets_per_box }}</td>
                    <td>{{ $container->note }}</td>
                    <td class="actions">
                        <a href="{{ route('containers.edit', $container) }}" class="btn-edit">Editar</a>
                        <form action="{{ route('containers.destroy', $container) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-delete">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4"><i class="bi bi-box text-secondary" style="font-size:2.2em;"></i><br><div class="mt-2">No hay contenedores registrados.</div></td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
