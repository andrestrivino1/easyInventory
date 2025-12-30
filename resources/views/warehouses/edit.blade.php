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
    .form-container input:focus {
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
    .btn-save:hover { background: #2f6fe0; }
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
    <h2>Editar bodega</h2>
    <form action="{{ route('warehouses.update', $warehouse) }}" method="POST" autocomplete="off">
      @csrf
      @method('PUT')
      <label for="nombre">Nombre*</label>
      <input type="text" name="nombre" id="nombre" class="@error('nombre') is-invalid @enderror" placeholder="Nombre de la bodega" value="{{ old('nombre', $warehouse->nombre) }}" required>
      @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="direccion">Dirección</label>
      <input type="text" name="direccion" id="direccion" class="@error('direccion') is-invalid @enderror" placeholder="Dirección" value="{{ old('direccion', $warehouse->direccion) }}">
      @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <div class="actions">
        <a href="{{ route('warehouses.index') }}" class="btn-cancel">Cancelar</a>
        <button type="submit" class="btn-save">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
