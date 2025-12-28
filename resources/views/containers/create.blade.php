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
</style>
<div class="form-bg">
<div class="form-container">
    <h2>Nuevo contenedor</h2>
    <form method="POST" action="{{ route('containers.store') }}" autocomplete="off" id="containerForm">
        @csrf
        <label for="reference">Referencia*</label>
        <input name="reference" type="text" required value="{{ old('reference') }}" placeholder="Ej: CONT-001">
        @error('reference') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <label for="note">Observación</label>
        <textarea name="note" rows="2" placeholder="Notas adicionales sobre el contenedor">{{ old('note') }}</textarea>
        @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div style="margin-top: 20px; margin-bottom: 10px;">
            <label style="margin-bottom: 10px;">Productos del contenedor*</label>
            <button type="button" class="btn-add-product" onclick="addProduct()">+ Agregar Producto</button>
        </div>

        <div id="products-container">
            <!-- Los productos se agregarán aquí dinámicamente -->
        </div>

        @error('products') <div class="invalid-feedback">{{ $message }}</div>@enderror

        <div class="actions">
            <a href="{{ route('containers.index') }}" class="btn-cancel">Cancelar</a>
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
                <select name="products[${productIndex}][product_id]" required onchange="updateProductFields(${productIndex})">
                    <option value="">Seleccione un producto</option>
                    ${products.map(p => `<option value="${p.id}" data-tipo="${p.tipo_medida}">${p.nombre} (${p.codigo})</option>`).join('')}
                </select>
            </div>
            <div>
                <label for="products[${productIndex}][boxes]">Cajas*</label>
                <input type="number" name="products[${productIndex}][boxes]" min="0" value="0" required oninput="calculateSheets(${productIndex})">
            </div>
            <div>
                <label for="products[${productIndex}][sheets_per_box]">Láminas por caja*</label>
                <input type="number" name="products[${productIndex}][sheets_per_box]" min="1" value="40" required oninput="calculateSheets(${productIndex})">
            </div>
            <div>
                <label>Total láminas</label>
                <div style="padding: 10px 16px; background: #e3f2fd; border-radius: 6px; color: #1565c0; font-weight: bold;" id="total-sheets-${productIndex}">0</div>
            </div>
        </div>
    `;
    
    container.appendChild(productItem);
    productIndex++;
    calculateSheets(productIndex - 1);
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

function updateProductFields(index) {
    const select = document.querySelector(`#product-${index} select[name*="[product_id]"]`);
    if (select && select.selectedOptions[0]) {
        const tipo = select.selectedOptions[0].dataset.tipo;
        const sheetsInput = document.querySelector(`#product-${index} input[name*="[sheets_per_box]"]`);
        if (tipo === 'unidad' && sheetsInput) {
            sheetsInput.value = 1;
            sheetsInput.readOnly = true;
        } else if (sheetsInput) {
            sheetsInput.readOnly = false;
            if (sheetsInput.value == 1) sheetsInput.value = 40;
        }
        calculateSheets(index);
    }
}

function calculateSheets(index) {
    const productItem = document.getElementById(`product-${index}`);
    if (!productItem) return;
    
    const boxesInput = productItem.querySelector('input[name*="[boxes]"]');
    const sheetsInput = productItem.querySelector('input[name*="[sheets_per_box]"]');
    const totalDiv = document.getElementById(`total-sheets-${index}`);
    
    if (boxesInput && sheetsInput && totalDiv) {
        const boxes = parseInt(boxesInput.value) || 0;
        const sheets = parseInt(sheetsInput.value) || 0;
        totalDiv.textContent = boxes * sheets;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Agregar un producto por defecto
    addProduct();
    
    // Validar que haya al menos un producto antes de enviar
    document.getElementById('containerForm').addEventListener('submit', function(e) {
        const productItems = document.querySelectorAll('.product-item');
        if (productItems.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos un producto al contenedor.');
            return false;
        }
    });
});
</script>
@endsection
