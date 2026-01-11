@extends('layouts.app')

@section('content')
<style>
.form-bg {
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
    width: 800px;
    max-width: 95%;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    margin: auto;
}
.form-container h2 {
    margin-top: 0; 
    font-size: 20px; 
    color: #222; 
    margin-bottom: 18px; 
    font-weight: 700;
}
.info-section {
    background: #f8fafc;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}
.info-label {
    font-weight: bold;
    color: #666;
}
.info-value {
    color: #333;
}
.product-item {
    background: #f8fafc;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}
.product-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.product-name {
    font-weight: bold;
    color: #333;
    font-size: 15px;
}
.product-quantity {
    font-size: 13px;
    color: #666;
}
.sheets-input-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-top: 10px;
}
.input-field {
    display: flex;
    flex-direction: column;
}
.input-field label {
    font-weight: bold;
    color: #333;
    margin-bottom: 6px;
    font-size: 13px;
}
.input-field input {
    padding: 10px 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    background: #fff;
    transition: box-shadow .15s, border-color .15s;
}
.input-field input:focus {
    border-color: #4a8af4;
    outline: none;
    box-shadow: 0 0 4px rgba(74,138,244,0.14);
}
.input-field input:disabled {
    background: #e9ecef;
    cursor: not-allowed;
}
.help-text {
    font-size: 11px;
    color: #666;
    margin-top: 4px;
}
.actions {
    display: flex;
    justify-content: space-between;
    margin-top: 25px;
}
.btn-cancel {
    background: transparent;
    border: none;
    color: #4a8af4;
    font-size: 15px;
    cursor: pointer;
    text-decoration: underline;
    font-weight: 500;
}
.btn-save {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
}
.btn-save:hover {
    background: #218838;
}
.invalid-feedback {
    color: #d60000;
    font-size: 12px;
    margin-top: 4px;
}
</style>

<div class="form-bg">
    <div class="form-container">
        <h2>Confirmar Recepción de Transferencia</h2>
        
        <div class="info-section">
            <div class="info-row">
                <span class="info-label">Origen:</span>
                <span class="info-value">{{ $transferOrder->from->nombre ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Destino:</span>
                <span class="info-value">{{ $transferOrder->to->nombre ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value">{{ $transferOrder->date ? \Carbon\Carbon::parse($transferOrder->date)->format('d/m/Y') : '-' }}</span>
            </div>
            @if($transferOrder->driver)
            <div class="info-row">
                <span class="info-label">Conductor:</span>
                <span class="info-value">{{ $transferOrder->driver->nombre }}</span>
            </div>
            @endif
        </div>

        <form method="POST" action="{{ route('transfer-orders.confirm.store', $transferOrder) }}" id="confirmForm">
            @csrf
            
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #333;">Ingrese la cantidad de láminas recibidas por producto:</h3>
            
            @foreach($transferOrder->products as $product)
                @php
                    $quantity = $product->pivot->quantity;
                    // Si el producto es por caja, convertir a láminas
                    if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                        $totalSheets = $quantity * $product->unidades_por_caja;
                    } else {
                        $totalSheets = $quantity;
                    }
                @endphp
                <div class="product-item">
                    <div class="product-header">
                        <div>
                            <div class="product-name">{{ $product->nombre }}</div>
                            <div class="product-quantity">
                                Cantidad enviada: {{ $quantity }} {{ $product->tipo_medida === 'caja' ? 'cajas' : 'láminas' }}
                                @if($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0)
                                    ({{ $totalSheets }} láminas)
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="input-field" style="margin-bottom: 15px;">
                        <label for="receive_by_{{ $product->id }}">Recibir por: *</label>
                        <select 
                            id="receive_by_{{ $product->id }}" 
                            name="products[{{ $loop->index }}][receive_by]" 
                            class="receive-by-select"
                            required
                            style="padding: 10px 16px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; background: #fff; width: 100%;"
                            onchange="toggleReceiveMode({{ $product->id }}, {{ $quantity }}, {{ $product->unidades_por_caja ?? 0 }}, '{{ $product->tipo_medida }}')"
                        >
                            <option value="">Seleccione una opción</option>
                            <option value="cajas" {{ old('products.'.$loop->index.'.receive_by') == 'cajas' ? 'selected' : '' }}>Cajas</option>
                            <option value="laminas" {{ old('products.'.$loop->index.'.receive_by', 'laminas') == 'laminas' ? 'selected' : '' }}>Láminas</option>
                        </select>
                        <input type="hidden" name="products[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                        @error('products.'.$loop->index.'.receive_by')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="sheets-input-group">
                        <div class="input-field">
                            <label for="good_sheets_{{ $product->id }}" id="label_good_{{ $product->id }}">Láminas en buen estado *</label>
                            <input 
                                type="number" 
                                id="good_sheets_{{ $product->id }}" 
                                name="products[{{ $loop->index }}][good_sheets]" 
                                value="{{ old('products.'.$loop->index.'.good_sheets', $totalSheets) }}"
                                min="0" 
                                max="{{ $totalSheets }}"
                                required
                                step="1"
                                onchange="updateBadSheets({{ $product->id }}, {{ $totalSheets }}, '{{ $product->tipo_medida }}', {{ $product->unidades_por_caja ?? 0 }})"
                            />
                            <span class="help-text" id="help_good_{{ $product->id }}">Máximo: {{ $totalSheets }} láminas</span>
                            @error('products.'.$loop->index.'.good_sheets')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="input-field">
                            <label for="bad_sheets_{{ $product->id }}" id="label_bad_{{ $product->id }}">Láminas en mal estado *</label>
                            <input 
                                type="number" 
                                id="bad_sheets_{{ $product->id }}" 
                                name="products[{{ $loop->index }}][bad_sheets]" 
                                value="{{ old('products.'.$loop->index.'.bad_sheets', 0) }}"
                                min="0" 
                                max="{{ $totalSheets }}"
                                required
                                step="1"
                                onchange="updateGoodSheets({{ $product->id }}, {{ $totalSheets }}, '{{ $product->tipo_medida }}', {{ $product->unidades_por_caja ?? 0 }})"
                            />
                            <span class="help-text" id="help_bad_{{ $product->id }}">Máximo: {{ $totalSheets }} láminas</span>
                            @error('products.'.$loop->index.'.bad_sheets')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div style="margin-top: 8px; font-size: 12px; color: #666;">
                        <strong>Total recibido:</strong> <span id="total_{{ $product->id }}">{{ $totalSheets }}</span> <span id="total_unit_{{ $product->id }}">láminas</span>
                    </div>
                </div>
            @endforeach
            
            <div class="actions">
                <a href="{{ route('transfer-orders.index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Confirmar Recepción</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleReceiveMode(productId, quantity, unidadesPorCaja, tipoMedida) {
    const receiveBySelect = document.getElementById('receive_by_' + productId);
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    const labelGood = document.getElementById('label_good_' + productId);
    const labelBad = document.getElementById('label_bad_' + productId);
    const helpGood = document.getElementById('help_good_' + productId);
    const helpBad = document.getElementById('help_bad_' + productId);
    const totalSpan = document.getElementById('total_' + productId);
    const totalUnitSpan = document.getElementById('total_unit_' + productId);
    
    const receiveBy = receiveBySelect.value;
    
    if (receiveBy === 'cajas') {
        // Recibir por cajas
        // La cantidad enviada (quantity) siempre está en la unidad original (cajas o láminas)
        // Si tipo_medida es 'caja', quantity ya está en cajas
        // Si no es 'caja', quantity está en láminas y debemos convertir a cajas
        let maxBoxes;
        if (tipoMedida === 'caja') {
            // Ya está en cajas
            maxBoxes = quantity;
        } else {
            // Está en láminas, convertir a cajas si hay unidades_por_caja
            maxBoxes = unidadesPorCaja > 0 ? Math.floor(quantity / unidadesPorCaja) : quantity;
        }
        
        goodSheetsInput.max = maxBoxes;
        badSheetsInput.max = maxBoxes;
        goodSheetsInput.value = Math.min(parseInt(goodSheetsInput.value) || maxBoxes, maxBoxes);
        badSheetsInput.value = Math.min(parseInt(badSheetsInput.value) || 0, maxBoxes);
        
        labelGood.textContent = 'Cajas en buen estado *';
        labelBad.textContent = 'Cajas en mal estado *';
        helpGood.textContent = 'Máximo: ' + maxBoxes + ' cajas';
        helpBad.textContent = 'Máximo: ' + maxBoxes + ' cajas';
        totalUnitSpan.textContent = 'cajas';
        
        // Actualizar total considerando cajas
        updateTotal(productId, maxBoxes, 'cajas');
    } else if (receiveBy === 'laminas') {
        // Recibir por láminas
        // Si tipo_medida es 'caja', convertir cajas a láminas
        // Si no es 'caja', quantity ya está en láminas
        const maxSheets = tipoMedida === 'caja' && unidadesPorCaja > 0 ? quantity * unidadesPorCaja : quantity;
        
        goodSheetsInput.max = maxSheets;
        badSheetsInput.max = maxSheets;
        goodSheetsInput.value = Math.min(parseInt(goodSheetsInput.value) || maxSheets, maxSheets);
        badSheetsInput.value = Math.min(parseInt(badSheetsInput.value) || 0, maxSheets);
        
        labelGood.textContent = 'Láminas en buen estado *';
        labelBad.textContent = 'Láminas en mal estado *';
        helpGood.textContent = 'Máximo: ' + maxSheets + ' láminas';
        helpBad.textContent = 'Máximo: ' + maxSheets + ' láminas';
        totalUnitSpan.textContent = 'láminas';
        
        // Actualizar total considerando láminas
        updateTotal(productId, maxSheets, 'laminas');
    }
}

function updateBadSheets(productId, maxValue, tipoMedida, unidadesPorCaja) {
    const receiveBySelect = document.getElementById('receive_by_' + productId);
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    
    const receiveBy = receiveBySelect.value;
    if (!receiveBy) return;
    
    const goodValue = parseInt(goodSheetsInput.value) || 0;
    const badValue = maxValue - goodValue;
    
    if (badValue >= 0) {
        badSheetsInput.value = badValue;
    } else {
        goodSheetsInput.value = maxValue;
        badSheetsInput.value = 0;
    }
    
    updateTotal(productId, maxValue, receiveBy);
}

function updateGoodSheets(productId, maxValue, tipoMedida, unidadesPorCaja) {
    const receiveBySelect = document.getElementById('receive_by_' + productId);
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    
    const receiveBy = receiveBySelect.value;
    if (!receiveBy) return;
    
    const badValue = parseInt(badSheetsInput.value) || 0;
    const goodValue = maxValue - badValue;
    
    if (goodValue >= 0) {
        goodSheetsInput.value = goodValue;
    } else {
        badSheetsInput.value = maxValue;
        goodSheetsInput.value = 0;
    }
    
    updateTotal(productId, maxValue, receiveBy);
}

function updateTotal(productId, maxValue, receiveBy) {
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    const totalSpan = document.getElementById('total_' + productId);
    
    const goodValue = parseInt(goodSheetsInput.value) || 0;
    const badValue = parseInt(badSheetsInput.value) || 0;
    const total = goodValue + badValue;
    
    totalSpan.textContent = total;
    
    if (total > maxValue) {
        totalSpan.style.color = '#d60000';
        totalSpan.style.fontWeight = 'bold';
    } else {
        totalSpan.style.color = '#666';
        totalSpan.style.fontWeight = 'normal';
    }
}

// Inicializar los campos cuando la página carga
document.addEventListener('DOMContentLoaded', function() {
    const products = @json($transferOrder->products);
    products.forEach((product) => {
        const receiveBySelect = document.getElementById('receive_by_' + product.id);
        if (receiveBySelect) {
            const quantity = product.pivot.quantity;
            const unidadesPorCaja = product.unidades_por_caja || 0;
            const tipoMedida = product.tipo_medida || '';
            
            // Si tiene un valor seleccionado (por old() o selección previa), usar ese valor
            if (receiveBySelect.value) {
                toggleReceiveMode(product.id, quantity, unidadesPorCaja, tipoMedida);
            } else {
                // Si no tiene valor, establecer "laminas" por defecto y luego inicializar
                receiveBySelect.value = 'laminas';
                toggleReceiveMode(product.id, quantity, unidadesPorCaja, tipoMedida);
            }
        }
    });
});

// Validar antes de enviar
document.getElementById('confirmForm').addEventListener('submit', function(e) {
    const products = @json($transferOrder->products);
    let isValid = true;
    let errorMessage = '';
    
    products.forEach((product, index) => {
        const receiveBySelect = document.getElementById('receive_by_' + product.id);
        if (!receiveBySelect || !receiveBySelect.value) {
            isValid = false;
            errorMessage = `Debe seleccionar cómo recibir el producto: ${product.nombre}`;
            return;
        }
        
        const receiveBy = receiveBySelect.value;
        const quantity = product.pivot.quantity;
        const unidadesPorCaja = product.unidades_por_caja || 0;
        const tipoMedida = product.tipo_medida;
        
        let maxValue;
        let unitName;
        
        if (receiveBy === 'cajas') {
            maxValue = tipoMedida === 'caja' ? quantity : Math.floor(quantity / unidadesPorCaja);
            unitName = 'cajas';
        } else {
            maxValue = tipoMedida === 'caja' && unidadesPorCaja > 0 ? quantity * unidadesPorCaja : quantity;
            unitName = 'láminas';
        }
        
        const goodValue = parseInt(document.getElementById('good_sheets_' + product.id).value) || 0;
        const badValue = parseInt(document.getElementById('bad_sheets_' + product.id).value) || 0;
        const total = goodValue + badValue;
        
        if (total > maxValue) {
            isValid = false;
            errorMessage = `El total de ${unitName} recibidas (${total}) no puede exceder la cantidad enviada (${maxValue} ${unitName}) para el producto ${product.nombre}.`;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        alert(errorMessage);
        return false;
    }
});
</script>
@endsection

