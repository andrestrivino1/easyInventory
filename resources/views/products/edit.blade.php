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
      @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:15px;">{{ session('error') }}</div>
      @endif
      <label for="nombre">Nombre*</label>
      <input type="text" name="nombre" id="nombre" class="@error('nombre') is-invalid @enderror" placeholder="Nombre del producto" value="{{ old('nombre', $product->nombre) }}" required>
      @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="medidas">Medidas</label>
      <input type="text" name="medidas" id="medidas" class="@error('medidas') is-invalid @enderror" placeholder="Ej: 100cm x 50cm x 2cm" value="{{ old('medidas', $product->medidas) }}">
      @error('medidas') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="calibre">Calibre</label>
      <input type="number" name="calibre" id="calibre" step="0.01" min="0" class="@error('calibre') is-invalid @enderror" value="{{ old('calibre', $product->calibre) }}">
      @error('calibre') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="alto">Alto</label>
      <input type="number" name="alto" id="alto" step="0.01" min="0" class="@error('alto') is-invalid @enderror" value="{{ old('alto', $product->alto) }}">
      @error('alto') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="ancho">Ancho</label>
      <input type="number" name="ancho" id="ancho" step="0.01" min="0" class="@error('ancho') is-invalid @enderror" value="{{ old('ancho', $product->ancho) }}">
      @error('ancho') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="peso_empaque">Peso empaque</label>
      <input type="number" name="peso_empaque" id="peso_empaque" step="0.01" min="0" class="@error('peso_empaque') is-invalid @enderror" value="{{ old('peso_empaque', $product->peso_empaque ?? 2.5) }}">
      @error('peso_empaque') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="tipo_medida">Tipo de medida</label>
      <select name="tipo_medida" id="tipo_medida">
          <option value="">-- Sin definir --</option>
          <option value="unidad" {{ old('tipo_medida', $product->tipo_medida) == 'unidad' ? 'selected' : '' }}>Unidad</option>
          <option value="caja" {{ old('tipo_medida', $product->tipo_medida) == 'caja' ? 'selected' : '' }}>Caja</option>
      </select>

      <label for="unidades_por_caja">Unidades por caja (láminas)</label>
      <input type="number" name="unidades_por_caja" id="unidades_por_caja" min="0" step="1" class="@error('unidades_por_caja') is-invalid @enderror" value="{{ old('unidades_por_caja', $product->unidades_por_caja) }}">
      @error('unidades_por_caja') <div class="invalid-feedback">{{ $message }}</div> @enderror
      <small class="text-muted" style="display:block; margin-top: -8px; margin-bottom: 12px;">Al agregar este producto a un contenedor, el peso por caja se rellenará automáticamente con: Calibre × Alto × Ancho × Peso empaque × Láminas × 1 caja.</small>

      <div style="background:#e3f2fd; padding:12px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1565c0;">
        <strong>Nota:</strong> Los productos son globales y se muestran en todas las bodegas. El tipo de medida y stock se definen automáticamente cuando se agregan a contenedores o se reciben por transferencias.
        @if($product->containers->count() > 0)
          <br><strong>Contenedores asociados:</strong> {{ $product->containers->pluck('reference')->join(', ') }}
        @endif
      </div>

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

