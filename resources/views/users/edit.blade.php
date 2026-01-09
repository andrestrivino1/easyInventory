@extends('layouts.app')

@section('content')
<style>
.form-container {background:#fff;max-width:500px;margin:50px auto 0 auto;padding:30px 26px;border-radius:12px;box-shadow:0 4px 10px rgba(0,0,0,.11);}
.form-container h2{font-size:20px;font-weight:bold;color:#222;margin-bottom:18px;}
.form-group{margin-bottom:15px;}
.form-group label{font-weight:500;margin-bottom:5px;display:block;color:#333;}
.form-group input,.form-group select{width:100%;padding:10px 17px;border-radius:7px;border:1px solid #ccc;background:#f8fafc;font-size:14px;transition:box-shadow .13s,border-color .13s;}
.form-group input:focus,.form-group select:focus{border-color:#4284f5;background:#fff;box-shadow:0 0 5px rgba(74,138,244,0.11);}
.checkbox-group{background:#f8fafc;border:1px solid #ccc;border-radius:7px;padding:15px;max-height:200px;overflow-y:auto;}
.checkbox-item{margin-bottom:10px;display:flex;align-items:center;}
.checkbox-item:last-child{margin-bottom:0;}
.checkbox-item input[type="checkbox"]{width:18px;height:18px;margin-right:10px;cursor:pointer;accent-color:#4284f5;}
.checkbox-item label{cursor:pointer;font-size:14px;color:#333;flex:1;}
.invalid-feedback{color:#cc0000;font-size:13px;margin-top:-10px;margin-bottom:8px;text-align:left;}
.form-actions{display:flex;justify-content:space-between;margin-top:12px;}
.btn-cancel{background:transparent;border:none;color:#4284f5;cursor:pointer;text-decoration:underline;font-size:15px;font-weight:500;}
.btn-save{background:#4284f5;color:#fff;border:none;padding:10px 18px;border-radius:7px;cursor:pointer;font-size:15px;font-weight:bold;}
.btn-save:hover{background:#2b6bd7;}
</style>
@if($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>Swal.fire({icon:'error',title:'',text:'{{ $errors->first() }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500})</script>
@endif
<div class="form-container">
    <h2>Editar usuario</h2>
    <form action="{{ route('users.update', $usuario) }}" method="POST" autocomplete="off">
    @csrf @method('PUT')
        <div class="form-group">
            <label for="nombre_completo">Nombre completo *</label>
            <input type="text" id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo', $usuario->nombre_completo) }}" required>
            @error('nombre_completo')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label for="email">Correo *</label>
            <input type="email" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="text" id="telefono" name="telefono" value="{{ old('telefono', $usuario->telefono) }}">
            @error('telefono')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label for="rol">Rol *</label>
            <select id="rol" name="rol" required>
                <option value="clientes" {{ old('rol', $usuario->rol)=='clientes' ? 'selected':'' }}>Clientes</option>
                <option value="funcionario" {{ old('rol', $usuario->rol)=='funcionario' ? 'selected':'' }}>Funcionario</option>
                <option value="importer" {{ old('rol', $usuario->rol)=='importer' ? 'selected':'' }}>Proveedor/Importer</option>
                <option value="admin" {{ old('rol', $usuario->rol)=='admin' ? 'selected':'' }}>Administrador</option>
            </select>
            @error('rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group" id="bodega-simple-group" style="display:none;">
            <label for="almacen_id">Bodega *</label>
            <select id="almacen_id" name="almacen_id">
                <option value="">Seleccione bodega</option>
                @foreach ($almacenes as $almacen)
                    <option value="{{ $almacen->id }}" {{ old('almacen_id', $usuario->almacen_id)==$almacen->id ? 'selected':'' }}>{{ $almacen->nombre }}{{ $almacen->ciudad ? ' - ' . $almacen->ciudad : '' }}</option>
                @endforeach
            </select>
            @error('almacen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group" id="bodega-multiple-group" style="display:none;">
            <label id="bodega-multiple-label">Bodegas *</label>
            <div class="checkbox-group">
                @foreach ($almacenes as $almacen)
                    <div class="checkbox-item">
                        <input type="checkbox" id="almacen_{{ $almacen->id }}" name="almacenes[]" value="{{ $almacen->id }}" {{ (old('almacenes') && in_array($almacen->id, old('almacenes'))) || (!$errors->any() && $usuario->almacenes->contains($almacen->id)) ? 'checked' : '' }}>
                        <label for="almacen_{{ $almacen->id }}">{{ $almacen->nombre }}{{ $almacen->ciudad ? ' - ' . $almacen->ciudad : '' }}</label>
                    </div>
                @endforeach
            </div>
            @error('almacenes')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label for="password">Nueva contraseña</label>
            <input type="password" id="password" name="password" autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirmar nueva contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
        </div>
        <div class="form-actions">
            <a href="{{ route('users.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rolSelect = document.getElementById('rol');
    const bodegaSimpleGroup = document.getElementById('bodega-simple-group');
    const bodegaMultipleGroup = document.getElementById('bodega-multiple-group');
    const almacenId = document.getElementById('almacen_id');
    const form = document.querySelector('form');
    let validationHandler = null;
    
    function toggleBodegaFields() {
        const rol = rolSelect.value;
        bodegaSimpleGroup.style.display = 'none';
        bodegaMultipleGroup.style.display = 'none';
        almacenId.removeAttribute('required');
        
        // Remover required de todos los checkboxes
        const checkboxes = bodegaMultipleGroup.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.removeAttribute('required');
            cb.closest('.checkbox-item').style.display = 'flex';
        });
        
        // Remover el listener anterior si existe
        if (validationHandler) {
            form.removeEventListener('submit', validationHandler);
            validationHandler = null;
        }
        
        if (rol === 'clientes') {
            bodegaMultipleGroup.style.display = 'block';
            document.getElementById('bodega-multiple-label').textContent = 'Bodegas *';
            // Validar que al menos un checkbox esté seleccionado
            validationHandler = function(e) {
                const checked = bodegaMultipleGroup.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Debes seleccionar al menos una bodega');
                    return false;
                }
            };
            form.addEventListener('submit', validationHandler);
        } else if (rol === 'funcionario') {
            bodegaMultipleGroup.style.display = 'block';
            document.getElementById('bodega-multiple-label').textContent = 'Bodegas de Buenaventura *';
            // Filtrar solo bodegas de Buenaventura
            const bodegasBuenaventuraIds = @json(\App\Models\Warehouse::getBodegasBuenaventuraIds());
            checkboxes.forEach(cb => {
                if (bodegasBuenaventuraIds.includes(parseInt(cb.value))) {
                    cb.closest('.checkbox-item').style.display = 'flex';
                } else {
                    cb.closest('.checkbox-item').style.display = 'none';
                    cb.checked = false;
                }
            });
            // Validar que al menos un checkbox esté seleccionado
            validationHandler = function(e) {
                const checked = bodegaMultipleGroup.querySelectorAll('input[type="checkbox"]:checked');
                if (checked.length === 0) {
                    e.preventDefault();
                    alert('Debes seleccionar al menos una bodega de Buenaventura');
                    return false;
                }
            };
            form.addEventListener('submit', validationHandler);
        }
        // admin no muestra campos de bodega
    }
    
    rolSelect.addEventListener('change', toggleBodegaFields);
    toggleBodegaFields(); // Ejecutar al cargar la página
});
</script>
@endsection
