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
    width: 420px;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    margin:auto;
}
.form-container h2 {
    margin-top: 0; font-size: 20px; color: #222; margin-bottom: 18px; font-weight: 700;
}
.form-container label {
    display: block; margin-bottom: 4px; font-weight: bold; color: #333; text-align: left; max-width: none;
}
.form-container input, .form-container textarea {
    display: block; width: 100%; padding: 10px 16px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 14px; font-size: 14px; background: #f8fafc; transition:box-shadow .15s, border-color .15s; box-sizing: border-box;
}
.form-container input:focus, .form-container textarea:focus {
    border-color: #4a8af4; outline: none; box-shadow: 0 0 4px rgba(74,138,244,0.14); background: #fff;
}
.total-badge {
    display:inline-block;
    min-width:120px;
    margin:7px 0 16px 0;
    background:#0066cc;
    color:#fff;
    font-weight:500;
    font-size:15px;
    border-radius:7px;
    padding:8px 20px;
}
.actions {
    display: flex; justify-content: space-between; margin-top: 10px;
}
.btn-cancel {
    background: transparent; border: none; color: #4a8af4; font-size: 15px; cursor: pointer; text-decoration: underline; font-weight: 500;
}
.btn-save {
    background: #4a8af4; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px;
}
.btn-save:hover { background: #2f6fe0; }
.invalid-feedback { color: #d60000; font-size: 13px; margin-top: -8px; margin-bottom: 8px; text-align:left; }
</style>
<div class="form-bg">
<div class="form-container">
    <h2>Nuevo contenedor</h2>
    <form method="POST" action="{{ route('containers.store') }}" autocomplete="off">
        @csrf
        <label for="reference">Referencia*</label>
        <input name="reference" type="text" required value="{{ old('reference') }}">
        @error('reference') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="product_name">Producto*</label>
        <input name="product_name" type="text" required value="{{ old('product_name') }}">
        @error('product_name') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="boxes">Cantidad de cajas*</label>
        <input name="boxes" id="boxes" type="number" required min="0" value="{{ old('boxes', 0) }}" oninput="calcSheets()">
        @error('boxes') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="sheets_per_box">Láminas por caja*</label>
        <input name="sheets_per_box" id="sheets_per_box" type="number" required min="1" value="{{ old('sheets_per_box', 40) }}" oninput="calcSheets()">
        @error('sheets_per_box') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <span class="total-badge">Total láminas: <span id="total_sheets">0</span></span>
        <label for="note">Observación</label>
        <textarea name="note" rows="2">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="actions">
            <a href="{{ route('containers.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
</div>
<script>
function calcSheets(){
    var cajas = parseInt(document.getElementById('boxes').value) || 0;
    var lxCaja = parseInt(document.getElementById('sheets_per_box').value) || 0;
    document.getElementById('total_sheets').innerText = cajas * lxCaja;
}
document.addEventListener('DOMContentLoaded',function(){ calcSheets(); document.getElementById('boxes').addEventListener('input',calcSheets); document.getElementById('sheets_per_box').addEventListener('input',calcSheets); });
</script>
@endsection
