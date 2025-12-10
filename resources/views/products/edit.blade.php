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
    .form-container input, .form-container select {
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
    <h2>Editar producto</h2>
    <form action="{{ route('products.update', $product) }}" method="POST" autocomplete="off">
      @csrf
      @method('PUT')
      <label for="almacen_id">Almacén*</label>
      <select name="almacen_id" id="almacen_id" required>
        <option value="">Seleccione un almacén</option>
        @foreach($warehouses as $almacen)
            <option value="{{ $almacen->id }}" {{ old('almacen_id', $product->almacen_id ?? '') == $almacen->id ? 'selected' : '' }}>{{ $almacen->nombre }}</option>
        @endforeach
      </select>
      @error('almacen_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="nombre">Nombre*</label>
      <input type="text" name="nombre" id="nombre" class="@error('nombre') is-invalid @enderror" placeholder="Nombre del producto" value="{{ old('nombre', $product->nombre) }}" required>
      @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="precio">Precio</label>
      <input type="number" name="precio" id="precio" value="{{ old('precio', $product->precio) }}">
      @error('precio') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="stock">Stock</label>
      <input type="number" name="stock" id="stock" value="{{ old('stock', $product->stock) }}">
      @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="estado">Estado</label>
      <select name="estado" id="estado">
          <option value="1" {{ old('estado', $product->estado) == 1 ? 'selected' : '' }}>Activo</option>
          <option value="0" {{ old('estado', $product->estado) == 0 ? 'selected' : '' }}>Inactivo</option>
      </select>
      <div class="actions">
        <a href="{{ route('products.index') }}" class="btn-cancel">Cancelar</a>
        <button type="submit" class="btn-save">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
