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
    width: 520px;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    margin:auto;
}
.form-container h2 {
    margin-top: 0; font-size: 20px; color: #222; margin-bottom: 18px; font-weight: 700;
}
.form-container label {
    display: block; margin-bottom: 4px; font-weight: bold; color: #333; text-align: left; margin-left:0; margin-right:0; max-width: none;
}
.form-container input, .form-container select, .form-container textarea {
    display: block; width: 100%; padding: 10px 16px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 15px; font-size: 14px; background: #f8fafc; transition:box-shadow .15s, border-color .15s; margin-left:0; margin-right:0; max-width:none; box-sizing: border-box;
}
.form-container input:focus, .form-container select:focus, .form-container textarea:focus {
    border-color: #4a8af4; outline: none; box-shadow: 0 0 4px rgba(74,138,244,0.14); background: #fff;
}
.actions {
    display: flex; justify-content: space-between; margin-top: 10px; max-width: unset; margin-left: 0; margin-right: 0;
}
.btn-cancel {
    background: transparent; border: none; color: #4a8af4; font-size: 15px; cursor: pointer; text-decoration: underline; font-weight: 500; padding-left:0; padding-right:0;
}
.btn-save {
    background: #4a8af4; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px;
}
.btn-save:hover { background: #2f6fe0; }
.invalid-feedback {
    color: #d60000; font-size: 13px; margin-top: -10px; margin-bottom: 8px; margin-left: 0; margin-right: 0; max-width:none; text-align:left;
}
</style>
<div class="form-bg">
<div class="form-container">
    <h2>Editar conductor</h2>
    <form method="POST" action="{{ route('drivers.update', $driver) }}" autocomplete="off" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <label for="name">Nombre*</label>
        <input name="name" type="text" class="form-control" required value="{{ old('name', $driver->name) }}">
        @error('name') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="identity">Cédula*</label>
        <input name="identity" type="text" class="form-control" required maxlength="20" value="{{ old('identity', $driver->identity) }}">
        @error('identity') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="phone">Teléfono</label>
        <input name="phone" type="text" class="form-control" maxlength="20" value="{{ old('phone', $driver->phone) }}">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="photo">Foto del conductor</label>
        @if($driver->photo_path)
            <div style="margin-bottom: 8px;">
                <img src="{{ Storage::url($driver->photo_path) }}" alt="Foto del conductor" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        @endif
        <input name="photo" type="file" class="form-control" accept="image/*">
        @error('photo') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="vehicle_plate">Placa*</label>
        <input name="vehicle_plate" type="text" class="form-control" required maxlength="20" value="{{ old('vehicle_plate', $driver->vehicle_plate) }}">
        @error('vehicle_plate') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="vehicle_photo">Foto del vehículo</label>
        @if($driver->vehicle_photo_path)
            <div style="margin-bottom: 8px;">
                <img src="{{ Storage::url($driver->vehicle_photo_path) }}" alt="Foto del vehículo" style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
        @endif
        <input name="vehicle_photo" type="file" class="form-control" accept="image/*">
        @error('vehicle_photo') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="social_security_date">Fecha de Seguridad Social</label>
        <input name="social_security_date" type="date" class="form-control" value="{{ old('social_security_date', $driver->social_security_date ? \Carbon\Carbon::parse($driver->social_security_date)->format('Y-m-d') : '') }}">
        @error('social_security_date') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="social_security_pdf">PDF de Seguridad Social</label>
        @if($driver->social_security_pdf)
            <div style="margin-bottom: 8px;">
                <a href="{{ route('drivers.social-security-pdf', $driver) }}" target="_blank" style="color: #4a8af4; text-decoration: underline;">
                    <i class="bi bi-file-pdf"></i> Ver PDF actual
                </a>
            </div>
        @endif
        <input name="social_security_pdf" type="file" class="form-control" accept="application/pdf">
        @error('social_security_pdf') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="vehicle_owner">Propietario del Vehículo</label>
        <input name="vehicle_owner" type="text" class="form-control" maxlength="255" value="{{ old('vehicle_owner', $driver->vehicle_owner) }}">
        @error('vehicle_owner') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="active">Estado</label>
        <select name="active" class="form-select">
            <option value="1" @if(old('active', $driver->active)) selected @endif>Activo</option>
            <option value="0" @if(!old('active', $driver->active)) selected @endif>Inactivo</option>
        </select>
        @error('active') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="actions">
            <a href="{{ route('drivers.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
</div>
@endsection
