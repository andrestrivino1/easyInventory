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
    <h2>Nueva transferencia</h2>
    <form method="POST" action="{{ route('transfer-orders.store') }}" autocomplete="off" id="transferForm">
        @csrf
        @if(session('error'))
        <div class="alert alert-danger" style="margin-bottom:15px; text-align:center; padding:12px; background:#ffebee; color:#c62828; border-radius:6px;">
            {{ session('error') }}
        </div>
        @endif

        <label for="warehouse_from_id">Bodega origen*</label>
        <select name="warehouse_from_id" id="warehouse_from_id" required onchange="loadProductsForWarehouse()">
            <option value="">Seleccione</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ old('warehouse_from_id') == $wh->id ? 'selected' : '' }}>{{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}</option>
            @endforeach
        </select>
        @error('warehouse_from_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="warehouse_to_id">Bodega destino*</label>
        <select name="warehouse_to_id" id="warehouse_to_id" required>
            <option value="">Seleccione</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ old('warehouse_to_id') == $wh->id ? 'selected' : '' }}>{{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}</option>
            @endforeach
        </select>
        @error('warehouse_to_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div id="pablo-rojas-info" style="display:none; background:#e3f2fd; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1565c0;">
            ℹ️ Desde Pablo Rojas solo se pueden despachar productos medidos en Cajas
        </div>

        <div style="margin-top: 20px; margin-bottom: 10px;">
            <label style="margin-bottom: 10px;">Productos a transferir*</label>
            <button type="button" class="btn-add-product" onclick="addProduct()">+ Agregar Producto</button>
        </div>

        <div id="products-container">
            <!-- Los productos se agregarán aquí dinámicamente -->
        </div>

        @error('products') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="driver_id">Placa del Vehículo*</label>
        <select name="driver_id" id="driver_id" required onchange="setConductorFromPlate(this)">
            <option value="">Seleccione</option>
            @foreach($drivers as $driver)
            <option value="{{ $driver->id }}" data-name="{{ $driver->name }}" data-id="{{ $driver->identity }}">{{ $driver->vehicle_plate }} - {{ $driver->name }}</option>
            @endforeach
        </select>
        @error('driver_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="conductor_show">Conductor</label>
        <input type="text" id="conductor_show" value="" readonly style="background:#e9ecef; pointer-events:none;">

        <label for="note">Notas</label>
        <textarea name="note" id="note" rows="2" placeholder="Opcional">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div style="margin-top: 15px; margin-bottom: 15px;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                <input type="checkbox" name="show_signatures" id="show_signatures" value="1" {{ old('show_signatures') ? 'checked' : '' }} style="width: auto; margin: 0;">
                <span>Mostrar campos de firma (Nombre y NIT/Cédula) en el PDF</span>
            </label>
        </div>

        <div class="actions">
            <a href="{{ route('transfer-orders.index') }}" class="btn-cancel">Cancelar</a>
            <button type="submit" class="btn-save">Guardar</button>
        </div>
    </form>
</div>
</div>

<script>
let productIndex = 0;
let availableProducts = [];

function setConductorFromPlate(sel) {
    let n = sel.options[sel.selectedIndex].getAttribute('data-name');
    let cid = sel.options[sel.selectedIndex].getAttribute('data-id');
    document.getElementById('conductor_show').value = (n && cid) ? (n + ' (' + cid + ')') : '';
}

async function loadProductsForWarehouse() {
    const warehouseFrom = document.getElementById('warehouse_from_id');
    const infoDiv = document.getElementById('pablo-rojas-info');
    
    if (!warehouseFrom || !warehouseFrom.value) {
        availableProducts = [];
        updateProductSelects();
        if (infoDiv) infoDiv.style.display = 'none';
        return;
    }
    
    const warehouseId = parseInt(warehouseFrom.value);
    
    // Mostrar mensaje de bodegas que reciben contenedores
    if (infoDiv) {
        infoDiv.style.display = 'block';
        infoDiv.textContent = 'ℹ️ Desde esta bodega solo se pueden despachar productos medidos en Cajas';
    }
    
    try {
        const response = await fetch(`{{ route('transfer-orders.get-products', ':id') }}`.replace(':id', warehouseId));
        if (!response.ok) throw new Error('Error al cargar productos');
        
        availableProducts = await response.json();
        updateProductSelects();
        
        // Limpiar selecciones de productos y contenedores cuando cambie la bodega
        const productSelects = document.querySelectorAll('select[id^="product-select-"]');
        productSelects.forEach(select => {
            select.value = '';
            const index = select.id.replace('product-select-', '');
            const containerSelect = document.getElementById(`container-select-${index}`);
            if (containerSelect) {
                containerSelect.innerHTML = '<option value="">Primero seleccione un producto</option>';
            }
            const stockInfo = document.getElementById(`stock-info-${index}`);
            if (stockInfo) stockInfo.innerHTML = '';
        });
    } catch (error) {
        console.error('Error:', error);
        availableProducts = [];
        updateProductSelects();
    }
}

function updateProductSelects() {
    const productSelects = document.querySelectorAll('select[id^="product-select-"]');
    productSelects.forEach(select => {
        const selectedValue = select.value;
        const currentOptions = Array.from(select.options);
        const firstOption = currentOptions[0];
        
        // Limpiar opciones excepto la primera
        select.innerHTML = firstOption ? firstOption.outerHTML : '<option value="">Seleccione un producto</option>';
        
        // Agregar productos disponibles
        availableProducts.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.nombre} (${product.codigo})`;
            option.setAttribute('data-tipo', product.tipo_medida || '');
            option.setAttribute('data-stock', product.stock || 0);
            option.setAttribute('data-unidades', product.unidades_por_caja || 1);
            option.setAttribute('data-containers', JSON.stringify(product.containers || []));
            select.appendChild(option);
        });
        
        // Restaurar selección si aún está disponible
        if (selectedValue && availableProducts.some(p => p.id == selectedValue)) {
            select.value = selectedValue;
            const index = select.id.replace('product-select-', '');
            loadContainersForProduct(index);
        }
    });
}

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
                <select name="products[${productIndex}][product_id]" id="product-select-${productIndex}" required onchange="loadContainersForProduct(${productIndex})">
                    <option value="">Seleccione un producto</option>
                </select>
                <div class="stock-info" id="stock-info-${productIndex}"></div>
            </div>
            <div>
                <label for="products[${productIndex}][container_id]">Contenedor*</label>
                <select name="products[${productIndex}][container_id]" id="container-select-${productIndex}" required>
                    <option value="">Primero seleccione un producto</option>
                </select>
            </div>
            <div>
                <label for="products[${productIndex}][quantity]">Cantidad*</label>
                <input type="number" name="products[${productIndex}][quantity]" id="quantity-${productIndex}" min="1" value="1" required oninput="updateProductInfo(${productIndex})">
            </div>
        </div>
    `;
    
    container.appendChild(productItem);
    productIndex++;
    updateProductSelects();
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

function loadContainersForProduct(index) {
    const productSelect = document.getElementById(`product-select-${index}`);
    const containerSelect = document.getElementById(`container-select-${index}`);
    const stockInfo = document.getElementById(`stock-info-${index}`);
    
    if (!productSelect || !containerSelect) return;
    
    const selectedOption = productSelect.selectedOptions[0];
    if (selectedOption && selectedOption.value) {
        // Cargar contenedores del producto
        const containersJson = selectedOption.getAttribute('data-containers');
        const containers = containersJson ? JSON.parse(containersJson) : [];
        
        // Limpiar y poblar el select de contenedores
        containerSelect.innerHTML = '<option value="">Seleccione un contenedor</option>';
        if (containers.length > 0) {
            containers.forEach(container => {
                const option = document.createElement('option');
                option.value = container.id;
                option.textContent = container.reference;
                containerSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No hay contenedores disponibles';
            option.disabled = true;
            containerSelect.appendChild(option);
        }
        
        // Actualizar información de stock
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const tipo = selectedOption.getAttribute('data-tipo');
        const unidadesPorCaja = parseInt(selectedOption.getAttribute('data-unidades')) || 1;
        
        if (tipo === 'caja' && unidadesPorCaja > 0) {
            const cajasDisponibles = Math.floor(stock / unidadesPorCaja);
            stockInfo.innerHTML = `Stock: ${stock} unidades (${cajasDisponibles} cajas disponibles)`;
        } else {
            stockInfo.innerHTML = `Stock: ${stock} unidades`;
        }
        
        updateProductInfo(index);
    } else {
        containerSelect.innerHTML = '<option value="">Primero seleccione un producto</option>';
        if (stockInfo) stockInfo.innerHTML = '';
    }
}

function updateProductInfo(index) {
    const quantityInput = document.getElementById(`quantity-${index}`);
    const stockInfo = document.getElementById(`stock-info-${index}`);
    
    if (!quantityInput || !stockInfo) return;
    
    const productSelect = document.getElementById(`product-select-${index}`);
    if (!productSelect || !productSelect.value) return;
    
    const selectedOption = productSelect.selectedOptions[0];
    if (selectedOption) {
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const tipo = selectedOption.getAttribute('data-tipo');
        const unidadesPorCaja = parseInt(selectedOption.getAttribute('data-unidades')) || 1;
        const quantity = parseInt(quantityInput.value) || 0;
        
        if (tipo === 'caja' && unidadesPorCaja > 0) {
            const cajasDisponibles = Math.floor(stock / unidadesPorCaja);
            const unidadesRequeridas = quantity * unidadesPorCaja;
            stockInfo.innerHTML = `Stock: ${stock} unidades (${cajasDisponibles} cajas disponibles)<br>Requiere: ${unidadesRequeridas} unidades`;
        } else {
            stockInfo.innerHTML = `Stock: ${stock} unidades`;
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Agregar un producto por defecto
    addProduct();
    
    // Validar que haya al menos un producto antes de enviar
    document.getElementById('transferForm').addEventListener('submit', function(e) {
        const productItems = document.querySelectorAll('.product-item');
        if (productItems.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos un producto a la transferencia.');
            return false;
        }
    });
    
    // Cargar productos cuando cambie la bodega origen
    const warehouseFrom = document.getElementById('warehouse_from_id');
    if (warehouseFrom) {
        warehouseFrom.addEventListener('change', loadProductsForWarehouse);
        // Cargar productos si ya hay una bodega seleccionada (por ejemplo, desde old input)
        if (warehouseFrom.value) {
            loadProductsForWarehouse();
        }
    }
});
</script>
@endsection
