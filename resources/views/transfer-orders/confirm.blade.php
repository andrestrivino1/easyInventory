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
                    
                    <div class="sheets-input-group">
                        <div class="input-field">
                            <label for="good_sheets_{{ $product->id }}">Láminas en buen estado *</label>
                            <input 
                                type="number" 
                                id="good_sheets_{{ $product->id }}" 
                                name="products[{{ $loop->index }}][good_sheets]" 
                                value="{{ old('products.'.$loop->index.'.good_sheets', $totalSheets) }}"
                                min="0" 
                                max="{{ $totalSheets }}"
                                required
                                onchange="updateBadSheets({{ $product->id }}, {{ $totalSheets }})"
                            />
                            <input type="hidden" name="products[{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                            <span class="help-text">Máximo: {{ $totalSheets }} láminas</span>
                            @error('products.'.$loop->index.'.good_sheets')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="input-field">
                            <label for="bad_sheets_{{ $product->id }}">Láminas en mal estado *</label>
                            <input 
                                type="number" 
                                id="bad_sheets_{{ $product->id }}" 
                                name="products[{{ $loop->index }}][bad_sheets]" 
                                value="{{ old('products.'.$loop->index.'.bad_sheets', 0) }}"
                                min="0" 
                                max="{{ $totalSheets }}"
                                required
                                onchange="updateGoodSheets({{ $product->id }}, {{ $totalSheets }})"
                            />
                            <span class="help-text">Máximo: {{ $totalSheets }} láminas</span>
                            @error('products.'.$loop->index.'.bad_sheets')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div style="margin-top: 8px; font-size: 12px; color: #666;">
                        <strong>Total recibido:</strong> <span id="total_{{ $product->id }}">{{ $totalSheets }}</span> láminas
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
function updateBadSheets(productId, maxSheets) {
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    const totalSpan = document.getElementById('total_' + productId);
    
    const goodSheets = parseInt(goodSheetsInput.value) || 0;
    const badSheets = maxSheets - goodSheets;
    
    if (badSheets >= 0) {
        badSheetsInput.value = badSheets;
    } else {
        goodSheetsInput.value = maxSheets;
        badSheetsInput.value = 0;
    }
    
    updateTotal(productId, maxSheets);
}

function updateGoodSheets(productId, maxSheets) {
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    const totalSpan = document.getElementById('total_' + productId);
    
    const badSheets = parseInt(badSheetsInput.value) || 0;
    const goodSheets = maxSheets - badSheets;
    
    if (goodSheets >= 0) {
        goodSheetsInput.value = goodSheets;
    } else {
        badSheetsInput.value = maxSheets;
        goodSheetsInput.value = 0;
    }
    
    updateTotal(productId, maxSheets);
}

function updateTotal(productId, maxSheets) {
    const goodSheetsInput = document.getElementById('good_sheets_' + productId);
    const badSheetsInput = document.getElementById('bad_sheets_' + productId);
    const totalSpan = document.getElementById('total_' + productId);
    
    const goodSheets = parseInt(goodSheetsInput.value) || 0;
    const badSheets = parseInt(badSheetsInput.value) || 0;
    const total = goodSheets + badSheets;
    
    totalSpan.textContent = total;
    
    if (total > maxSheets) {
        totalSpan.style.color = '#d60000';
        totalSpan.style.fontWeight = 'bold';
    } else {
        totalSpan.style.color = '#666';
        totalSpan.style.fontWeight = 'normal';
    }
}

// Validar antes de enviar
document.getElementById('confirmForm').addEventListener('submit', function(e) {
    const products = @json($transferOrder->products);
    let isValid = true;
    let errorMessage = '';
    
    products.forEach((product, index) => {
        const quantity = product.pivot.quantity;
        const totalSheets = product.tipo_medida === 'caja' && product.unidades_por_caja > 0 
            ? quantity * product.unidades_por_caja 
            : quantity;
        
        const goodSheets = parseInt(document.getElementById('good_sheets_' + product.id).value) || 0;
        const badSheets = parseInt(document.getElementById('bad_sheets_' + product.id).value) || 0;
        const total = goodSheets + badSheets;
        
        if (total > totalSheets) {
            isValid = false;
            errorMessage = `El total de láminas recibidas (${total}) no puede exceder la cantidad enviada (${totalSheets}) para el producto ${product.nombre}.`;
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

