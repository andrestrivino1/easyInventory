@extends('layouts.app')

@section('content')
<style>
    body .form-bg {
        font-family: Arial, sans-serif;
        background: #eef2f7;
        display: flex;
        justify-content: center;
        padding-top: 40px;
        min-height: 87vh;
    }
    .form-container {
        background: white;
        width: 500px;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        margin:auto;
    }
    .form-container h2 {
        margin-top: 0;
        font-size: 20px;
        color: #222;
        margin-bottom: 18px;
        font-weight: 700;
    }
    .form-container label {
        display: block;
        margin-bottom: 4px;
        font-weight: bold;
        color: #333;
        text-align: left;
        margin-left:0;
        margin-right:0;
        max-width: none;
    }
    .form-container input {
        display: block;
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
        background: #f8fafc;
        transition:box-shadow .15s, border-color .15s;
        margin-left:0;
        margin-right:0;
        max-width:none;
        box-sizing: border-box;
    }
    .form-container select {
        display: block;
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
        background: #f8fafc;
        transition:box-shadow .15s, border-color .15s;
        box-sizing: border-box;
    }
    .form-container input:focus, .form-container select:focus {
        border-color: #4a8af4;
        outline: none;
        box-shadow: 0 0 4px rgba(74,138,244,0.14);
        background: #fff;
    }
    .actions {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        max-width: unset;
        margin-left: 0;
        margin-right: 0;
    }
    .btn-cancel {
        background: transparent;
        border: none;
        color: #4a8af4;
        font-size: 15px;
        cursor: pointer;
        text-decoration: underline;
        font-weight: 500;
        padding-left:0;
        padding-right:0;
    }
    .btn-save {
        background: #4a8af4;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        font-size: 15px;
    }
    .btn-save:hover {
        background: #2f6fe0;
    }
    .invalid-feedback {
        color: #d60000;
        font-size: 13px;
        margin-top: -10px;
        margin-bottom: 8px;
        margin-left: 0;
        margin-right: 0;
        max-width:none;
        text-align:left;
    }
</style>
<div class="form-bg">
  <div class="form-container">
    <h2>Nuevo producto</h2>
    <form action="{{ route('products.store') }}" method="POST" autocomplete="off">
      @csrf
      @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:15px;">{{ session('error') }}</div>
      @endif
      @php $user = Auth::user(); @endphp
      @if($errors->any())
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <script>Swal.fire({icon:'error',title:'Error',text:'{{ $errors->first() }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500})</script>
      @endif
      <div style="background:#e3f2fd; padding:12px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1565c0;">
        <strong>Nota:</strong> El stock y las unidades por caja se asignarán cuando agregues este producto a un contenedor.
      </div>
      <label for="almacen_id">Bodega*</label>
      @if($user->rol === 'admin')
      <select name="almacen_id" id="almacen_id" required>
        <option value="">Seleccione una bodega</option>
        @foreach($warehouses as $almacen)
          <option value="{{ $almacen->id }}" {{ old('almacen_id') == $almacen->id ? 'selected' : '' }}>{{ $almacen->nombre }}</option>
        @endforeach
      </select>
      @else
      <input type="hidden" name="almacen_id" value="{{ $user->almacen_id }}">
      <div style="margin-bottom:15px; padding:10px 14px; background:#f4f4f8; border-radius:5px; color:#333; font-size:14px;">
        {{ $user->almacen->nombre ?? 'Bodega asignada' }}
      </div>
      @endif
      @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="nombre">Nombre*</label>
      <input type="text" name="nombre" id="nombre" class="@error('nombre') is-invalid @enderror" placeholder="Nombre del producto" value="{{ old('nombre') }}" required>
      @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="medidas">Medidas</label>
      <input type="text" name="medidas" id="medidas" class="@error('medidas') is-invalid @enderror" placeholder="Ej: 100cm x 50cm x 2cm" value="{{ old('medidas') }}">
      @error('medidas') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="tipo_medida">Tipo de medida*</label>
      <select name="tipo_medida" id="tipo_medida" required>
        <option value="unidad">Unidades</option>
        <option value="caja" {{ old('tipo_medida') == 'caja' ? 'selected' : '' }}>Cajas</option>
      </select>
      @error('tipo_medida') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="estado">Estado</label>
      <select name="estado" id="estado">
          <option value="1" {{ old('estado', 1) == 1 ? 'selected' : '' }}>Activo</option>
          <option value="0" {{ old('estado', 1) == 0 ? 'selected' : '' }}>Inactivo</option>
      </select>
      <div class="actions">
        <a href="{{ route('products.index') }}" class="btn-cancel">Cancelar</a>
        <button type="submit" class="btn-save">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
  var wh = document.getElementById('almacen_id');
  var tipoSelect = document.getElementById('tipo_medida');
  if(wh && tipoSelect){
    wh.addEventListener('change', function() {
      var pabloRojasOption = null;
      for(let opt of wh.options) {
        if(opt.innerText.trim().toLowerCase() === 'pablo rojas' && opt.value) pabloRojasOption = opt.value;
      }
      if (wh.value === pabloRojasOption) {
        if(tipoSelect.value !== 'caja') {
        tipoSelect.value = 'caja';
          if(typeof Swal !== 'undefined') {
            Swal.fire({icon:'info',title:'Solo se permiten Cajas en Pablo Rojas',toast:true,position:'top-end',showConfirmButton:false,timer:2000});
          }
        }
        tipoSelect.options[0].disabled = true;
      } else {
        tipoSelect.options[0].disabled = false;
      }
    });
    // Ejecutar al cargar
    wh.dispatchEvent(new Event('change'));
  }
});
</script>
