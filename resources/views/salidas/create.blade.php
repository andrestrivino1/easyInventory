@extends('layouts.app')

@section('content')
<style>
body .form-bg {
    font-family: Arial, sans-serif;
    background: #eef2f7;
    display: flex;
    justify-content: center;
    padding-top: 40px;
    padding-bottom: 40px;
    min-height: 87vh;
}
.form-container {
    background: white;
    width: 700px;
    max-width: 95%;
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
.form-container input, .form-container textarea, .form-container select {
    display: block; width: 100%; padding: 10px 16px; border: 1px solid #ccc; border-radius: 6px; margin-bottom: 14px; font-size: 14px; background: #f8fafc; transition:box-shadow .15s, border-color .15s; box-sizing: border-box;
}
.form-container input:focus, .form-container textarea:focus, .form-container select:focus {
    border-color: #4a8af4; outline: none; box-shadow: 0 0 4px rgba(74,138,244,0.14); background: #fff;
}
.actions {
    display: flex; justify-content: space-between; margin-top: 20px;
}
.btn-cancel {
    background: transparent; border: none; color: #4a8af4; font-size: 15px; cursor: pointer; text-decoration: underline; font-weight: 500;
}
.btn-save {
    background: #4a8af4; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px;
}
.btn-save:hover { background: #2f6fe0; }
.invalid-feedback { color: #d60000; font-size: 13px; margin-top: -8px; margin-bottom: 8px; text-align:left; }
.product-item {
    background: #f8fafc;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    position: relative;
}
.product-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.product-item-title {
    font-weight: bold;
    color: #333;
    font-size: 14px;
}
.btn-remove-product {
    background: #dc3545;
    color: white;
    border: none;
    padding: 5px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
}
.btn-remove-product:hover {
    background: #c82333;
}
.product-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.product-fields > div {
    grid-column: span 1;
}
.product-fields > div.full-width {
    grid-column: span 2;
}
.btn-add-product {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 15px;
    width: 100%;
}
.btn-add-product:hover {
    background: #218838;
}
.stock-info {
    font-size: 12px;
    color: #666;
    margin-top: -8px;
    margin-bottom: 8px;
}
</style>
<div class="form-bg">
<div class="form-container">
    <h2>Nueva salida</h2>
    <form method="POST" action="{{ route('salidas.store') }}" autocomplete="off" id="salidaForm">
        @csrf
        @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:15px; text-align:center; padding:12px; background:#ffebee; color:#c62828; border-radius:6px;">
            {{ session('error') }}
        </div>
        @endif

        @php
            $user = Auth::user();
        @endphp

        @if(in_array($user->rol, ['admin', 'secretaria']))
        <label for="warehouse_id">Bodega*</label>
        <select name="warehouse_id" id="warehouse_id" required>
            <option value="">Seleccione</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ old('warehouse_id', $user->almacen_id) == $wh->id ? 'selected' : '' }}>{{ $wh->nombre }}</option>
            @endforeach
        </select>
        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div>@enderror
        @else
        <input type="hidden" name="warehouse_id" value="{{ $user->almacen_id }}">
        <label>Bodega</label>
        <input type="text" value="{{ $user->almacen->nombre ?? 'N/A' }}" readonly style="background:#e9ecef; pointer-events:none;">
        @endif

        <label for="fecha">Fecha*</label>
        <input type="date" name="fecha" id="fecha" value="{{ old('fecha', date('Y-m-d')) }}" required>
        @error('fecha') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="a_nombre_de">A nombre de*</label>
        <input type="text" name="a_nombre_de" id="a_nombre_de" value="{{ old('a_nombre_de') }}" placeholder="Nombre del cliente" required>
        @error('a_nombre_de') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="nit_cedula">NIT/Cédula*</label>
        <input type="text" name="nit_cedula" id="nit_cedula" value="{{ old('nit_cedula') }}" placeholder="NIT o Cédula" required>
        @error('nit_cedula') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div style="margin-top: 20px; margin-bottom: 10px;">
            <label style="margin-bottom: 10px;">Productos a dar salida*</label>
            <button type="button" class="btn-add-product" onclick="addProduct()">+ Agregar Producto</button>
        </div>

        <div id="products-container">
            <!-- Los productos se agregarán aquí dinámicamente -->
        </div>

        @error('products') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="note">Notas</label>
        <textarea name="note" id="note" rows="2" placeholder="Opcional">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="actions">
            <a href="{{ route('salidas.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
</div>

<script>
let productIndex = 0;
const products = @json($products ?? []);

function addProduct() {
    const container = document.getElementById('products-container');
    const productItem = document.createElement('div');
    productItem.className = 'product-item';
    productItem.id = `product-${productIndex}`;
    
    productItem.innerHTML = `
        <div class="product-item-header">
            <span class="product-item-title">Producto #${productIndex + 1}</span>
            <button type="button" class="btn-remove-product" onclick="removeProduct(${productIndex})">Eliminar</button>
        </div>
        <div class="product-fields">
            <div>
                <label for="products[${productIndex}][product_id]">Producto*</label>
                <select name="products[${productIndex}][product_id]" id="product-select-${productIndex}" required onchange="updateProductInfo(${productIndex})">
                    <option value="">Seleccione un producto</option>
                    ${products.map(p => `<option value="${p.id}" data-tipo="${p.tipo_medida}" data-stock="${p.stock}" data-unidades="${p.unidades_por_caja || 1}">${p.nombre} (${p.codigo}) - Stock: ${p.stock}</option>`).join('')}
                </select>
                <div class="stock-info" id="stock-info-${productIndex}"></div>
            </div>
            <div>
                <label for="products[${productIndex}][quantity]">Cantidad (en láminas)*</label>
                <input type="number" name="products[${productIndex}][quantity]" id="quantity-${productIndex}" min="1" value="1" required oninput="updateProductInfo(${productIndex})" placeholder="Cantidad en láminas">
            </div>
        </div>
    `;
    
    container.appendChild(productItem);
    productIndex++;
}

function removeProduct(index) {
    const productItem = document.getElementById(`product-${index}`);
    if (productItem) {
        productItem.remove();
        renumberProducts();
    }
}

function renumberProducts() {
    const items = document.querySelectorAll('.product-item');
    items.forEach((item, index) => {
        const title = item.querySelector('.product-item-title');
        if (title) {
            title.textContent = `Producto #${index + 1}`;
        }
    });
}

function updateProductInfo(index) {
    const productSelect = document.getElementById(`product-select-${index}`);
    const quantityInput = document.getElementById(`quantity-${index}`);
    const stockInfo = document.getElementById(`stock-info-${index}`);
    
    if (!productSelect || !quantityInput) return;
    
    const selectedOption = productSelect.selectedOptions[0];
    if (selectedOption && selectedOption.value) {
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const tipo = selectedOption.getAttribute('data-tipo');
        const unidades = parseInt(selectedOption.getAttribute('data-unidades')) || 1;
        const quantity = parseInt(quantityInput.value) || 0; // Cantidad en láminas
        
        // La cantidad ingresada ya está en láminas (unidades)
        const laminasRequeridas = quantity;
        
        if (stockInfo) {
            if (laminasRequeridas > stock) {
                const cajas = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
                stockInfo.innerHTML = `<span style="color: #dc3545; font-weight: bold;">⚠️ Stock insuficiente. Disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`;
            } else {
                const cajas = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
                stockInfo.innerHTML = `<span style="color: #28a745">✓ Stock disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`;
            }
        }
    } else {
        // Si no hay producto seleccionado, limpiar la información
        if (stockInfo) stockInfo.innerHTML = '';
    }
}
</script>
@endsection

