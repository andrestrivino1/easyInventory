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
  <h2>Nueva transferencia</h2>
  <form action="{{ route('transfer-orders.store') }}" method="POST" autocomplete="off">
    @csrf
    <label for="warehouse_from_id">Almacén origen*</label>
    <select name="warehouse_from_id" id="warehouse_from_id" required>
      <option value="">Seleccione</option>
      @foreach($warehouses as $wh)
        <option value="{{ $wh->id }}" {{ old('warehouse_from_id') == $wh->id ? 'selected' : '' }}>{{ $wh->nombre }}</option>
      @endforeach
    </select>

    <label for="warehouse_to_id">Almacén destino*</label>
    <select name="warehouse_to_id" id="warehouse_to_id" required>
      <option value="">Seleccione</option>
      @foreach($warehouses as $wh)
        <option value="{{ $wh->id }}" {{ old('warehouse_to_id') == $wh->id ? 'selected' : '' }}>{{ $wh->nombre }}</option>
      @endforeach
    </select>

    <label for="product_id">Producto*</label>
    <select name="product_id" id="product_id" required>
      <option value="">Seleccione</option>
      @foreach($products as $prod)
        <option value="{{ $prod->id }}" {{ old('product_id') == $prod->id ? 'selected' : '' }}>{{ $prod->nombre }}</option>
      @endforeach
    </select>

    <label for="quantity">Cantidad*</label>
    <input type="number" name="quantity" id="quantity" min="1" value="{{ old('quantity', 1) }}" required>

    <label for="note">Notas</label>
    <textarea name="note" id="note" rows="2" placeholder="Opcional">{{ old('note') }}</textarea>

    <div class="actions">
      <a href="{{ route('transfer-orders.index') }}" class="btn-cancel">Cancelar</a>
      <button type="submit" class="btn-save">Guardar</button>
    </div>
  </form>
</div>
</div>
@endsection
