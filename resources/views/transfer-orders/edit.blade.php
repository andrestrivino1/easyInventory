@extends('layouts.app')

@section('content')
<style>
.form-bg {
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
    margin: auto;
}
.form-container h2 {
    margin-top: 0; font-size: 20px; color: #222; margin-bottom: 18px; font-weight: 700;
}
.form-container label {
    display: block; margin-bottom: 4px; font-weight: bold; color: #333; text-align: left; margin-left:0; margin-right:0; max-width: none;
}
.form-container input, .form-container select {
    display: block; width: 100%; padding: 10px 16px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 15px; font-size: 14px; background: #f8fafc; transition:box-shadow .15s, border-color .15s; margin-left:0; margin-right:0; max-width:none; box-sizing: border-box;
}
.form-container input:focus, .form-container select:focus {
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
  <h2>Editar transferencia</h2>
  <form action="{{ route('transfer-orders.update', $transferOrder) }}" method="POST" autocomplete="off">
    @csrf @method('PUT')

    <label for="warehouse_from_id">Almacén origen*</label>
    <select name="warehouse_from_id" id="warehouse_from_id" required @if($transferOrder->status!=='en_transito') disabled @endif>
      <option value="">Seleccione</option>
      @foreach($warehouses as $wh)
        <option value="{{ $wh->id }}" @if(old('warehouse_from_id', $transferOrder->warehouse_from_id)==$wh->id) selected @endif>{{ $wh->nombre }}</option>
      @endforeach
    </select>

    <label for="warehouse_to_id">Almacén destino*</label>
    <select name="warehouse_to_id" id="warehouse_to_id" required @if($transferOrder->status!=='en_transito') disabled @endif>
      <option value="">Seleccione</option>
      @foreach($warehouses as $wh)
        <option value="{{ $wh->id }}" @if(old('warehouse_to_id', $transferOrder->warehouse_to_id)==$wh->id) selected @endif>{{ $wh->nombre }}</option>
      @endforeach
    </select>

    <label for="product_id">Producto*</label>
    <select name="product_id" id="product_id" required @if($transferOrder->status!=='en_transito') disabled @endif>
      <option value="">Seleccione</option>
      @foreach($products as $prod)
        <option value="{{ $prod->id }}" @if(old('product_id', $transferOrder->products[0]->id ?? null)==$prod->id) selected @endif>{{ $prod->nombre }}</option>
      @endforeach
    </select>

    <label for="quantity">Cantidad*</label>
    <input type="number" name="quantity" id="quantity" min="1" value="{{ old('quantity', $transferOrder->products[0]->pivot->quantity ?? 1) }}" required @if($transferOrder->status!=='en_transito') readonly @endif>

    <label for="note">Notas</label>
    <input type="text" name="note" id="note" value="{{ old('note', $transferOrder->note) }}" @if($transferOrder->status!=='en_transito') readonly @endif>

    <label for="driver_id">Placa del Vehículo*</label>
    <select name="driver_id" id="driver_id" required onchange="setConductorFromPlate(this)" @if($transferOrder->status!=='en_transito') disabled @endif>
      <option value="">Seleccione</option>
      @foreach($drivers as $driver)
        <option value="{{ $driver->id }}" data-name="{{ $driver->name }}" data-id="{{ $driver->identity }}" @if(old('driver_id', $transferOrder->driver_id)==$driver->id) selected @endif>{{ $driver->vehicle_plate }} - {{ $driver->name }}</option>
      @endforeach
    </select>

    <label for="conductor_show">Conductor</label>
    <input type="text" id="conductor_show" value="{{ old('conductor_show', $transferOrder->driver ? ($transferOrder->driver->name.' ('.$transferOrder->driver->identity.')') : '') }}" readonly style="background:#e9ecef; pointer-events:none;">

    <div class="actions">
      <a href="{{ route('transfer-orders.index') }}" class="btn-cancel">Cancelar</a>
      @if($transferOrder->status==='en_transito')
      <button type="submit" class="btn-save">Guardar</button>
      @endif
    </div>
  </form>
</div>
</div>
@endsection

<script>
function setConductorFromPlate(sel) {
    let n = sel.options[sel.selectedIndex].getAttribute('data-name');
    let cid = sel.options[sel.selectedIndex].getAttribute('data-id');
    document.getElementById('conductor_show').value = (n && cid) ? (n + ' (' + cid + ')') : '';
}
window.onload = function() {
  var sel = document.getElementById('driver_id');
  if(sel) setConductorFromPlate(sel);
}
</script>
