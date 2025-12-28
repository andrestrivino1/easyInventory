@extends('layouts.app')

@section('content')
<style>
.form-container {background:#fff;max-width:500px;margin:50px auto 0 auto;padding:30px 26px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,.11);}
.form-container h2{font-size:20px;font-weight:bold;color:#222;margin-bottom:18px;}
.form-group{margin-bottom:15px;}
.form-group label{font-weight:500;margin-bottom:5px;display:block;color:#333;}
.form-group input,.form-group select{width:100%;padding:10px 17px;border-radius:7px;border:1px solid #ccc;background:#f8fafc;font-size:14px;transition:box-shadow .13s,border-color .13s;}
.form-group input:focus,.form-group select:focus{border-color:#4284f5;background:#fff;box-shadow:0 0 5px rgba(74,138,244,0.11);}
.form-actions{display:flex;justify-content:space-between;margin-top:12px;}
.btn-cancel{background:transparent;border:none;color:#4284f5;cursor:pointer;text-decoration:underline;font-size:15px;font-weight:500;}
.btn-save{background:#4284f5;color:#fff;border:none;padding:10px 18px;border-radius:7px;cursor:pointer;font-size:15px;font-weight:bold;}
.btn-save:hover{background:#2b6bd7;}
.invalid-feedback{color:#cc0000;font-size:13px;margin-top:-10px;margin-bottom:8px;text-align:left;}
</style>
@if($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>Swal.fire({icon:'error',title:'',text:'{{ $errors->first() }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500})</script>
@endif
<div class="form-container">
    <h2>Nuevo usuario</h2>
    <form action="{{ route('users.store') }}" method="POST" autocomplete="off">
    @csrf
        <div class="form-group">
          <label for="nombre_completo">Nombre completo *</label>
          <input type="text" id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo') }}" required>
          @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="name">Nombre de usuario *</label>
          <input type="text" id="name" name="name" value="{{ old('name') }}" required>
          @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="email">Correo *</label>
          <input type="email" id="email" name="email" value="{{ old('email') }}" required>
          @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="telefono">Teléfono</label>
          <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}">
          @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="almacen_id">Almacén *</label>
          <select id="almacen_id" name="almacen_id" required>
              <option value="">Seleccione almacén</option>
              @foreach ($almacenes as $almacen)
                  <option value="{{ $almacen->id }}" {{ old('almacen_id')==$almacen->id ? 'selected' : '' }}>{{ $almacen->nombre }}</option>
              @endforeach
          </select>
          @error('almacen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="rol">Rol *</label>
          <select id="rol" name="rol" required>
              <option value="clientes" {{ old('rol')=='clientes' ? 'selected':'' }}>Clientes</option>
              <option value="secretaria" {{ old('rol')=='secretaria' ? 'selected':'' }}>Secretaria</option>
              <option value="funcionario" {{ old('rol')=='funcionario' ? 'selected':'' }}>Funcionario</option>
              <option value="admin" {{ old('rol')=='admin' ? 'selected':'' }}>Administrador</option>
          </select>
          @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="password">Contraseña *</label>
          <input type="password" id="password" name="password" required>
          @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirmar contraseña *</label>
          <input type="password" id="password_confirmation" name="password_confirmation" required>
        </div>
        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
@endsection
