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

      <label for="tipo_medida">Tipo de stock*</label>
      <select name="tipo_medida" id="tipo_medida" onchange="toggleBoxFields()" required>
        <option value="unidad" {{ (old('tipo_medida', $product->tipo_medida)==='unidad') ? 'selected' : '' }}>Unidades</option>
        <option value="caja" {{ (old('tipo_medida', $product->tipo_medida)==='caja') ? 'selected' : '' }}>Cajas</option>
      </select>
      <div id="box-fields" style="display: none;">
        <label for="stock_cajas">Stock (en cajas)</label>
        <input type="number" id="stock_cajas" name="stock_cajas" min="1" value="{{ old('stock_cajas', $product->tipo_medida=='caja' ? ceil($product->stock / $product->unidades_por_caja) : 1) }}" oninput="calculateTotalStock()">
        <label for="unidades_por_caja">Unidades por caja*</label>
        <input type="number" name="unidades_por_caja" id="unidades_por_caja" min="1" value="{{ old('unidades_por_caja', $product->unidades_por_caja ?? 40) }}">
        <div style="color:#2b3136; font-size:13px; margin-bottom:12px;">Ejemplo: Una caja contiene 40 unidades</div>
        <div style="color:#0066cc;font-size:14px;margin-bottom:12px;">Stock total en unidades: <span id="total_unidades">0</span></div>
      </div>
      <div id="solo-unidades">
        <label for="stock">Stock</label>
        <input type="number" name="stock" id="stock" value="{{ old('stock', $product->tipo_medida==='unidad' ? $product->stock : '') }}">
      </div>
      @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror

      <label for="container_id">Contenedor (Referencia)*</label>
      <select name="container_id" id="container_id" required>
        <option value="">Seleccione</option>
        @foreach($containers as $cont)
            <option value="{{ $cont->id }}" {{ old('container_id', $product->container_id) == $cont->id ? 'selected' : '' }}>{{ $cont->reference }}</option>
        @endforeach
      </select>
      @error('container_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function toggleStockTypeByWarehouse() {
    var wh = document.getElementById('almacen_id');
    var tipoSelect = document.getElementById('tipo_medida');
    if(!wh || !tipoSelect) return;
    var buenaventuraOption = null;
    for(let opt of wh.options) {
        if(opt.innerText.trim().toLowerCase() === 'buenaventura' && opt.value) buenaventuraOption = opt.value;
    }
    tipoSelect.removeEventListener('change', enforceCaja);
    if (wh.value === buenaventuraOption) {
        if(tipoSelect.value !== 'caja') {
            tipoSelect.value = 'caja';
            Swal.fire({icon:'info',title:'Solo se permiten Cajas en Buenaventura',toast:true,position:'top-end',showConfirmButton:false,timer:2000});
        }
        for(let opt of tipoSelect.options) {
            if(opt.value === 'unidad') { opt.disabled = true; opt.hidden = true; } else { opt.disabled = false; opt.hidden = false; }
        }
        tipoSelect.addEventListener('change', enforceCaja);
    } else {
        for(let opt of tipoSelect.options) { opt.disabled = false; opt.hidden = false; }
        tipoSelect.removeEventListener('change', enforceCaja);
    }
    toggleBoxFields();
}
function enforceCaja(e) {
    var tipoSelect = document.getElementById('tipo_medida');
    tipoSelect.value = 'caja';
    Swal && Swal.fire({icon:'info',title:'Solo se permiten Cajas en Buenaventura',toast:true,position:'top-end',showConfirmButton:false,timer:2000});
}
function toggleBoxFields() {
  var tipo = document.getElementById('tipo_medida').value;
  document.getElementById('box-fields').style.display = (tipo === 'caja') ? 'block' : 'none';
  document.getElementById('solo-unidades').style.display = (tipo === 'caja') ? 'none' : 'block';
}
function calculateTotalStock() {
  var cajas = parseInt(document.getElementById('stock_cajas').value) || 0;
  var unidadesPorCaja = parseInt(document.getElementById('unidades_por_caja').value) || 0;
  document.getElementById('total_unidades').innerText = cajas * unidadesPorCaja;
}
document.addEventListener('DOMContentLoaded', function() {
  var wh = document.getElementById('almacen_id');
  if(wh){
    wh.addEventListener('change', toggleStockTypeByWarehouse);
    toggleStockTypeByWarehouse();
  }
  toggleBoxFields();
  calculateTotalStock();
  var sc = document.getElementById('stock_cajas');
  var upc = document.getElementById('unidades_por_caja');
  if(sc) sc.addEventListener('input', calculateTotalStock);
  if(upc) upc.addEventListener('input', calculateTotalStock);
  // Fuerza mostrar tipomedida según dato actual al cargar
  var tipoSelect = document.getElementById('tipo_medida');
  if(tipoSelect) {
    tipoSelect.value = "{{ old('tipo_medida', $product->tipo_medida) }}";
    toggleBoxFields();
  }
  // Fuerza tipo de stock = caja para Buenaventura justo antes del submit
  var form = document.querySelector('form');
  if(form) {
    form.addEventListener('submit', function(){
      var buenaventuraOption = null;
      for(let opt of wh.options) {
        if(opt.innerText.trim().toLowerCase() === 'buenaventura' && opt.value) buenaventuraOption = opt.value;
      }
      if (wh.value === buenaventuraOption && tipoSelect) tipoSelect.value = 'caja';
    });
  }
});
</script>
