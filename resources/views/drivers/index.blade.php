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
.actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    align-items: center;
    justify-content: center;
    min-width: 120px;
}
.actions form {
    display: inline-block;
    margin: 0;
    width: 100%;
}
.actions button, .actions a {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: block;
    text-decoration: none;
    white-space: nowrap;
    width: 100%;
    text-align: center;
    box-sizing: border-box;
}
.btn-edit {background: #1d7ff0; color: white;}
.btn-delete {background: #ffb3b3; color: #b30000;}
.actions button:hover, .actions a:hover { opacity: 0.85; }
.driver-photo, .vehicle-photo {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s;
    display: block;
}
.driver-photo:hover, .vehicle-photo:hover {
    transform: scale(1.1);
    border-color: #0066cc;
    z-index: 10;
    position: relative;
}
.photo-container {
    justify-content: center;
    align-items: center;
    padding: 5px;
}
.photo-placeholder {
    width: 60px;
    height: 60px;
    background: #f0f0f0;
    border-radius: 6px;
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    font-size: 11px;
    text-align: center;
    padding: 5px;
    box-sizing: border-box;
}
</style>
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
  <div class="mx-auto" style="max-width:1050px;">
    <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
      <a href="{{ route('drivers.create') }}" class="btn btn-primary rounded-pill px-4" style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo conductor</a>
    </div>
    <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Conductores registrados</h2>
    
    <!-- Campo de búsqueda -->
    <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
      <div style="position: relative; width: 100%;">
        <input type="text" id="search-drivers" class="form-control" placeholder="Buscar conductores..." style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
        <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
      </div>
    </div>
    
    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="driver-table" id="drivers-table">
      <thead>
        <tr>
          <th>Foto Conductor</th>
          <th>Nombre</th>
          <th>Cédula</th>
          <th>Teléfono</th>
          <th>Placa</th>
          <th>Foto Vehículo</th>
          <th>Propietario</th>
          <th>Fecha Seg. Social</th>
          <th>Estado</th>
          <th style="min-width:120px">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($drivers as $driver)
        <tr>
          <td class="photo-container">
            @if($driver->photo_path)
              <img src="{{ route('drivers.photo', $driver) }}" alt="Foto de {{ $driver->name }}" class="driver-photo" onclick="window.open('{{ route('drivers.photo', $driver) }}', '_blank')">
            @else
              <div class="photo-placeholder">Sin foto</div>
            @endif
          </td>
          <td>{{ $driver->name }}</td>
          <td>{{ $driver->identity }}</td>
          <td>{{ $driver->phone }}</td>
          <td>{{ $driver->vehicle_plate }}</td>
          <td class="photo-container">
            @if($driver->vehicle_photo_path)
              <img src="{{ route('drivers.vehicle-photo', $driver) }}" alt="Foto vehículo {{ $driver->vehicle_plate }}" class="vehicle-photo" onclick="window.open('{{ route('drivers.vehicle-photo', $driver) }}', '_blank')">
            @else
              <div class="photo-placeholder">Sin foto</div>
            @endif
          </td>
          <td>{{ $driver->vehicle_owner ?? '-' }}</td>
          <td>
            @if($driver->social_security_date)
              @php
                $securityDate = \Carbon\Carbon::parse($driver->social_security_date);
                $isExpired = \Carbon\Carbon::today()->greaterThan($securityDate);
              @endphp
              <span style="color: {{ $isExpired ? '#dc3545' : '#333' }}; font-weight: {{ $isExpired ? 'bold' : 'normal' }};">
                {{ $securityDate->format('d/m/Y') }}
              </span>
              @if($driver->social_security_pdf)
                <a href="{{ route('drivers.social-security-pdf', $driver) }}" target="_blank" style="margin-left: 5px; color: #dc3545;" title="Ver PDF">
                  <i class="bi bi-file-pdf"></i>
                </a>
              @endif
              @if($isExpired)
                <span style="color: #dc3545; margin-left: 5px;" title="Seguridad social vencida">
                  <i class="bi bi-exclamation-triangle"></i>
                </span>
              @endif
            @else
              -
            @endif
          </td>
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
            <form action="{{ route('drivers.destroy', $driver) }}" method="POST">
              @csrf @method('DELETE')
              <button type="submit" class="btn-delete">Desactivar</button>
            </form>
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="10" class="text-center text-muted py-4">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-drivers');
    const table = document.getElementById('drivers-table');
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
