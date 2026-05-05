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
            width: 700px;
            max-width: 95%;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            margin: auto;
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
            margin-left: 0;
            margin-right: 0;
            max-width: none;
        }

        .form-container input,
        .form-container textarea,
        .form-container select {
            display: block;
            width: 100%;
            padding: 10px 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 14px;
            font-size: 14px;
            background: #f8fafc;
            transition: box-shadow .15s, border-color .15s;
            margin-left: 0;
            margin-right: 0;
            max-width: none;
            box-sizing: border-box;
        }

        .form-container input:focus,
        .form-container textarea:focus,
        .form-container select:focus {
            border-color: #4a8af4;
            outline: none;
            box-shadow: 0 0 4px rgba(74, 138, 244, 0.14);
            background: #fff;
        }

        .form-container input:disabled,
        .form-container select:disabled,
        .form-container textarea:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn-cancel {
            background: transparent;
            border: none;
            color: #4a8af4;
            font-size: 15px;
            cursor: pointer;
            text-decoration: underline;
            font-weight: 500;
            padding-left: 0;
            padding-right: 0;
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

        .btn-save:hover {
            background: #2f6fe0;
        }

        .invalid-feedback {
            color: #d60000;
            font-size: 13px;
            margin-top: -10px;
            margin-bottom: 8px;
            margin-left: 0;
            margin-right: 0;
            max-width: none;
            text-align: left;
        }

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

        .product-fields>div {
            grid-column: span 1;
        }

        .product-fields>div.full-width {
            grid-column: span 2;
        }

        .stock-info {
            font-size: 12px;
            color: #666;
            margin-top: -8px;
            margin-bottom: 8px;
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
            <h2>Editar transferencia</h2>
            <form method="POST" action="{{ route('transfer-orders.update', $transferOrder) }}" autocomplete="off"
                id="transferForm">
                @csrf
                @method('PUT')

                @php
                    $isEditable = $transferOrder->status === 'en_transito';
                @endphp

                <label for="warehouse_from_id">Bodega origen*</label>
                <select name="warehouse_from_id" id="warehouse_from_id" required @if(!$isEditable) disabled @endif
                    onchange="loadProductsForWarehouse()">
                    <option value="">Seleccione</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @if(old('warehouse_from_id', $transferOrder->warehouse_from_id) == $wh->id)
                        selected @endif>{{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}</option>
                    @endforeach
                </select>
                @if(!$isEditable) <input type="hidden" name="warehouse_from_id"
                value="{{ $transferOrder->warehouse_from_id }}"> @endif
                @error('warehouse_from_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <label for="warehouse_to_id">Bodega destino*</label>
                <select name="warehouse_to_id" id="warehouse_to_id" required @if(!$isEditable) disabled @endif>
                    <option value="">Seleccione</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" @if(old('warehouse_to_id', $transferOrder->warehouse_to_id) == $wh->id)
                        selected @endif>{{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}</option>
                    @endforeach
                </select>
                @if(!$isEditable) <input type="hidden" name="warehouse_to_id" value="{{ $transferOrder->warehouse_to_id }}">
                @endif
                @error('warehouse_to_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <div id="pablo-rojas-info"
                    style="display:none; background:#e3f2fd; padding:10px; border-radius:6px; margin-bottom:15px; font-size:13px; color:#1565c0;">
                    ℹ️ Desde Pablo Rojas solo se pueden despachar productos medidos en Cajas
                </div>

                @if($isEditable)
                    <div style="margin-top: 20px; margin-bottom: 10px;">
                        <label style="margin-bottom: 10px;">Productos a transferir*</label>
                        <button type="button" class="btn-add-product" onclick="addProduct()">+ Agregar Producto</button>
                    </div>
                @else
                    <div style="margin-top: 20px; margin-bottom: 10px;">
                        <label style="margin-bottom: 10px;">Productos de la transferencia</label>
                    </div>
                @endif

                <div id="products-container">
                    <!-- Los productos se agregarán aquí dinámicamente -->
                </div>

                @error('products') <div class="invalid-feedback">{{ $message }}</div>@enderror

                @php
                    $useExternalDriver = $transferOrder->isExternalDriver();
                @endphp
                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                        <input type="checkbox" name="use_external_driver" id="use_external_driver" value="1"
                            {{ old('use_external_driver', $useExternalDriver) ? 'checked' : '' }}
                            @if(!$isEditable) disabled @endif style="width: auto; margin: 0;">
                        <span>Conductor externo (no registrado)</span>
                    </label>
                </div>

                <div id="driver-registered-block" style="{{ $useExternalDriver ? 'display:none;' : '' }}">
                    <label for="driver_id">Placa del Vehículo*</label>
                    <select name="driver_id" id="driver_id" onchange="setConductorFromPlate(this)" @if(!$isEditable) disabled @endif>
                        <option value="">Seleccione</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" data-name="{{ $driver->name }}" data-id="{{ $driver->identity }}"
                                @if(old('driver_id', $transferOrder->driver_id) == $driver->id) selected @endif>
                                {{ $driver->vehicle_plate }} - {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                    @if(!$isEditable) <input type="hidden" name="driver_id" value="{{ $transferOrder->driver_id }}"> @endif
                    @error('driver_id') <div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label for="conductor_show">Conductor</label>
                    <input type="text" id="conductor_show"
                        value="{{ old('conductor_show', $transferOrder->driver ? ($transferOrder->driver->name . ' (' . $transferOrder->driver->identity . ')') : '') }}"
                        readonly style="background:#e9ecef; pointer-events:none;">
                </div>

                <div id="driver-external-block" style="{{ $useExternalDriver ? '' : 'display:none;' }}">
                    <label for="external_driver_name">Nombre del conductor*</label>
                    <input type="text" name="external_driver_name" id="external_driver_name"
                        value="{{ old('external_driver_name', $transferOrder->external_driver_name) }}" maxlength="255"
                        @if(!$isEditable) readonly @endif>
                    @error('external_driver_name') <div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label for="external_driver_identity">Cédula / NIT*</label>
                    <input type="text" name="external_driver_identity" id="external_driver_identity"
                        value="{{ old('external_driver_identity', $transferOrder->external_driver_identity) }}" maxlength="50"
                        @if(!$isEditable) readonly @endif>
                    @error('external_driver_identity') <div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label for="external_driver_plate">Placa del vehículo*</label>
                    <input type="text" name="external_driver_plate" id="external_driver_plate"
                        value="{{ old('external_driver_plate', $transferOrder->external_driver_plate) }}" maxlength="50"
                        @if(!$isEditable) readonly @endif>
                    @error('external_driver_plate') <div class="invalid-feedback">{{ $message }}</div>@enderror
                    <label for="external_driver_phone">Teléfono del conductor</label>
                    <input type="text" name="external_driver_phone" id="external_driver_phone"
                        value="{{ old('external_driver_phone', $transferOrder->external_driver_phone) }}" maxlength="20"
                        @if(!$isEditable) readonly @endif placeholder="Número de teléfono">
                    @error('external_driver_phone') <div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <label for="note">Notas</label>
                <textarea name="note" id="note" rows="2" placeholder="Opcional" @if(!$isEditable) readonly
                @endif>{{ old('note', $transferOrder->note) }}</textarea>
                @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <div style="margin-top: 15px; margin-bottom: 15px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                        <input type="checkbox" name="show_signatures" id="show_signatures" value="1" {{ old('show_signatures', session("transfer_signatures_{$transferOrder->id}")) ? 'checked' : '' }}
                            @if(!$isEditable) disabled @endif style="width: auto; margin: 0;">
                        <span>Mostrar campos de firma (Nombre y NIT/Cédula) en el PDF</span>
                    </label>
                </div>

                <div id="weight-box"
                    style="margin-top: 10px; background: #e8f5e9; padding: 10px; border-radius: 8px; border: 1px solid #c8e6c9;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span id="weight-title" style="font-weight: bold; color: #2e7d32;">Peso total de la
                            transferencia:</span>
                        <span id="total-transfer-weight" style="font-weight: bold; color: #1b5e20; font-size: 16px;">0.00
                            kg</span>
                    </div>
                    <div id="capacity-alert"
                        style="display: none; margin-top: 5px; color: #d32f2f; font-weight: bold; font-size: 13px;">
                        ⚠️ El peso total excede la capacidad de carga del conductor (<span
                            id="driver-capacity-val">0.00</span> kg).
                    </div>
                </div>

                <div class="actions">
                    <a href="{{ route('transfer-orders.index') }}" class="btn-cancel">Cancelar</a>
                    @if($isEditable)
                        <button type="submit" class="btn-save" id="btn-save">Guardar</button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <script>
        let productIndex = 0;
        let availableProducts = [];
        const driverCapacities = @json($drivers->pluck('capacity', 'id'));
        const existingProducts = @json($transferOrder->products ?? []);
        const ID_PABLO_ROJAS = 1;
        const isEditable = @json($transferOrder->status === 'en_transito');

        function toggleDriverBlocks() {
            const useExternal = document.getElementById('use_external_driver').checked;
            const blockReg = document.getElementById('driver-registered-block');
            const blockExt = document.getElementById('driver-external-block');
            const driverSelect = document.getElementById('driver_id');
            const extName = document.getElementById('external_driver_name');
            const extId = document.getElementById('external_driver_identity');
            const extPlate = document.getElementById('external_driver_plate');
            const extPhone = document.getElementById('external_driver_phone');
            if (useExternal) {
                if (blockReg) blockReg.style.display = 'none';
                if (blockExt) blockExt.style.display = 'block';
                if (driverSelect && !driverSelect.disabled) { driverSelect.removeAttribute('required'); driverSelect.value = ''; }
                if (extName && !extName.readOnly) { extName.setAttribute('required', 'required'); }
                if (extId && !extId.readOnly) extId.setAttribute('required', 'required');
                if (extPlate && !extPlate.readOnly) extPlate.setAttribute('required', 'required');
                if (document.getElementById('conductor_show')) document.getElementById('conductor_show').value = '';
            } else {
                if (blockReg) blockReg.style.display = 'block';
                if (blockExt) blockExt.style.display = 'none';
                if (driverSelect && !driverSelect.disabled) driverSelect.setAttribute('required', 'required');
                if (extName && !extName.readOnly) extName.removeAttribute('required');
                if (extId && !extId.readOnly) extId.removeAttribute('required');
                if (extPlate && !extPlate.readOnly) extPlate.removeAttribute('required');
            }
            calculateTotalWeightAndCheckCapacity();
        }

        function setConductorFromPlate(sel) {
            let n = sel.options[sel.selectedIndex].getAttribute('data-name');
            let cid = sel.options[sel.selectedIndex].getAttribute('data-id');
            document.getElementById('conductor_show').value = (n && cid) ? (n + ' (' + cid + ')') : '';
            calculateTotalWeightAndCheckCapacity();
        }

        function calculateTotalWeightAndCheckCapacity() {
            let totalWeight = 0;
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach((item, idx) => {
                const productSelect = document.getElementById(`product-select-${idx}`);
                const quantityInput = document.getElementById(`quantity-${idx}`);

                // If readonly/static mode (no select), we might need to get weight from availableProducts if loaded,
                // or from existingProducts.
                if (isEditable && productSelect && productSelect.value !== "" && quantityInput) {
                    const product = availableProducts[productSelect.value];
                    if (product) {
                        const quantity = parseInt(quantityInput.value) || 0;
                        const weightPerBox = parseFloat(product.weight_per_box) || 0;
                        totalWeight += (quantity * weightPerBox);
                    }
                } else if (!isEditable && existingProducts[idx]) {
                    // Solo consulta: usar peso de existingProducts si el pivot tiene weight?
                    // actually, existingProducts items in pivot might not have weight yet for old transfers.
                    // But for new ones we might need it.
                }
            });

            const totalWeightDisplay = document.getElementById('total-transfer-weight');
            if (totalWeightDisplay) {
                totalWeightDisplay.textContent = totalWeight.toFixed(2) + ' kg';
            }

            const driverSelect = document.getElementById('driver_id');
            const alertDiv = document.getElementById('capacity-alert');
            const saveBtn = document.getElementById('btn-save');
            const capacitySpan = document.getElementById('driver-capacity-val');
            const weightBox = document.getElementById('weight-box');
            const weightTitle = document.getElementById('weight-title');
            const weightVal = document.getElementById('total-transfer-weight');

            const useExternalDriver = document.getElementById('use_external_driver') && document.getElementById('use_external_driver').checked;
            if (useExternalDriver) {
                if (alertDiv) alertDiv.style.display = 'none';
                if (saveBtn) { saveBtn.disabled = false; saveBtn.style.opacity = '1'; saveBtn.style.cursor = 'pointer'; }
                if (weightBox) { weightBox.style.background = '#e8f5e9'; weightBox.style.borderColor = '#c8e6c9'; }
                if (weightTitle) weightTitle.style.color = '#2e7d32';
                if (weightVal) weightVal.style.color = '#1b5e20';
            } else if (driverSelect && driverSelect.value !== "") {
                const driverId = driverSelect.value;
                const capacity = parseFloat(driverCapacities[driverId]) || 0;

                if (capacitySpan) capacitySpan.textContent = capacity.toFixed(2);

                if (capacity > 0 && totalWeight > capacity && isEditable) {
                    if (alertDiv) alertDiv.style.display = 'block';
                    if (saveBtn) {
                        saveBtn.disabled = true;
                        saveBtn.style.opacity = '0.5';
                        saveBtn.style.cursor = 'not-allowed';
                    }
                    if (weightBox) {
                        weightBox.style.background = '#ffebee';
                        weightBox.style.borderColor = '#ffcdd2';
                    }
                    if (weightTitle) weightTitle.style.color = '#c62828';
                    if (weightVal) weightVal.style.color = '#b71c1c';
                } else {
                    if (alertDiv) alertDiv.style.display = 'none';
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.style.opacity = '1';
                        saveBtn.style.cursor = 'pointer';
                    }
                    if (weightBox) {
                        weightBox.style.background = '#e8f5e9';
                        weightBox.style.borderColor = '#c8e6c9';
                    }
                    if (weightTitle) weightTitle.style.color = '#2e7d32';
                    if (weightVal) weightVal.style.color = '#1b5e20';
                }
            } else {
                if (alertDiv) alertDiv.style.display = 'none';
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.style.opacity = '1';
                    saveBtn.style.cursor = 'pointer';
                }
                if (weightBox) {
                    weightBox.style.background = '#e8f5e9';
                    weightBox.style.borderColor = '#c8e6c9';
                }
                if (weightTitle) weightTitle.style.color = '#2e7d32';
                if (weightVal) weightVal.style.color = '#1b5e20';
            }
        }

        async function loadProductsForWarehouse() {
            const warehouseFrom = document.getElementById('warehouse_from_id');
            const infoDiv = document.getElementById('pablo-rojas-info');

            if (!warehouseFrom || !warehouseFrom.value || warehouseFrom.disabled) {
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
                const forEditTransferId = {{ $transferOrder->id }};
                const url = `{{ route('transfer-orders.get-products', ':id') }}`.replace(':id', warehouseId) + '?for_edit_transfer_id=' + forEditTransferId;
                const response = await fetch(url);
                if (!response.ok) throw new Error('Error al cargar productos');

                availableProducts = await response.json();
                updateProductSelects();

                // Restaurar selecciones existentes después de actualizar
                if (availableProducts.length > 0) {
                    restoreProductSelections();
                }
            } catch (error) {
                console.error('Error:', error);
                availableProducts = [];
                updateProductSelects();
            }
        }

        function updateProductSelects() {
            const productSelects = document.querySelectorAll('select[id^="product-select-"]');
            productSelects.forEach(select => {
                if (select.disabled) return;

                const selectedValue = select.value;

                // Limpiar opciones
                select.innerHTML = '<option value="">Seleccione un producto</option>';

                // Agregar productos disponibles
                availableProducts.forEach((product, index) => {
                    const option = document.createElement('option');
                    let displayText = `${product.nombre}`;
                    if (product.medidas) {
                        displayText += ` - ${product.medidas}`;
                    }
                    displayText += ` (${product.codigo})`;

                    if (product.sheets_per_box) {
                        displayText += ` - ${product.sheets_per_box} láminas/caja`;
                    }

                    if (product.containers && product.containers.length === 1) {
                        displayText += ` [${product.containers[0].reference}]`;
                    }

                    option.value = index; // Use ARRAY INDEX
                    option.textContent = displayText;

                    select.appendChild(option);
                });

                // No restauramos selección ciega por índice aquí porque en Edit es complejo mapping
            });
        }

        function restoreProductSelections() {
            // Restaurar selecciones de productos existentes (producto + contenedor + cantidad)
            existingProducts.forEach((product, idx) => {
                const productItem = document.getElementById(`product-${idx}`);
                if (!productItem) return;

                const productSelect = productItem.querySelector(`select[id^="product-select-"]`);
                if (!productSelect || productSelect.disabled) return;

                const pivot = product.pivot || {};
                const containerId = pivot.container_id;
                const sheetsPerBox = pivot.sheets_per_box;

                // Buscar el índice en availableProducts: mismo producto Y contenedor que coincida
                let foundIndex = availableProducts.findIndex(p => {
                    if (p.id != product.id) return false;
                    const sheetMatch = !sheetsPerBox || p.sheets_per_box == sheetsPerBox;
                    const containerMatch = !containerId || (p.containers || []).some(c => c.id == containerId);
                    return sheetMatch && (containerMatch || (p.containers || []).length === 0);
                });

                // Fallback: solo por producto y sheets_per_box
                if (foundIndex === -1 && sheetsPerBox) {
                    foundIndex = availableProducts.findIndex(p => p.id == product.id && p.sheets_per_box == sheetsPerBox);
                }
                if (foundIndex === -1) {
                    foundIndex = availableProducts.findIndex(p => p.id == product.id);
                }

                if (foundIndex !== -1) {
                    productSelect.value = foundIndex;
                    const index = productSelect.id.replace('product-select-', '');
                    loadContainersForProduct(index);

                    if (containerId) {
                        const containerSelect = document.getElementById(`container-select-${index}`);
                        if (containerSelect) {
                            containerSelect.value = containerId;
                            updateStockForContainer(index);
                        }
                    }
                    updateProductInfo(index);
                    calculateTotalWeightAndCheckCapacity();
                }
            });
        }

        function addProduct(existingProduct = null) {
            const container = document.getElementById('products-container');
            const productItem = document.createElement('div');
            productItem.className = 'product-item';
            productItem.id = `product-${productIndex}`;

            // Values for read-only or initialization
            const selectedProductId = existingProduct ? existingProduct.id : '';
            const selectedProductName = existingProduct ? (existingProduct.nombre + ' (' + existingProduct.codigo + ')') : '';
            const selectedQuantity = existingProduct ? existingProduct.pivot.quantity : 1;
            const selectedContainerId = existingProduct && existingProduct.pivot ? (existingProduct.pivot.container_id || '') : '';
            const selectedSheets = existingProduct && existingProduct.pivot ? (existingProduct.pivot.sheets_per_box || '') : '';

            productItem.innerHTML = `
                            <div class="product-item-header">
                                <span class="product-item-title">Producto #${productIndex + 1}</span>
                                ${isEditable ? `<button type="button" class="btn-remove-product" onclick="removeProduct(${productIndex})">Eliminar</button>` : ''}
                            </div>
                            <div class="product-fields">
                                <div>
                                    <label>Producto*</label>
                                    <!-- Campos ocultos -->
                                    <input type="hidden" name="products[${productIndex}][product_id]" id="product-id-${productIndex}" value="${selectedProductId}">
                                    <input type="hidden" name="products[${productIndex}][sheets_per_box]" id="sheets-per-box-${productIndex}" value="${selectedSheets}">

                                    ${!isEditable ?
                    `<input type="text" value="${selectedProductName}" readonly class="form-control" style="background:#e9ecef;">` :
                    `<select id="product-select-${productIndex}" required onchange="loadContainersForProduct(${productIndex})">
                                            <option value="">Seleccione un producto</option>
                                        </select>`
                }
                                    <div class="stock-info" id="stock-info-${productIndex}"></div>
                                </div>
                                <div>
                                    <label for="products[${productIndex}][container_id]">Contenedor*</label>
                                    <select name="products[${productIndex}][container_id]" id="container-select-${productIndex}" required ${!isEditable ? 'disabled' : ''}>
                                        <option value="">Primero seleccione un producto</option>
                                    </select>
                                    ${!isEditable && selectedContainerId ? `<input type="hidden" name="products[${productIndex}][container_id]" value="${selectedContainerId}">` : ''}
                                </div>
                                <div>
                                    <label for="products[${productIndex}][quantity]">Cantidad*</label>
                                    <input type="number" name="products[${productIndex}][quantity]" id="quantity-${productIndex}" min="1" value="${selectedQuantity}" required oninput="updateProductInfo(${productIndex})" ${!isEditable ? 'readonly' : ''}>
                                </div>
                            </div>
                        `;

            container.appendChild(productItem);
            productIndex++;

            if (isEditable) {
                updateProductSelects();
            } else {
                // En modo solo lectura, cargar contenedores si es necesario visualizar, o simplemente dejarlo estático
                // Pero necesitamos cargar options para que el select (disabled) muestre el valor correcto si se usara select disabled
                // Como usamos input text para nombre en readonly, el select no existe.
                // Pero container select sí existe disabled.
                // Para simplificar, en modo readonly, podríamos cargar solo el contenedor seleccionado si pudiéramos.
                // Pero loadContainersForProduct depende de availableProducts que se carga async.
                // Si es readonly, loadProductsForWarehouse se llama al inicio.
            }
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
            const hiddenProductId = document.getElementById(`product-id-${index}`);
            const hiddenSheets = document.getElementById(`sheets-per-box-${index}`);

            if (!containerSelect) return;

            // Logic split for Editable vs Readonly
            if (!isEditable) {
                // En readonly, el productSelect no existe (es un input text).
                // Podemos intentar llenar el containerSelect si tenemos data, pero availableProducts se carga async.
                // restoreProductSelections se encarga de esto.
                return;
            }

            const selectedIndex = productSelect.value;

            if (selectedIndex !== "") {
                const product = availableProducts[selectedIndex];

                if (hiddenProductId) hiddenProductId.value = product.id;
                if (hiddenSheets) hiddenSheets.value = product.sheets_per_box || '';

                // Cargar contenedores
                const containers = product.containers || [];
                const cajasEnContenedor = product.cajas_en_contenedor || 0;

                containerSelect.innerHTML = '<option value="">Seleccione un contenedor</option>';
                if (containers.length > 0) {
                    containers.forEach(container => {
                        const option = document.createElement('option');
                        option.value = container.id;
                        option.textContent = container.reference;
                        option.setAttribute('data-stock', container.stock || 0);
                        option.setAttribute('data-boxes', container.boxes || 0);
                        containerSelect.appendChild(option);
                    });

                    containerSelect.onchange = function () {
                        updateStockForContainer(index);
                    };

                    if (containers.length === 1) {
                        containerSelect.value = containers[0].id;
                        updateStockForContainer(index); // Esto llama a updateProductInfo
                    } else {
                        // Info inicial sin contenedor
                        showGenericStockInfo(index, product, stockInfo);
                        updateProductInfo(index);
                    }
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay contenedores disponibles';
                    option.disabled = true;
                    containerSelect.appendChild(option);

                    showGenericStockInfo(index, product, stockInfo);
                    updateProductInfo(index);
                }

            } else {
                containerSelect.innerHTML = '<option value="">Primero seleccione un producto</option>';
                if (stockInfo) stockInfo.innerHTML = '';
                if (hiddenProductId) hiddenProductId.value = '';
                if (hiddenSheets) hiddenSheets.value = '';
            }
        }

        function showGenericStockInfo(index, product, stockInfo) {
            const stock = product.stock || 0;
            const tipo = product.tipo_medida;
            const unidadesPorCaja = product.unidades_por_caja || 1;
            const cajasEnContenedor = product.cajas_en_contenedor || 0;
            const containers = product.containers || [];

            if (cajasEnContenedor > 0 && containers.length === 1) {
                const stockContenedor = cajasEnContenedor * unidadesPorCaja;
                stockInfo.innerHTML = `Stock: ${stockContenedor} unidades (${cajasEnContenedor} cajas disponibles)`;
            } else if (containers.length > 1) {
                stockInfo.innerHTML = `Seleccione un contenedor para ver el stock disponible`;
            } else if (tipo === 'caja' && unidadesPorCaja > 0) {
                const cajasDisponibles = Math.floor(stock / unidadesPorCaja);
                stockInfo.innerHTML = `Stock: ${stock} unidades (${cajasDisponibles} cajas disponibles)`;
            } else {
                stockInfo.innerHTML = `Stock: ${stock} unidades`;
            }
        }

        function updateStockForContainer(index) {
            const containerSelect = document.getElementById(`container-select-${index}`);
            const stockInfo = document.getElementById(`stock-info-${index}`);
            const productSelect = document.getElementById(`product-select-${index}`);

            if (!containerSelect || !stockInfo) return;

            // Check product info source (Select or Array via Readonly logic?)
            // In Edit mode, we rely on productSelect.
            if (!isEditable) return;

            const selectedContainerOption = containerSelect.selectedOptions[0];
            if (!selectedContainerOption || !selectedContainerOption.value) return;

            const selectedIndex = productSelect.value;
            if (selectedIndex === "") return;
            const product = availableProducts[selectedIndex];

            const tipo = product.tipo_medida || '';
            const unidadesPorCaja = product.unidades_por_caja || 1;

            const stockContenedor = parseInt(selectedContainerOption.getAttribute('data-stock')) || 0;
            const boxesContenedor = parseInt(selectedContainerOption.getAttribute('data-boxes')) || 0;

            if (boxesContenedor > 0 && unidadesPorCaja > 0) {
                stockInfo.innerHTML = `Stock: ${stockContenedor} unidades (${boxesContenedor} cajas disponibles)`;
            } else if (tipo === 'caja' && unidadesPorCaja > 0) {
                const cajasDisponibles = Math.floor(stockContenedor / unidadesPorCaja);
                stockInfo.innerHTML = `Stock: ${stockContenedor} unidades (${cajasDisponibles} cajas disponibles)`;
            } else {
                stockInfo.innerHTML = `Stock: ${stockContenedor} unidades`;
            }

            updateProductInfo(index);
        }

        function updateProductInfo(index) {
            const quantityInput = document.getElementById(`quantity-${index}`);
            const stockInfo = document.getElementById(`stock-info-${index}`);

            if (!quantityInput || !stockInfo) return;
            if (!isEditable) return; // Skip dynamic updates in readonly

            const productSelect = document.getElementById(`product-select-${index}`);
            const containerSelect = document.getElementById(`container-select-${index}`);
            if (!productSelect || !productSelect.value) return;

            const selectedIndex = productSelect.value;
            const product = availableProducts[selectedIndex];

            const tipo = product.tipo_medida || '';
            const unidadesPorCaja = product.unidades_por_caja || 1;
            const quantity = parseInt(quantityInput.value) || 0;

            let stock = 0;
            let boxes = 0;

            if (containerSelect && containerSelect.value) {
                const containerOption = containerSelect.selectedOptions[0];
                if (containerOption) {
                    stock = parseInt(containerOption.getAttribute('data-stock')) || 0;
                    boxes = parseInt(containerOption.getAttribute('data-boxes')) || 0;
                }
            } else {
                stock = product.stock || 0;
            }

            if (tipo === 'caja' && unidadesPorCaja > 0) {
                const cajasDisponibles = boxes > 0 ? boxes : Math.floor(stock / unidadesPorCaja);
                const unidadesRequeridas = quantity * unidadesPorCaja;
                stockInfo.innerHTML = `Stock: ${stock} unidades (${cajasDisponibles} cajas disponibles)<br>Requiere: ${unidadesRequeridas} unidades`;
            } else {
                const unidadesRequeridas = quantity;
                stockInfo.innerHTML = `Stock: ${stock} unidades<br>Requiere: ${unidadesRequeridas} unidades`;
            }

            calculateTotalWeightAndCheckCapacity();
        }

        document.addEventListener('DOMContentLoaded', function () {
            const useExternalEl = document.getElementById('use_external_driver');
            if (useExternalEl && !useExternalEl.disabled) {
                useExternalEl.addEventListener('change', toggleDriverBlocks);
                toggleDriverBlocks();
            }
            // Cargar productos existentes
            if (existingProducts.length > 0) {
                existingProducts.forEach(product => {
                    addProduct(product);
                });
            } else if (isEditable) {
                // Si no hay productos y es editable, agregar uno vacío
                addProduct();
            }

            // Validar
            if (isEditable) {
                document.getElementById('transferForm').addEventListener('submit', function (e) {
                    const productItems = document.querySelectorAll('.product-item');
                    if (productItems.length === 0) {
                        e.preventDefault();
                        alert('Debes agregar al menos un producto a la transferencia.');
                        return false;
                    }
                });
            }

            // Cargar productos de bodega
            const warehouseFrom = document.getElementById('warehouse_from_id');
            if (warehouseFrom && !warehouseFrom.disabled) {
                warehouseFrom.addEventListener('change', loadProductsForWarehouse);
                if (warehouseFrom.value) {
                    loadProductsForWarehouse();
                }
            } else if (warehouseFrom && warehouseFrom.disabled && warehouseFrom.value) {
                // Even if disabled, we might want to load products to populate container options?
                // In readonly mode we rely on static display mostly, but restoreProductSelections fills container dropdowns.
                loadProductsForWarehouse();
            }

            // Inicializar conductor
            const driverSelect = document.getElementById('driver_id');
            if (driverSelect) {
                setConductorFromPlate(driverSelect);
            }
        });
    </script>
@endsection