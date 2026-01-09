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
        width: 600px;
        max-width: 95%;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        margin: auto;
    }
    .form-container h2 {
        margin-top: 0;
        font-size: 20px;
        color: #222;
        margin-bottom: 20px;
        font-weight: 700;
    }
    .form-group {
        margin-bottom: 18px;
    }
    .form-container label {
        display: block;
        margin-bottom: 6px;
        font-weight: bold;
        color: #333;
        text-align: left;
        font-size: 14px;
    }
    .form-container input,
    .form-container select {
        display: block;
        width: 100%;
        padding: 10px 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        background: #f8fafc;
        transition: box-shadow .15s, border-color .15s;
        box-sizing: border-box;
    }
    .form-container input:focus,
    .form-container select:focus {
        border-color: #4a8af4;
        outline: none;
        box-shadow: 0 0 4px rgba(74,138,244,0.14);
        background: #fff;
    }
    .form-container input[type="file"] {
        padding: 8px;
        background: white;
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
        padding: 10px 24px;
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
        margin-top: 4px;
        text-align: left;
    }
</style>
@if($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>Swal.fire({icon:'error',title:'Error',text:'{{ $errors->first() }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500})</script>
@endif
<div class="form-bg">
    <div class="form-container">
        <h2>Nueva Importación</h2>
        <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf
            <div class="form-group">
                <label for="product_name">Nombre del producto *</label>
                <input type="text" name="product_name" id="product_name" class="@error('product_name') is-invalid @enderror" value="{{ old('product_name') }}" required />
                @error('product_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="origin">Origen *</label>
                <input type="text" name="origin" id="origin" class="@error('origin') is-invalid @enderror" value="{{ old('origin') }}" placeholder="Ej: China, Estados Unidos..." required />
                @error('origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="departure_date">Fecha de salida *</label>
                <input type="date" name="departure_date" id="departure_date" class="@error('departure_date') is-invalid @enderror" value="{{ old('departure_date') }}" required />
                @error('departure_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="arrival_date">Fecha estimada de llegada *</label>
                <input type="date" name="arrival_date" id="arrival_date" class="@error('arrival_date') is-invalid @enderror" value="{{ old('arrival_date') }}" required />
                @error('arrival_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Contenedores *</label>
                <div id="containers-container">
                    <div class="container-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: #333;">Contenedor #1</strong>
                            <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; display: none;">Eliminar</button>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size: 13px; margin-bottom: 4px;">Referencia *</label>
                            <input type="text" name="containers[0][reference]" class="form-control" style="padding: 8px 12px; font-size: 14px;" required />
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size: 13px; margin-bottom: 4px;">PDF</label>
                            <input type="file" name="containers[0][pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 13px; margin-bottom: 4px;">Imágenes</label>
                            <input type="file" name="containers[0][images][]" class="form-control" style="padding: 6px; font-size: 13px;" accept="image/png,image/jpeg" multiple />
                        </div>
                    </div>
                </div>
                <button type="button" id="add-container-btn" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                    <i class="bi bi-plus-circle me-1"></i>Agregar Contenedor
                </button>
                @error('containers') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_pdf">Proforma Invoice (PDF)</label>
                <input type="file" name="proforma_pdf" id="proforma_pdf" class="@error('proforma_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('proforma_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_invoice_low_pdf">Proforma Invoice Low (PDF)</label>
                <input type="file" name="proforma_invoice_low_pdf" id="proforma_invoice_low_pdf" class="@error('proforma_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('proforma_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="invoice_pdf">Commercial Invoice (PDF)</label>
                <input type="file" name="invoice_pdf" id="invoice_pdf" class="@error('invoice_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('invoice_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="commercial_invoice_low_pdf">Commercial Invoice Low (PDF)</label>
                <input type="file" name="commercial_invoice_low_pdf" id="commercial_invoice_low_pdf" class="@error('commercial_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('commercial_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="packing_list_pdf">Packing List (PDF)</label>
                <input type="file" name="packing_list_pdf" id="packing_list_pdf" class="@error('packing_list_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('packing_list_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="bl_pdf">Bill of Lading (PDF)</label>
                <input type="file" name="bl_pdf" id="bl_pdf" class="@error('bl_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('bl_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="apostillamiento_pdf">Apostillamiento (PDF)</label>
                <input type="file" name="apostillamiento_pdf" id="apostillamiento_pdf" class="@error('apostillamiento_pdf') is-invalid @enderror" accept="application/pdf" />
                @error('apostillamiento_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="etd">ETD</label>
                <input type="text" name="etd" id="etd" class="@error('etd') is-invalid @enderror" value="{{ old('etd') }}" />
                @error('etd') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="shipping_company">Naviera o Agente de Carga</label>
                <input type="text" name="shipping_company" id="shipping_company" class="@error('shipping_company') is-invalid @enderror" value="{{ old('shipping_company') }}" />
                @error('shipping_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="free_days_at_dest">Días libres destino</label>
                <input type="number" name="free_days_at_dest" id="free_days_at_dest" class="@error('free_days_at_dest') is-invalid @enderror" min="0" value="{{ old('free_days_at_dest') }}" />
                @error('free_days_at_dest') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="supplier">Proveedor</label>
                <input type="text" name="supplier" id="supplier" class="@error('supplier') is-invalid @enderror" value="{{ old('supplier') }}" />
                @error('supplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="credit_time">Tiempo de crédito *</label>
                <select name="credit_time" id="credit_time" class="@error('credit_time') is-invalid @enderror" required>
                    <option value="">Seleccione...</option>
                    <option value="15" {{ old('credit_time') == '15' ? 'selected' : '' }}>15 días</option>
                    <option value="30" {{ old('credit_time') == '30' ? 'selected' : '' }}>30 días</option>
                    <option value="45" {{ old('credit_time') == '45' ? 'selected' : '' }}>45 días</option>
                </select>
                @error('credit_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="actions">
                <a href="{{ route('imports.provider-index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Guardar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let containerCount = 1;
    const containersContainer = document.getElementById('containers-container');
    const addContainerBtn = document.getElementById('add-container-btn');
    
    addContainerBtn.addEventListener('click', function() {
        const containerHtml = `
            <div class="container-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong style="color: #333;">Contenedor #${containerCount + 1}</strong>
                    <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">Eliminar</button>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px; margin-bottom: 4px;">Referencia *</label>
                    <input type="text" name="containers[${containerCount}][reference]" class="form-control" style="padding: 8px 12px; font-size: 14px;" required />
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px; margin-bottom: 4px;">PDF</label>
                    <input type="file" name="containers[${containerCount}][pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px; margin-bottom: 4px;">Imágenes</label>
                    <input type="file" name="containers[${containerCount}][images][]" class="form-control" style="padding: 6px; font-size: 13px;" accept="image/png,image/jpeg" multiple />
                </div>
            </div>
        `;
        containersContainer.insertAdjacentHTML('beforeend', containerHtml);
        containerCount++;
        updateRemoveButtons();
    });
    
    function updateRemoveButtons() {
        const containerItems = containersContainer.querySelectorAll('.container-item');
        containerItems.forEach((item, index) => {
            const removeBtn = item.querySelector('.btn-remove-container');
            if (containerItems.length > 1) {
                removeBtn.style.display = 'block';
                removeBtn.addEventListener('click', function() {
                    item.remove();
                    updateRemoveButtons();
                    // Renumerar contenedores
                    containerItems.forEach((itm, idx) => {
                        itm.querySelector('strong').textContent = `Contenedor #${idx + 1}`;
                    });
                });
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }
    
    updateRemoveButtons();
});
</script>
@endsection
