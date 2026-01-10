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
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        width: 100%;
    }
    .file-input-wrapper input[type="file"] {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    .file-input-custom {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.15s;
    }
    .file-input-custom:hover {
        border-color: #4a8af4;
        background: #fff;
    }
    .file-input-custom .file-button {
        background: #4a8af4;
        color: white;
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 13px;
        margin-right: 10px;
        white-space: nowrap;
    }
    .file-input-custom .file-name {
        color: #666;
        font-size: 14px;
        flex: 1;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
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
    .supplier-info {
        background: #e7f3ff;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 18px;
        border-left: 4px solid #4a8af4;
    }
    .supplier-info strong {
        color: #333;
    }
    .translate-btn {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        margin-left: 8px;
    }
    .translate-btn:hover {
        background: #218838;
    }
</style>
@if($errors->any())
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>Swal.fire({icon:'error',title:'Error',text:'{{ $errors->first() }}',toast:true,position:'top-end',showConfirmButton:false,timer:3500})</script>
@endif
<div class="form-bg">
    <div class="form-container">
        <h2>{{ __('common.nueva_importacion') }}</h2>
        <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf
            
            <div class="supplier-info">
                <strong>{{ __('common.proveedor') }}:</strong> {{ Auth::user()->name ?? Auth::user()->email }}
            </div>

            <div class="form-group">
                <label for="commercial_invoice_number">{{ __('common.comercial_invoice') }} *</label>
                <input type="text" name="commercial_invoice_number" id="commercial_invoice_number" class="@error('commercial_invoice_number') is-invalid @enderror" value="{{ old('commercial_invoice_number') }}" placeholder="{{ __('common.comercial_invoice') }}" required />
                @error('commercial_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_invoice_number">{{ __('common.numero_proforma_invoice') }}</label>
                <input type="text" name="proforma_invoice_number" id="proforma_invoice_number" class="@error('proforma_invoice_number') is-invalid @enderror" value="{{ old('proforma_invoice_number') }}" placeholder="{{ __('common.numero_proforma_invoice') }}" />
                @error('proforma_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="bl_number">{{ __('common.numero_bill_of_lading') }}</label>
                <input type="text" name="bl_number" id="bl_number" class="@error('bl_number') is-invalid @enderror" value="{{ old('bl_number') }}" placeholder="{{ __('common.numero_bill_of_lading') }}" />
                @error('bl_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="origin">{{ __('common.origen') }} *</label>
                <input type="text" name="origin" id="origin" class="@error('origin') is-invalid @enderror" value="{{ old('origin') }}" placeholder="{{ __('common.origen') }}" required />
                @error('origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="destination">{{ __('common.destino') }}</label>
                <input type="text" name="destination" id="destination" value="{{ __('common.colombia') }}" disabled style="background: #e9ecef;" />
            </div>

            <div class="form-group">
                <label for="departure_date">{{ __('common.fecha_salida') }} *</label>
                <input type="date" name="departure_date" id="departure_date" class="@error('departure_date') is-invalid @enderror" value="{{ old('departure_date') }}" required />
                @error('departure_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="arrival_date">{{ __('common.fecha_llegada') }} *</label>
                <input type="date" name="arrival_date" id="arrival_date" class="@error('arrival_date') is-invalid @enderror" value="{{ old('arrival_date') }}" required />
                @error('arrival_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>{{ __('common.contenedores') }} *</label>
                <div id="containers-container">
                    <div class="container-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong style="color: #333;">{{ __('common.contenedor_num') }}1</strong>
                            <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; display: none;">{{ __('common.eliminar') }}</button>
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.referencia') }} *</label>
                            <input type="text" name="containers[0][reference]" id="container_ref_0" class="form-control" style="padding: 8px 12px; font-size: 14px;" required />
                        </div>
                        <div class="form-group" style="margin-bottom: 12px;">
                            <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.pdf_informacion_contenedor') }}</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="containers[0][pdf]" id="container_pdf_0" class="file-input" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                                <div class="file-input-custom">
                                    <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                                    <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.pdf_imagenes_contenedor') }}</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="containers[0][image_pdf]" id="container_image_pdf_0" class="file-input" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                                <div class="file-input-custom">
                                    <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                                    <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" id="add-container-btn" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; margin-top: 10px;">
                    <i class="bi bi-plus-circle me-1"></i>{{ __('common.agregar_contenedor') }}
                </button>
                @error('containers') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_pdf">{{ __('common.proforma_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="proforma_pdf" id="proforma_pdf" class="file-input @error('proforma_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('proforma_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_invoice_low_pdf">{{ __('common.proforma_invoice_low_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="proforma_invoice_low_pdf" id="proforma_invoice_low_pdf" class="file-input @error('proforma_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('proforma_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="invoice_pdf">{{ __('common.comercial_invoice_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="invoice_pdf" id="invoice_pdf" class="file-input @error('invoice_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('invoice_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="commercial_invoice_low_pdf">{{ __('common.commercial_invoice_low_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="commercial_invoice_low_pdf" id="commercial_invoice_low_pdf" class="file-input @error('commercial_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('commercial_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="packing_list_pdf">{{ __('common.packing_list_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="packing_list_pdf" id="packing_list_pdf" class="file-input @error('packing_list_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('packing_list_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="bl_pdf">{{ __('common.bl_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="bl_pdf" id="bl_pdf" class="file-input @error('bl_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('bl_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="apostillamiento_pdf">{{ __('common.apostillamiento_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="apostillamiento_pdf" id="apostillamiento_pdf" class="file-input @error('apostillamiento_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('apostillamiento_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="other_documents_pdf">{{ __('common.otros_documentos_pdf') }}</label>
                <div class="file-input-wrapper">
                    <input type="file" name="other_documents_pdf" id="other_documents_pdf" class="file-input @error('other_documents_pdf') is-invalid @enderror" accept="application/pdf" />
                    <div class="file-input-custom">
                        <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                        <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                    </div>
                </div>
                @error('other_documents_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="shipping_company">{{ __('common.naviera_agente') }}</label>
                <input type="text" name="shipping_company" id="shipping_company" class="@error('shipping_company') is-invalid @enderror" value="{{ old('shipping_company') }}" />
                @error('shipping_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="free_days_at_dest">{{ __('common.dias_libres_destino') }}</label>
                <input type="number" name="free_days_at_dest" id="free_days_at_dest" class="@error('free_days_at_dest') is-invalid @enderror" min="0" value="{{ old('free_days_at_dest') }}" />
                @error('free_days_at_dest') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="credit_time">{{ __('common.tiempo_credito') }} *</label>
                <select name="credit_time" id="credit_time" class="@error('credit_time') is-invalid @enderror" required>
                    <option value="">{{ __('common.seleccione') }}</option>
                    <option value="15" {{ old('credit_time') == '15' ? 'selected' : '' }}>15 {{ __('common.dias') }}</option>
                    <option value="30" {{ old('credit_time') == '30' ? 'selected' : '' }}>30 {{ __('common.dias') }}</option>
                    <option value="45" {{ old('credit_time') == '45' ? 'selected' : '' }}>45 {{ __('common.dias') }}</option>
                </select>
                @error('credit_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="actions">
                <a href="{{ route('imports.provider-index') }}" class="btn-cancel">{{ __('common.cancelar') }}</a>
                <button type="submit" class="btn-save">{{ __('common.guardar') }}</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let containerCount = 1;
    const containersContainer = document.getElementById('containers-container');
    const addContainerBtn = document.getElementById('add-container-btn');
    
    addContainerBtn.addEventListener('click', function() {
        const containerHtml = `
            <div class="container-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <strong style="color: #333;">{{ __('common.contenedor_num') }}${containerCount + 1}</strong>
                    <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">{{ __('common.eliminar') }}</button>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.referencia') }} *</label>
                    <input type="text" name="containers[${containerCount}][reference]" id="container_ref_${containerCount}" class="form-control" style="padding: 8px 12px; font-size: 14px;" required />
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.pdf_informacion_contenedor') }}</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="containers[${containerCount}][pdf]" id="container_pdf_${containerCount}" class="file-input" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                        <div class="file-input-custom">
                            <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                            <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px; margin-bottom: 4px;">{{ __('common.pdf_imagenes_contenedor') }}</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="containers[${containerCount}][image_pdf]" id="container_image_pdf_${containerCount}" class="file-input" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                        <div class="file-input-custom">
                            <span class="file-button">{{ __('common.seleccionar_archivo') }}</span>
                            <span class="file-name">{{ __('common.ningun_archivo_seleccionado') }}</span>
                        </div>
                    </div>
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
                    const remainingItems = containersContainer.querySelectorAll('.container-item');
                    remainingItems.forEach((itm, idx) => {
                        itm.querySelector('strong').textContent = `{{ __('common.contenedor_num') }}${idx + 1}`;
                    });
                });
            } else {
                removeBtn.style.display = 'none';
            }
        });
    }
    
    updateRemoveButtons();
    
    // Manejar la visualización de nombres de archivos para inputs de tipo file
    function setupFileInputs() {
        document.querySelectorAll('.file-input').forEach(function(input) {
            const wrapper = input.closest('.file-input-wrapper');
            if (!wrapper) return;
            const fileNameSpan = wrapper.querySelector('.file-name');
            if (!fileNameSpan) return;
            
            // Limpiar listeners anteriores
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            
            newInput.addEventListener('change', function(e) {
                if (this.files && this.files.length > 0) {
                    fileNameSpan.textContent = this.files[0].name;
                } else {
                    fileNameSpan.textContent = '{{ __('common.ningun_archivo_seleccionado') }}';
                }
            });
        });
    }
    
    setupFileInputs();
    
    // Actualizar file inputs cuando se agregan nuevos contenedores
    const originalClick = addContainerBtn.onclick;
    addContainerBtn.addEventListener('click', function() {
        setTimeout(function() {
            setupFileInputs();
        }, 100);
    });
});
</script>
@endsection
