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
            <h2>Editar contenedor</h2>
            <form method="POST" action="{{ route('containers.update', $container) }}" autocomplete="off" id="containerForm">
                @csrf
                @method('PUT')
                <label for="reference">Contenedor*</label>
                <input name="reference" type="text" required value="{{ old('reference', $container->reference) }}"
                    placeholder="Ej: CONT-001">
                @error('reference') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <label for="warehouse_id">Bodega*</label>
                <select name="warehouse_id" id="warehouse_id" required>
                    <option value="">Seleccione una bodega</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $container->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->nombre }}{{ $warehouse->ciudad ? ' - ' . $warehouse->ciudad : '' }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div>@enderror

                <label for="note">Observación</label>
                <textarea name="note" rows="2"
                    placeholder="Notas adicionales sobre el contenedor">{{ old('note', $container->note) }}</textarea>
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
        const existingProducts = @json($container->products ?? []);
        // Mapa productId => {tipo, numero} para productos que salieron en transferencias/salidas
        const productOrigin = @json($productOrigin ?? []);

        function addProduct(existingProduct = null) {
            const container = document.getElementById('products-container');
            const productItem = document.createElement('div');
            productItem.className = 'product-item';
            productItem.id = `product-${productIndex}`;

            const selectedProductId = existingProduct ? existingProduct.id : '';
            const selectedBoxes = existingProduct ? existingProduct.pivot.boxes : 1;
            const selectedSheets = existingProduct ? existingProduct.pivot.sheets_per_box : 40;
            const selectedWeight = existingProduct ? existingProduct.pivot.weight_per_box : 0;

            // Texto informativo si el producto salió en una transferencia o salida
            let originNotice = '';
            if (existingProduct && selectedBoxes == 0 && productOrigin[existingProduct.id]) {
                const o = productOrigin[existingProduct.id];
                originNotice = `<div style="margin-bottom:10px; padding:7px 11px; background:#fff8e1; border:1px solid #ffe082; border-radius:6px; font-size:12.5px; color:#795548;">
                    ⚠️ Este producto salió en ${o.tipo === 'transferencia' ? 'la transferencia' : 'la salida'} <strong>${o.numero}</strong>.
                </div>`;
            } else if (existingProduct && selectedBoxes == 0) {
                originNotice = `<div style="margin-bottom:10px; padding:7px 11px; background:#fff8e1; border:1px solid #ffe082; border-radius:6px; font-size:12.5px; color:#795548;">
                    ⚠️ Este producto salió en una transferencia o salida registrada.
                </div>`;
            }

            productItem.innerHTML = `
                        <div class="product-item-header">
                            <span class="product-item-title">Producto #${productIndex + 1}</span>
                            <button type="button" class="btn-remove-product" onclick="removeProduct(${productIndex})">Eliminar</button>
                        </div>
                        ${originNotice}
                        <div class="product-fields">
                            <div>
                                <label for="products[${productIndex}][product_id]">Producto*</label>
                                <select name="products[${productIndex}][product_id]" required onchange="updateProductFields(${productIndex})">
                                    <option value="">Seleccione un producto</option>
                                    ${products.map(p => `<option value="${p.id}" data-tipo="${p.tipo_medida || ''}" data-medidas="${(p.medidas || '')}" data-weight="${p.weight_per_box != null ? p.weight_per_box : ''}" data-sheets="${p.unidades_por_caja != null ? p.unidades_por_caja : ''}" data-calibre="${p.calibre != null ? p.calibre : ''}" data-alto="${p.alto != null ? p.alto : ''}" data-ancho="${p.ancho != null ? p.ancho : ''}" data-peso-empaque="${p.peso_empaque != null ? p.peso_empaque : ''}" ${p.id == selectedProductId ? 'selected' : ''}>${p.nombre} (${p.codigo})</option>`).join('')}
                                </select>
                            </div>
                            <div>
                                <label for="products[${productIndex}][medidas]">Medidas</label>
                                <input type="text" name="products[${productIndex}][medidas]" id="medidas-${productIndex}" value="${existingProduct && existingProduct.medidas ? existingProduct.medidas : ''}" readonly placeholder="Seleccione un producto primero" style="background-color: #f0f0f0; cursor: not-allowed;">
                            </div>
                            <div>
                                <label for="products[${productIndex}][boxes]">Cajas*</label>
                                <input type="number" name="products[${productIndex}][boxes]" min="${existingProduct && existingProduct.pivot.boxes == 0 ? 0 : 1}" value="${selectedBoxes}" required data-depleted="${existingProduct && existingProduct.pivot.boxes == 0 ? 'true' : 'false'}" oninput="calculateSheets(${productIndex}); calculateWeight(${productIndex});">
                            </div>
                            <div>
                                <label for="products[${productIndex}][sheets_per_box]">Láminas por caja*</label>
                                <input type="number" name="products[${productIndex}][sheets_per_box]" min="1" value="${selectedSheets}" required oninput="calculateSheets(${productIndex}); updateWeightFromSheets(${productIndex}); calculateWeight(${productIndex});">
                            </div>
                            <div>
                                <label for="products[${productIndex}][weight_per_box]">Peso por caja (kg)*</label>
                                <input type="number" step="0.01" name="products[${productIndex}][weight_per_box]" id="weight-${productIndex}" min="0" value="${selectedWeight}" required oninput="calculateWeight(${productIndex})">
                                <small class="text-muted" style="font-size:11px;">Se rellena automáticamente si el producto tiene calibre, alto, ancho y peso empaque.</small>
                            </div>
                            <div>
                                <label>Total láminas</label>
                                <div style="padding: 10px 16px; background: #e3f2fd; border-radius: 6px; color: #1565c0; font-weight: bold;" id="total-sheets-${productIndex}">${selectedBoxes * selectedSheets}</div>
                            </div>
                            <div>
                                <label>Total peso (kg)</label>
                                <div style="padding: 10px 16px; background: #f1f8e9; border-radius: 6px; color: #2e7d32; font-weight: bold;" id="total-weight-${productIndex}">${(selectedBoxes * selectedWeight).toFixed(2)}</div>
                            </div>
                        </div>
                    `;

            container.appendChild(productItem);
            productIndex++;
            calculateSheets(productIndex - 1);
            calculateWeight(productIndex - 1);
            updateProductFields(productIndex - 1);
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
                const opt = select.selectedOptions[0];
                const tipo = opt.dataset.tipo || '';
                const medidas = opt.dataset.medidas || '';
                const weight = opt.dataset.weight || '';
                const sheets = opt.dataset.sheets || '';
                const medidasInput = document.querySelector(`#medidas-${index}`);
                const sheetsInput = document.querySelector(`#product-${index} input[name*="[sheets_per_box]"]`);
                const weightInput = document.querySelector(`#product-${index} input[name*="[weight_per_box]"]`);

                if (medidasInput) medidasInput.value = medidas;
                if (weightInput && weight !== '') weightInput.value = weight;
                if (tipo === 'unidad' && sheetsInput) {
                    sheetsInput.value = 1;
                    sheetsInput.readOnly = true;
                } else if (sheetsInput) {
                    sheetsInput.readOnly = false;
                    if (sheets !== '') sheetsInput.value = sheets;
                    else if (sheetsInput.value == 1) sheetsInput.value = 40;
                }
                calculateSheets(index);
                calculateWeight(index);
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

        function updateWeightFromSheets(index) {
            const productItem = document.getElementById(`product-${index}`);
            if (!productItem) return;
            const select = productItem.querySelector('select[name*="[product_id]"]');
            const sheetsInput = productItem.querySelector('input[name*="[sheets_per_box]"]');
            const weightInput = productItem.querySelector('input[name*="[weight_per_box]"]');
            if (!select || !select.selectedOptions[0] || !sheetsInput || !weightInput) return;
            const opt = select.selectedOptions[0];
            const calibre = parseFloat(opt.dataset.calibre) || 0;
            const alto = parseFloat(opt.dataset.alto) || 0;
            const ancho = parseFloat(opt.dataset.ancho) || 0;
            const pesoEmpaque = parseFloat(opt.dataset.pesoEmpaque) || 0;
            const sheets = parseInt(sheetsInput.value) || 0;
            if (calibre > 0 && alto > 0 && ancho > 0 && pesoEmpaque > 0 && sheets > 0) {
                const altoMetros = alto / 100;
                const anchoMetros = ancho / 100;
                const w = calibre * altoMetros * anchoMetros * pesoEmpaque * sheets;
                weightInput.value = Math.round(w * 100) / 100;
            }
        }

        function calculateWeight(index) {
            const productItem = document.getElementById(`product-${index}`);
            if (!productItem) return;

            const boxesInput = productItem.querySelector('input[name*="[boxes]"]');
            const weightInput = productItem.querySelector('input[name*="[weight_per_box]"]');
            const totalDiv = document.getElementById(`total-weight-${index}`);

            if (boxesInput && weightInput && totalDiv) {
                const boxes = parseInt(boxesInput.value) || 0;
                const weight = parseFloat(weightInput.value) || 0;
                totalDiv.textContent = (boxes * weight).toFixed(2);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Cargar solo productos activos (boxes > 0)
            if (existingProducts.length > 0) {
                existingProducts.forEach(product => {
                    addProduct(product);
                });
            } else {
                // Si todos los productos están agotados o no hay ninguno, agregar fila vacía
                addProduct();
            }

            // Validar que haya al menos un producto antes de enviar
            document.getElementById('containerForm').addEventListener('submit', function (e) {
                const productItems = document.querySelectorAll('.product-item');
                if (productItems.length === 0) {
                    e.preventDefault();
                    alert('Debes agregar al menos un producto al contenedor.');
                    return false;
                }

                // Validar que ningún producto nuevo tenga 0 cajas
                // (se permite 0 en productos que ya salieron en transferencias/salidas)
                let hasInvalidZeroBoxes = false;
                productItems.forEach((item) => {
                    const boxesInput = item.querySelector('input[name*="[boxes]"]');
                    const isDepleted = boxesInput && boxesInput.dataset.depleted === 'true';
                    if (boxesInput && (parseInt(boxesInput.value) || 0) === 0 && !isDepleted) {
                        hasInvalidZeroBoxes = true;
                    }
                });

                if (hasInvalidZeroBoxes) {
                    e.preventDefault();
                    alert('No puedes guardar productos nuevos con 0 cajas. Por favor, ingresa al menos 1 caja o elimina el producto.');
                    return false;
                }
            });
        });
    </script>
@endsection