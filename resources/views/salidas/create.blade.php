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
            $isAdmin = $user->rol === 'admin';
            $isCliente = $user->rol === 'clientes';
            $isFuncionario = $user->rol === 'funcionario';
            
            // Determinar si la bodega es de Buenaventura
            $isBuenaventura = false;
            $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
            if ($isCliente && $warehouses->count() == 1) {
                $isBuenaventura = in_array($warehouses->first()->id, $bodegasBuenaventuraIds);
            } elseif (!$isAdmin && !$isCliente && !$isFuncionario) {
                $isBuenaventura = in_array($user->almacen_id, $bodegasBuenaventuraIds);
            }
        @endphp

        @if($isAdmin || ($isCliente && $warehouses->count() > 1) || ($isFuncionario && $warehouses->count() > 1))
        <label for="warehouse_id">Bodega*</label>
        <select name="warehouse_id" id="warehouse_id" required onchange="updateWarehouseType(); loadProductsForWarehouse();">
            <option value="">Seleccione</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" data-is-buenaventura="{{ in_array($wh->id, $bodegasBuenaventuraIds) ? 'true' : 'false' }}" {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                {{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}
            </option>
            @endforeach
        </select>
        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div>@enderror
        @elseif($isCliente && $warehouses->count() == 1)
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $warehouses->first()->id }}">
        <label>Bodega</label>
        <input type="text" value="{{ $warehouses->first()->nombre }}{{ $warehouses->first()->ciudad ? ' - ' . $warehouses->first()->ciudad : '' }}" readonly style="background:#e9ecef; pointer-events:none;">
        @else
        <input type="hidden" name="warehouse_id" id="warehouse_id" value="{{ $user->almacen_id }}">
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
let availableProducts = @json($products ?? []);
let isBuenaventura = @json($isBuenaventura ?? false);
let bodegasBuenaventuraIds = @json($bodegasBuenaventuraIds ?? []);

// Función para actualizar los labels según el tipo de bodega
function updateWarehouseType() {
    const warehouseSelect = document.getElementById('warehouse_id');
    if (!warehouseSelect || warehouseSelect.tagName !== 'SELECT') return;
    
    const selectedOption = warehouseSelect.options[warehouseSelect.selectedIndex];
    const isBuenaventura = selectedOption ? selectedOption.getAttribute('data-is-buenaventura') === 'true' : false;
    
    // Actualizar labels de cantidad en todos los productos
    const quantityLabels = document.querySelectorAll('[id^="quantity-label-"]');
    const quantityInputs = document.querySelectorAll('[id^="quantity-"]');
    
    quantityLabels.forEach(label => {
        if (isBuenaventura) {
            label.textContent = 'Cantidad (en cajas)*';
        } else {
            label.textContent = 'Cantidad (en láminas)*';
        }
    });
    
    quantityInputs.forEach(input => {
        if (input.id.startsWith('quantity-')) {
            input.setAttribute('data-is-buenaventura', isBuenaventura);
            if (isBuenaventura) {
                input.placeholder = 'Cantidad en cajas';
            } else {
                input.placeholder = 'Cantidad en láminas';
            }
            // Actualizar info del producto si hay uno seleccionado
            const index = input.id.replace('quantity-', '');
            updateProductInfo(parseInt(index));
        }
    });
}

// Hacer la función disponible globalmente
window.loadProductsForWarehouse = async function() {
    // Buscar bodega en select o en input hidden
    let warehouseId = null;
    const warehouseElement = document.getElementById('warehouse_id');
    
    if (warehouseElement) {
        if (warehouseElement.tagName === 'SELECT') {
            warehouseId = warehouseElement.value;
            console.log('Bodega encontrada en SELECT:', warehouseId);
        } else if (warehouseElement.tagName === 'INPUT') {
            warehouseId = warehouseElement.value;
            console.log('Bodega encontrada en INPUT hidden:', warehouseId);
        } else {
            console.warn('Elemento warehouse_id encontrado pero tipo desconocido:', warehouseElement.tagName);
        }
    } else {
        // Intentar buscar por name attribute como fallback
        const warehouseByName = document.querySelector('input[name="warehouse_id"], select[name="warehouse_id"]');
        if (warehouseByName) {
            warehouseId = warehouseByName.value;
            console.log('Bodega encontrada por name attribute:', warehouseId);
        } else {
            console.error('No se encontró el elemento warehouse_id ni por ID ni por name');
        }
    }
    
    if (!warehouseId || warehouseId === '') {
        console.warn('No hay bodega seleccionada o el valor está vacío');
        availableProducts = [];
        updateProductSelects();
        // Limpiar productos existentes
        const productSelects = document.querySelectorAll('select[id^="product-select-"]');
        productSelects.forEach(select => {
            select.innerHTML = '<option value="">Seleccione un producto</option>';
            const index = select.id.replace('product-select-', '');
            const stockInfo = document.getElementById(`stock-info-${index}`);
            if (stockInfo) stockInfo.innerHTML = '';
        });
        return;
    }
    
    warehouseId = parseInt(warehouseId);
    
    try {
        const url = `{{ route('salidas.get-products', ':id') }}`.replace(':id', warehouseId);
        console.log('=== INICIO CARGA PRODUCTOS ===');
        console.log('Bodega ID:', warehouseId);
        console.log('URL:', url);
        console.log('Timestamp:', new Date().toISOString());
        
        const response = await fetch(url);
        console.log('Respuesta HTTP:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok,
            headers: Object.fromEntries(response.headers.entries())
        });
        
        if (!response.ok) {
            if (response.status === 403) {
                console.error('ERROR 403: No tienes permiso para ver productos de esta bodega.');
                alert('No tienes permiso para ver productos de esta bodega.');
            } else {
                const errorText = await response.text();
                console.error('Error en respuesta:', {
                    status: response.status,
                    statusText: response.statusText,
                    body: errorText
                });
                alert('Error al cargar productos. Por favor, recarga la página e intenta nuevamente.');
            }
            availableProducts = [];
            updateProductSelects();
            console.log('=== FIN CARGA PRODUCTOS (ERROR) ===');
            return;
        }
        
        availableProducts = await response.json();
        console.log('=== PRODUCTOS CARGADOS ===');
        console.log('Total productos:', availableProducts.length);
        console.log('Productos detallados:', availableProducts);
        
        if (availableProducts.length === 0) {
            console.warn('ADVERTENCIA: No hay productos con stock disponible para esta bodega');
            alert('Esta bodega no tiene productos con stock disponible.');
        } else {
            console.log('Productos disponibles para seleccionar:');
            availableProducts.forEach((p, index) => {
                console.log(`  ${index + 1}. ${p.nombre} (${p.codigo}) - Stock: ${p.stock}`);
            });
        }
        
        updateProductSelects();
        console.log('=== FIN CARGA PRODUCTOS (ÉXITO) ===');
        
        // Limpiar selecciones de productos cuando cambie la bodega
        const productSelects = document.querySelectorAll('select[id^="product-select-"]');
        productSelects.forEach(select => {
            select.value = '';
            const index = select.id.replace('product-select-', '');
            const stockInfo = document.getElementById(`stock-info-${index}`);
            if (stockInfo) stockInfo.innerHTML = '';
        });
    } catch (error) {
        console.error('Error al cargar productos:', error);
        alert('Error al cargar productos. Por favor, verifica la consola del navegador para más detalles.');
        availableProducts = [];
        updateProductSelects();
    }
};

function updateProductSelects() {
    const productSelects = document.querySelectorAll('select[id^="product-select-"]');
    productSelects.forEach(select => {
        const currentValue = select.value;
        const index = select.id.replace('product-select-', '');
        
        // Guardar el valor seleccionado
        select.innerHTML = '<option value="">Seleccione un producto</option>';
        
        // Agregar productos disponibles
        availableProducts.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            // Incluir medidas si están disponibles
            const medidasText = product.medidas ? ` - ${product.medidas}` : '';
            option.textContent = `${product.nombre} (${product.codigo})${medidasText} - Stock: ${product.stock}`;
            option.setAttribute('data-tipo', product.tipo_medida || '');
            option.setAttribute('data-stock', product.stock);
            option.setAttribute('data-unidades', product.unidades_por_caja || 1);
            option.setAttribute('data-medidas', product.medidas || '');
            
            // Restaurar selección si existe
            if (currentValue && product.id == currentValue) {
                option.selected = true;
            }
            
            select.appendChild(option);
        });
        
        // Disparar evento change para actualizar info de stock
        if (select.value) {
            updateProductInfo(parseInt(index));
        }
    });
}

function addProduct() {
    const container = document.getElementById('products-container');
    const productItem = document.createElement('div');
    productItem.className = 'product-item';
    productItem.id = `product-${productIndex}`;
    
    // Determinar si es bodega de Buenaventura
    const warehouseElement = document.getElementById('warehouse_id');
    let currentIsBuenaventura = isBuenaventura;
    if (warehouseElement) {
        if (warehouseElement.tagName === 'SELECT' && warehouseElement.value) {
            const selectedOption = warehouseElement.options[warehouseElement.selectedIndex];
            currentIsBuenaventura = selectedOption ? selectedOption.getAttribute('data-is-buenaventura') === 'true' : false;
        } else if (warehouseElement.tagName === 'INPUT' && warehouseElement.value) {
            const currentWarehouseId = parseInt(warehouseElement.value);
            currentIsBuenaventura = bodegasBuenaventuraIds.includes(currentWarehouseId);
        }
    }
    
    const cantidadLabel = currentIsBuenaventura ? 'Cantidad (en cajas)*' : 'Cantidad (en láminas)*';
    const cantidadPlaceholder = currentIsBuenaventura ? 'Cantidad en cajas' : 'Cantidad en láminas';
    
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
                </select>
                <div class="stock-info" id="stock-info-${productIndex}"></div>
            </div>
            <div>
                <label for="products[${productIndex}][quantity]" id="quantity-label-${productIndex}">${cantidadLabel}</label>
                <input type="number" name="products[${productIndex}][quantity]" id="quantity-${productIndex}" min="1" value="1" required oninput="updateProductInfo(${productIndex})" placeholder="${cantidadPlaceholder}" data-is-buenaventura="${currentIsBuenaventura}">
            </div>
        </div>
    `;
    
    container.appendChild(productItem);
    
    // Actualizar el select con productos disponibles
    const select = document.getElementById(`product-select-${productIndex}`);
    if (select) {
        availableProducts.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            // Incluir medidas si están disponibles
            const medidasText = product.medidas ? ` - ${product.medidas}` : '';
            option.textContent = `${product.nombre} (${product.codigo})${medidasText} - Stock: ${product.stock}`;
            option.setAttribute('data-tipo', product.tipo_medida || '');
            option.setAttribute('data-stock', product.stock);
            option.setAttribute('data-unidades', product.unidades_por_caja || 1);
            option.setAttribute('data-medidas', product.medidas || '');
            select.appendChild(option);
        });
    }
    
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
    
    // Verificar si es bodega de Buenaventura
    const isBuenaventura = quantityInput.getAttribute('data-is-buenaventura') === 'true';
    
    const selectedOption = productSelect.selectedOptions[0];
    if (selectedOption && selectedOption.value) {
        const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
        const tipo = selectedOption.getAttribute('data-tipo');
        const unidades = parseInt(selectedOption.getAttribute('data-unidades')) || 1;
        const quantity = parseInt(quantityInput.value) || 0;
        
        if (isBuenaventura) {
            // Para Buenaventura: cantidad ingresada es en cajas, convertir a láminas para validar
            const laminasRequeridas = quantity * unidades;
            const cajasDisponibles = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
            
            if (stockInfo) {
                if (laminasRequeridas > stock) {
                    stockInfo.innerHTML = `<span style="color: #dc3545; font-weight: bold;">⚠️ Stock insuficiente. Disponible: ${cajasDisponibles} cajas (${stock} láminas)</span>`;
                } else {
                    stockInfo.innerHTML = `<span style="color: #28a745">✓ Stock disponible: ${cajasDisponibles} cajas (${stock} láminas)</span>`;
                }
            }
        } else {
            // Para otras bodegas: cantidad ingresada es en láminas
            const laminasRequeridas = quantity;
            const cajas = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
            
            if (stockInfo) {
                if (laminasRequeridas > stock) {
                    stockInfo.innerHTML = `<span style="color: #dc3545; font-weight: bold;">⚠️ Stock insuficiente. Disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`;
                } else {
                    stockInfo.innerHTML = `<span style="color: #28a745">✓ Stock disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`;
                }
            }
        }
    } else {
        // Si no hay producto seleccionado, limpiar la información
        if (stockInfo) stockInfo.innerHTML = '';
    }
}

// Cargar productos al iniciar si hay una bodega seleccionada
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Iniciando carga de productos');
    
    // Esperar un momento para asegurar que el DOM esté completamente cargado
    setTimeout(function() {
        // Buscar bodega en select o en input hidden
        let warehouseId = null;
        const warehouseElement = document.getElementById('warehouse_id');
        
        if (warehouseElement) {
            warehouseId = warehouseElement.value;
            console.log('Bodega encontrada:', warehouseId, 'Tipo:', warehouseElement.tagName);
        } else {
            // Intentar buscar por name attribute como fallback
            const warehouseByName = document.querySelector('input[name="warehouse_id"], select[name="warehouse_id"]');
            if (warehouseByName) {
                warehouseId = warehouseByName.value;
                console.log('Bodega encontrada por name attribute:', warehouseId);
            } else {
                console.warn('No se encontró el elemento warehouse_id');
            }
        }
        
        if (warehouseId && warehouseId !== '') {
            console.log('Cargando productos iniciales para bodega:', warehouseId);
            updateWarehouseType();
            loadProductsForWarehouse();
        } else {
            console.log('No hay bodega seleccionada inicialmente o el valor está vacío');
        }
    }, 100); // Pequeño delay para asegurar que el DOM esté listo
});
</script>
@endsection

