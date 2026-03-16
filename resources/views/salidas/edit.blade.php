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
        }

        .btn-save {
            background: #f09c0a;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }

        .btn-save:hover {
            background: #d4880a;
        }

        .invalid-feedback {
            color: #d60000;
            font-size: 13px;
            margin-top: -8px;
            margin-bottom: 8px;
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

        .edit-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 13px;
            margin-bottom: 16px;
        }
    </style>

    <div class="form-bg">
        <div class="form-container">
            <h2>✏️ Editar salida <span style="color:#f09c0a;">{{ $salida->salida_number }}</span></h2>
            <div class="edit-badge">⚠️ Solo el administrador puede editar salidas</div>

            <form method="POST" action="{{ route('salidas.update', $salida) }}" autocomplete="off" id="salidaForm">
                @csrf
                @method('PUT')

                @if(session('error'))
                    <div class="alert alert-danger"
                        style="margin-bottom:15px; text-align:center; padding:12px; background:#ffebee; color:#c62828; border-radius:6px;">
                        {{ session('error') }}
                    </div>
                @endif

                @php
                    $isBuenaventura = in_array($salida->warehouse_id, $bodegasBuenaventuraIds);
                @endphp

                {{-- Bodega --}}
                <label for="warehouse_id">Bodega*</label>
                <select name="warehouse_id" id="warehouse_id" required
                    onchange="updateWarehouseType(); loadProductsForWarehouse();">
                    <option value="">Seleccione</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}"
                            data-is-buenaventura="{{ in_array($wh->id, $bodegasBuenaventuraIds) ? 'true' : 'false' }}"
                            {{ (old('warehouse_id', $salida->warehouse_id) == $wh->id) ? 'selected' : '' }}>
                            {{ $wh->nombre }}{{ $wh->ciudad ? ' - ' . $wh->ciudad : '' }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- Fecha --}}
                <label for="fecha">Fecha*</label>
                <input type="date" name="fecha" id="fecha"
                    value="{{ old('fecha', $salida->fecha->format('Y-m-d')) }}" required>
                @error('fecha') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- A nombre de --}}
                <label for="a_nombre_de">A nombre de*</label>
                <input type="text" name="a_nombre_de" id="a_nombre_de"
                    value="{{ old('a_nombre_de', $salida->a_nombre_de) }}"
                    placeholder="Nombre del cliente" required>
                @error('a_nombre_de') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- NIT / Cédula --}}
                <label for="nit_cedula">NIT/Cédula*</label>
                <input type="text" name="nit_cedula" id="nit_cedula"
                    value="{{ old('nit_cedula', $salida->nit_cedula) }}"
                    placeholder="NIT o Cédula" required>
                @error('nit_cedula') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- Aprobó --}}
                <label for="aprobo">Aprobó</label>
                <input type="text" name="aprobo" id="aprobo"
                    value="{{ old('aprobo', $salida->aprobo) }}"
                    placeholder="Nombre de quien aprueba (opcional)">
                @error('aprobo') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- Ciudad destino --}}
                <label for="ciudad_destino">Ciudad Destino</label>
                <input type="text" name="ciudad_destino" id="ciudad_destino"
                    value="{{ old('ciudad_destino', $salida->ciudad_destino) }}"
                    placeholder="Ciudad destino (opcional)">
                @error('ciudad_destino') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- Conductor externo --}}
                @php
                    $useExternalOld = old('use_external_driver', $salida->isExternalDriver() ? '1' : '');
                    $isExternal = $useExternalOld == '1';
                @endphp
                <div style="margin-bottom: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: normal;">
                        <input type="checkbox" name="use_external_driver" id="use_external_driver" value="1"
                            {{ $isExternal ? 'checked' : '' }} style="width: auto; margin: 0;">
                        <span>Conductor externo (no registrado)</span>
                    </label>
                </div>

                <div id="driver-registered-block" style="{{ $isExternal ? 'display:none;' : '' }}">
                    <label for="driver_id">Conductor</label>
                    <select name="driver_id" id="driver_id" class="form-control">
                        <option value="">Seleccione un conductor (opcional)</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}"
                                {{ old('driver_id', $salida->driver_id) == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }} - {{ $driver->identity }}
                            </option>
                        @endforeach
                    </select>
                    @error('driver_id') <div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div id="driver-external-block" style="{{ $isExternal ? '' : 'display: none;' }}">
                    <label for="external_driver_name">Nombre del conductor*</label>
                    <input type="text" name="external_driver_name" id="external_driver_name"
                        value="{{ old('external_driver_name', $salida->external_driver_name) }}"
                        maxlength="255" placeholder="Nombre completo">
                    @error('external_driver_name') <div class="invalid-feedback">{{ $message }}</div>@enderror

                    <label for="external_driver_identity">Cédula / NIT*</label>
                    <input type="text" name="external_driver_identity" id="external_driver_identity"
                        value="{{ old('external_driver_identity', $salida->external_driver_identity) }}"
                        maxlength="50" placeholder="Documento de identidad">
                    @error('external_driver_identity') <div class="invalid-feedback">{{ $message }}</div>@enderror

                    <label for="external_driver_plate">Placa del vehículo*</label>
                    <input type="text" name="external_driver_plate" id="external_driver_plate"
                        value="{{ old('external_driver_plate', $salida->external_driver_plate) }}"
                        maxlength="50" placeholder="Placa">
                    @error('external_driver_plate') <div class="invalid-feedback">{{ $message }}</div>@enderror

                    <label for="external_driver_phone">Teléfono del conductor</label>
                    <input type="text" name="external_driver_phone" id="external_driver_phone"
                        value="{{ old('external_driver_phone', $salida->external_driver_phone) }}"
                        maxlength="20" placeholder="Número de teléfono">
                    @error('external_driver_phone') <div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Productos --}}
                <div style="margin-top: 20px; margin-bottom: 10px;">
                    <label style="margin-bottom: 10px;">Productos a dar salida*</label>
                    <button type="button" class="btn-add-product" onclick="addProduct()">+ Agregar Producto</button>
                </div>

                <div id="products-container">
                    {{-- Productos existentes se renderizan aquí --}}
                </div>

                @error('products') <div class="invalid-feedback">{{ $message }}</div>@enderror

                {{-- Notas --}}
                <label for="note">Notas</label>
                <textarea name="note" id="note" rows="2"
                    placeholder="Opcional">{{ old('note', $salida->note) }}</textarea>
                @error('note') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="actions">
                    <a href="{{ route('salidas.index') }}" class="btn-cancel">Cancelar</a>
                    <button type="submit" class="btn-save">💾 Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $productsJson = $products->map(function($p) {
            return [
                'id'               => $p->id,
                'nombre'           => $p->nombre,
                'codigo'           => $p->codigo,
                'medidas'          => $p->medidas,
                'tipo_medida'      => $p->tipo_medida,
                'unidades_por_caja'=> $p->unidades_por_caja,
                'stock'            => 0,
                'containers'       => $p->containers->map(function($c) {
                    return ['id' => $c->id, 'reference' => $c->reference];
                })->values()->toArray(),
            ];
        })->values()->toJson();

        $existingProductsJson = $salida->products->map(function($p) {
            return [
                'product_id'   => $p->id,
                'quantity_raw' => $p->pivot->quantity,
                'container_id' => $p->pivot->container_id,
            ];
        })->values()->toJson();
    @endphp

    <script>
        let productIndex = 0;
        let availableProducts = {!! $productsJson !!};

        // Productos actuales de la salida (para precargar)
        const existingProducts = {!! $existingProductsJson !!};

        let isBuenaventura = @json($isBuenaventura);
        let bodegasBuenaventuraIds = @json($bodegasBuenaventuraIds);

        // =====================================================================
        // Toggle conductor externo
        // =====================================================================
        function toggleDriverBlocks() {
            const useExternal = document.getElementById('use_external_driver').checked;
            const blockReg = document.getElementById('driver-registered-block');
            const blockExt = document.getElementById('driver-external-block');
            const driverSelect = document.getElementById('driver_id');
            const extName  = document.getElementById('external_driver_name');
            const extId    = document.getElementById('external_driver_identity');
            const extPlate = document.getElementById('external_driver_plate');
            const extPhone = document.getElementById('external_driver_phone');
            if (useExternal) {
                if (blockReg) blockReg.style.display = 'none';
                if (blockExt) blockExt.style.display = 'block';
                if (driverSelect) driverSelect.value = '';
                if (extName) extName.setAttribute('required', 'required');
                if (extId)   extId.setAttribute('required', 'required');
                if (extPlate) extPlate.setAttribute('required', 'required');
            } else {
                if (blockReg) blockReg.style.display = 'block';
                if (blockExt) blockExt.style.display = 'none';
                if (extName) { extName.removeAttribute('required'); }
                if (extId)   { extId.removeAttribute('required'); }
                if (extPlate){ extPlate.removeAttribute('required'); }
            }
        }
        document.getElementById('use_external_driver').addEventListener('change', toggleDriverBlocks);

        // =====================================================================
        // Actualizar labels de cantidad según bodega Buenaventura
        // =====================================================================
        function updateWarehouseType() {
            const warehouseSelect = document.getElementById('warehouse_id');
            if (!warehouseSelect || warehouseSelect.tagName !== 'SELECT') return;
            const selectedOption = warehouseSelect.options[warehouseSelect.selectedIndex];
            isBuenaventura = selectedOption ? selectedOption.getAttribute('data-is-buenaventura') === 'true' : false;

            document.querySelectorAll('[id^="quantity-label-"]').forEach(label => {
                label.textContent = isBuenaventura ? 'Cantidad (en cajas)*' : 'Cantidad (en láminas)*';
            });
            document.querySelectorAll('[id^="quantity-"]').forEach(input => {
                if (input.id.startsWith('quantity-')) {
                    input.setAttribute('data-is-buenaventura', isBuenaventura);
                    input.placeholder = isBuenaventura ? 'Cantidad en cajas' : 'Cantidad en láminas';
                    const idx = parseInt(input.id.replace('quantity-', ''));
                    updateProductInfo(idx);
                }
            });
        }

        // =====================================================================
        // Cargar productos disponibles para la bodega seleccionada
        // =====================================================================
        window.loadProductsForWarehouse = async function (preloadCallback) {
            const warehouseElement = document.getElementById('warehouse_id');
            let warehouseId = warehouseElement ? warehouseElement.value : null;

            if (!warehouseId || warehouseId === '') {
                availableProducts = [];
                updateProductSelects();
                return;
            }
            warehouseId = parseInt(warehouseId);

            try {
                const url = `{{ route('salidas.get-products', ':id') }}`.replace(':id', warehouseId);
                const response = await fetch(url);
                if (!response.ok) {
                    if (response.status === 403) alert('No tienes permiso para ver productos de esta bodega.');
                    else alert('Error al cargar productos.');
                    availableProducts = [];
                    updateProductSelects();
                    return;
                }
                availableProducts = await response.json();
                updateProductSelects();
                if (typeof preloadCallback === 'function') preloadCallback();
            } catch (error) {
                console.error('Error al cargar productos:', error);
                availableProducts = [];
                updateProductSelects();
            }
        };

        // =====================================================================
        // Texto de opción de producto
        // =====================================================================
        function buildProductOptionText(product) {
            const medidasText = product.medidas ? ` - ${product.medidas}` : '';
            let text = `${product.nombre} (${product.codigo})${medidasText} - Stock: ${product.stock}`;
            if (product.containers && product.containers.length === 1) {
                text += ` [${product.containers[0].reference}]`;
            } else if (product.containers && product.containers.length > 1) {
                text += ` [${product.containers.map(c => c.reference).join(', ')}]`;
            }
            return text;
        }

        // =====================================================================
        // Actualizar todos los selects de producto
        // =====================================================================
        function updateProductSelects() {
            document.querySelectorAll('select[id^="product-select-"]').forEach(select => {
                const currentValue = select.value;
                const index = select.id.replace('product-select-', '');
                select.innerHTML = '<option value="">Seleccione un producto</option>';
                availableProducts.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = buildProductOptionText(product);
                    option.setAttribute('data-tipo', product.tipo_medida || '');
                    option.setAttribute('data-stock', product.stock);
                    option.setAttribute('data-unidades', product.unidades_por_caja || 1);
                    option.setAttribute('data-medidas', product.medidas || '');
                    option.setAttribute('data-containers', JSON.stringify(product.containers || []));
                    if (currentValue && product.id == currentValue) option.selected = true;
                    select.appendChild(option);
                });
                if (select.value) {
                    updateContainerForProduct(parseInt(index));
                } else {
                    const cb = document.getElementById(`container-block-${index}`);
                    if (cb) cb.style.display = 'none';
                    updateProductInfo(parseInt(index));
                }
            });
        }

        // =====================================================================
        // Agregar fila de producto (nuevo)
        // =====================================================================
        function addProduct(preselectedProductId, preselectedContainerId, preselectedQuantityRaw) {
            const container = document.getElementById('products-container');
            const productItem = document.createElement('div');
            productItem.className = 'product-item';
            productItem.id = `product-${productIndex}`;

            const warehouseElement = document.getElementById('warehouse_id');
            let currentIsBuenaventura = isBuenaventura;
            if (warehouseElement && warehouseElement.tagName === 'SELECT' && warehouseElement.value) {
                const sel = warehouseElement.options[warehouseElement.selectedIndex];
                currentIsBuenaventura = sel ? sel.getAttribute('data-is-buenaventura') === 'true' : false;
            }

            const cantidadLabel       = currentIsBuenaventura ? 'Cantidad (en cajas)*' : 'Cantidad (en láminas)*';
            const cantidadPlaceholder = currentIsBuenaventura ? 'Cantidad en cajas' : 'Cantidad en láminas';

            // Si es Buenaventura, convertir láminas → cajas para mostrar en el campo
            let displayQuantity = preselectedQuantityRaw || 1;

            productItem.innerHTML = `
                <div class="product-item-header">
                    <span class="product-item-title">Producto #${productIndex + 1}</span>
                    <button type="button" class="btn-remove-product" onclick="removeProduct(${productIndex})">Eliminar</button>
                </div>
                <div class="product-fields">
                    <div class="full-width">
                        <label for="products[${productIndex}][product_id]">Producto*</label>
                        <select name="products[${productIndex}][product_id]" id="product-select-${productIndex}" required
                            onchange="updateContainerForProduct(${productIndex}); updateProductInfo(${productIndex})">
                            <option value="">Seleccione un producto</option>
                        </select>
                        <div class="stock-info" id="stock-info-${productIndex}"></div>
                    </div>
                    <div class="full-width" id="container-block-${productIndex}" style="display:none;">
                        <label for="products[${productIndex}][container_id]">Contenedor*</label>
                        <select name="products[${productIndex}][container_id]" id="container-select-${productIndex}"
                            onchange="updateProductInfo(${productIndex})">
                            <option value="">Seleccione un contenedor</option>
                        </select>
                    </div>
                    <div>
                        <label for="products[${productIndex}][quantity]" id="quantity-label-${productIndex}">${cantidadLabel}</label>
                        <input type="number" name="products[${productIndex}][quantity]" id="quantity-${productIndex}"
                            min="1" value="${displayQuantity}" required
                            oninput="updateProductInfo(${productIndex})"
                            placeholder="${cantidadPlaceholder}"
                            data-is-buenaventura="${currentIsBuenaventura}"
                            data-preselected-container="${preselectedContainerId || ''}">
                    </div>
                </div>
            `;

            container.appendChild(productItem);

            // Poblar el select con los productos disponibles
            const select = document.getElementById(`product-select-${productIndex}`);
            if (select) {
                availableProducts.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = buildProductOptionText(product);
                    option.setAttribute('data-tipo', product.tipo_medida || '');
                    option.setAttribute('data-stock', product.stock);
                    option.setAttribute('data-unidades', product.unidades_por_caja || 1);
                    option.setAttribute('data-medidas', product.medidas || '');
                    option.setAttribute('data-containers', JSON.stringify(product.containers || []));
                    if (preselectedProductId && product.id == preselectedProductId) option.selected = true;
                    select.appendChild(option);
                });

                if (preselectedProductId) {
                    const currentIdx = productIndex;
                    updateContainerForProduct(currentIdx, preselectedContainerId);
                    // Recalcular cantidad display (láminas→cajas si Buenaventura)
                    if (currentIsBuenaventura && preselectedQuantityRaw) {
                        const selOpt = select.selectedOptions[0];
                        const unidades = selOpt ? parseInt(selOpt.getAttribute('data-unidades')) || 1 : 1;
                        if (unidades > 1) {
                            const qInputEl = document.getElementById(`quantity-${currentIdx}`);
                            if (qInputEl) qInputEl.value = Math.round(preselectedQuantityRaw / unidades);
                        }
                    }
                    updateProductInfo(currentIdx);
                }
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
                if (title) title.textContent = `Producto #${index + 1}`;
            });
        }

        // =====================================================================
        // Contenedor según producto seleccionado
        // =====================================================================
        function updateContainerForProduct(index, preselectedContainerId) {
            const productSelect   = document.getElementById(`product-select-${index}`);
            const containerBlock  = document.getElementById(`container-block-${index}`);
            const containerSelect = document.getElementById(`container-select-${index}`);

            if (!productSelect || !containerBlock || !containerSelect) return;

            const selectedOption = productSelect.selectedOptions[0];
            if (!selectedOption || !selectedOption.value) {
                containerBlock.style.display = 'none';
                containerSelect.innerHTML = '<option value="">Seleccione un contenedor</option>';
                containerSelect.removeAttribute('required');
                return;
            }

            let containers = [];
            try { containers = JSON.parse(selectedOption.getAttribute('data-containers') || '[]'); } catch (e) {}

            containerSelect.innerHTML = '<option value="">Seleccione un contenedor</option>';

            // Obtener el container_id que se debe preseleccionar
            const quantityInput = document.getElementById(`quantity-${index}`);
            const preContId = preselectedContainerId ||
                (quantityInput ? parseInt(quantityInput.getAttribute('data-preselected-container')) || null : null);

            if (containers.length === 0) {
                containerBlock.style.display = 'none';
                containerSelect.removeAttribute('required');
            } else if (containers.length === 1) {
                const opt = document.createElement('option');
                opt.value = containers[0].id;
                opt.textContent = containers[0].reference;
                opt.selected = true;
                containerSelect.appendChild(opt);
                containerBlock.style.display = 'block';
                containerSelect.setAttribute('required', 'required');
                containerSelect.style.background = '#e9ecef';
                containerSelect.style.pointerEvents = 'none';
            } else {
                containers.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.reference;
                    if (preContId && c.id == preContId) opt.selected = true;
                    containerSelect.appendChild(opt);
                });
                containerBlock.style.display = 'block';
                containerSelect.setAttribute('required', 'required');
                containerSelect.style.background = '';
                containerSelect.style.pointerEvents = '';
            }

            updateProductInfo(index);
        }

        // =====================================================================
        // Info de stock del producto seleccionado
        // =====================================================================
        function updateProductInfo(index) {
            const productSelect    = document.getElementById(`product-select-${index}`);
            const quantityInput    = document.getElementById(`quantity-${index}`);
            const stockInfo        = document.getElementById(`stock-info-${index}`);
            const containerSelect  = document.getElementById(`container-select-${index}`);

            if (!productSelect || !quantityInput) return;

            const isBuenaventuraLocal = quantityInput.getAttribute('data-is-buenaventura') === 'true';
            const selectedOption = productSelect.selectedOptions[0];

            if (selectedOption && selectedOption.value) {
                let stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
                const selectedContainerId = containerSelect ? parseInt(containerSelect.value) : null;
                if (selectedContainerId) {
                    const productId   = parseInt(selectedOption.value);
                    const productData = availableProducts.find(p => p.id === productId);
                    if (productData && productData.containers && productData.containers.length > 1) {
                        const foundContainer = productData.containers.find(c => c.id === selectedContainerId);
                        if (foundContainer && foundContainer.stock !== undefined) stock = foundContainer.stock;
                    }
                }

                const tipo     = selectedOption.getAttribute('data-tipo');
                const unidades = parseInt(selectedOption.getAttribute('data-unidades')) || 1;
                const quantity = parseInt(quantityInput.value) || 0;

                if (isBuenaventuraLocal) {
                    const laminasReq   = quantity * unidades;
                    const cajasDisp    = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
                    if (stockInfo) {
                        stockInfo.innerHTML = laminasReq > stock
                            ? `<span style="color:#dc3545;font-weight:bold;">⚠️ Stock insuficiente. Disponible: ${cajasDisp} cajas (${stock} láminas)</span>`
                            : `<span style="color:#28a745;">✓ Stock disponible: ${cajasDisp} cajas (${stock} láminas)</span>`;
                    }
                } else {
                    const cajas = tipo === 'caja' && unidades > 0 ? Math.floor(stock / unidades) : 0;
                    if (stockInfo) {
                        stockInfo.innerHTML = quantity > stock
                            ? `<span style="color:#dc3545;font-weight:bold;">⚠️ Stock insuficiente. Disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`
                            : `<span style="color:#28a745;">✓ Stock disponible: ${stock} láminas${cajas > 0 ? ` (${cajas} cajas)` : ''}</span>`;
                    }
                }
            } else {
                if (stockInfo) stockInfo.innerHTML = '';
            }
        }

        // =====================================================================
        // Inicialización: cargar productos del warehouse y precargar los existentes
        // =====================================================================
        document.addEventListener('DOMContentLoaded', function () {
            toggleDriverBlocks();
            updateWarehouseType();

            loadProductsForWarehouse(function () {
                // Callback: una vez cargados los productos disponibles, renderizar los existentes
                existingProducts.forEach(function (ep) {
                    addProduct(ep.product_id, ep.container_id, ep.quantity_raw);
                });
            });
        });
    </script>
@endsection
