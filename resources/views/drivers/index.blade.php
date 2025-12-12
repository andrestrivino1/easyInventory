@extends('layouts.app')

@section('content')
<style>
.driver-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
}
.driver-table th {
    background: #0066cc;
    color: white;
    text-align: left;
    padding: 12px;
    font-size: 15px;
}
.driver-table td {
    padding: 12px;
    border-bottom: 1px solid #e6e6e6;
}
.driver-table tr:hover {
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
.btn-edit {background: #1d7ff0; color: white;}
.btn-delete {background: #ffb3b3; color: #b30000;}
.actions button:hover, .actions a:hover { opacity: 0.85; }
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
  <div class="mx-auto" style="max-width:1050px;">
    <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
      <a href="{{ route('drivers.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo conductor</a>
    </div>
    <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Conductores registrados</h2>
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="driver-table">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Cédula</th>
          <th>Teléfono</th>
          <th>Placa</th>
          <th>Estado</th>
          <th style="min-width:120px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($drivers as $driver)
        <tr>
          <td>{{ $driver->name }}</td>
          <td>{{ $driver->identity }}</td>
          <td>{{ $driver->phone }}</td>
          <td>{{ $driver->vehicle_plate }}</td>
          <td>
            @if($driver->active)
              <span class="badge bg-success">Activo</span>
            @else
              <span class="badge bg-secondary">Inactivo</span>
            @endif
          </td>
          <td class="actions">
            <a href="{{ route('drivers.edit', $driver) }}" class="btn-edit">Editar</a>
            @if($driver->active)
            <form action="{{ route('drivers.destroy', $driver) }}" method="POST" style="display:inline;">
              @csrf @method('DELETE')
              <button type="submit" class="btn-delete">Desactivar</button>
            </form>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="bi bi-truck text-secondary" style="font-size:2.2em;"></i><br>
            <div class="mt-2">No existen conductores registrados.</div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
