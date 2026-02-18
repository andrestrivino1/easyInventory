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
    .file-link {
        display: inline-block;
        margin-top: 6px;
        padding: 6px 12px;
        background: #e3f2fd;
        color: #1565c0;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    .file-link:hover {
        background: #bbdefb;
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
        <h2>Editar Importación</h2>
        <form action="{{ route('imports.update', $import->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="supplier-info" style="background: #e7f3ff; padding: 12px; border-radius: 6px; margin-bottom: 18px; border-left: 4px solid #4a8af4;">
                <strong>Proveedor:</strong> {{ $import->user->name ?? $import->user->email }}
            </div>

            <div style="text-align: right; margin-bottom: 20px;">
                <button type="button" class="translate-btn" onclick="translateAllToChinese()" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;">
                    <i class="bi bi-translate"></i> Traducir Todo a Chino
                </button>
            </div>

            <div class="form-group">
                <label for="commercial_invoice_number">COMERCIAL INVOICE *</label>
                <input type="text" name="commercial_invoice_number" id="commercial_invoice_number" class="@error('commercial_invoice_number') is-invalid @enderror translate-field" value="{{ old('commercial_invoice_number', $import->commercial_invoice_number ?? $import->product_name) }}" placeholder="Número de la comercial invoice" required />
                @error('commercial_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_invoice_number">Número de Proforma Invoice</label>
                <input type="text" name="proforma_invoice_number" id="proforma_invoice_number" class="@error('proforma_invoice_number') is-invalid @enderror translate-field" value="{{ old('proforma_invoice_number', $import->proforma_invoice_number) }}" placeholder="Número de proforma invoice" />
                @error('proforma_invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="bl_number">Número de Bill of Lading</label>
                <input type="text" name="bl_number" id="bl_number" class="@error('bl_number') is-invalid @enderror translate-field" value="{{ old('bl_number', $import->bl_number) }}" placeholder="Número de Bill of Lading" />
                @error('bl_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="origin">Origen *</label>
                <input type="text" name="origin" id="origin" class="@error('origin') is-invalid @enderror translate-field" value="{{ old('origin', $import->origin) }}" placeholder="Ej: China, Estados Unidos..." required />
                @error('origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Destino</label>
                <input type="text" value="Colombia" class="form-control" disabled style="background: #e9ecef; cursor: not-allowed;" />
                <small style="color: #666; font-size: 12px;">El destino siempre es Colombia</small>
            </div>

            <div class="form-group">
                <label for="departure_date">Fecha de salida *</label>
                <input type="date" name="departure_date" id="departure_date" class="@error('departure_date') is-invalid @enderror" value="{{ old('departure_date', $import->departure_date) }}" required />
                @error('departure_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="arrival_date">Fecha estimada de llegada *</label>
                <input type="date" name="arrival_date" id="arrival_date" class="@error('arrival_date') is-invalid @enderror" value="{{ old('arrival_date', $import->arrival_date) }}" required />
                @error('arrival_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label>Contenedores *</label>
                <div id="containers-container">
                    @if($import->containers && $import->containers->count() > 0)
                        @foreach($import->containers as $index => $container)
                            <div class="container-item" data-container-id="{{ $container->id }}" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <strong style="color: #333;">Contenedor #{{ $index + 1 }}</strong>
                                    <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">Eliminar</button>
                                </div>
                                <input type="hidden" name="containers[{{ $index }}][id]" value="{{ $container->id }}">
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label style="font-size: 13px; margin-bottom: 4px;">Referencia *</label>
                                    <input type="text" name="containers[{{ $index }}][reference]" id="container_ref_edit_{{ $index }}" class="form-control translate-field" style="padding: 8px 12px; font-size: 14px;" value="{{ old('containers.'.$index.'.reference', $container->reference) }}" required />
                                </div>
                                <div class="form-group" style="margin-bottom: 12px;">
                                    <label style="font-size: 13px; margin-bottom: 4px;">PDF con Información del Contenedor</label>
                                    @if($container->pdf_path)
                                        <div><a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                                    @endif
                                    <input type="file" name="containers[{{ $index }}][pdf]" class="form-control" style="padding: 6px; font-size: 13px; margin-top: 8px;" accept="application/pdf" />
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 13px; margin-bottom: 4px;">PDF con Imágenes del Contenedor</label>
                                    @if($container->image_pdf_path)
                                        <div><a href="{{ route('imports.view', [$import->id, 'container_'.$container->id.'_image_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF de imágenes actual</a></div>
                                    @endif
                                    <input type="file" name="containers[{{ $index }}][image_pdf]" class="form-control" style="padding: 6px; font-size: 13px; margin-top: 8px;" accept="application/pdf" />
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="container-item" style="border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <strong style="color: #333;">Contenedor #1</strong>
                                <button type="button" class="btn-remove-container" style="background: #dc3545; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; display: none;">Eliminar</button>
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size: 13px; margin-bottom: 4px;">Referencia *</label>
                                <input type="text" name="containers[0][reference]" id="container_ref_edit_0" class="form-control translate-field" style="padding: 8px 12px; font-size: 14px;" required />
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <label style="font-size: 13px; margin-bottom: 4px;">PDF con Información del Contenedor</label>
                                <input type="file" name="containers[0][pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label style="font-size: 13px; margin-bottom: 4px;">PDF con Imágenes del Contenedor</label>
                                <input type="file" name="containers[0][image_pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                            </div>
                        </div>
                    @endif
                </div>
                <button type="button" id="add-container-btn" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                    <i class="bi bi-plus-circle me-1"></i>Agregar Contenedor
                </button>
                @error('containers') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_pdf">Proforma Invoice (PDF)</label>
                @if($import->proforma_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'proforma_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="proforma_pdf" id="proforma_pdf" class="@error('proforma_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('proforma_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="proforma_invoice_low_pdf">Proforma Invoice Low (PDF)</label>
                @if($import->proforma_invoice_low_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'proforma_invoice_low_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="proforma_invoice_low_pdf" id="proforma_invoice_low_pdf" class="@error('proforma_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('proforma_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="invoice_pdf">Commercial Invoice (PDF)</label>
                @if($import->invoice_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'invoice_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="invoice_pdf" id="invoice_pdf" class="@error('invoice_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('invoice_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="commercial_invoice_low_pdf">Commercial Invoice Low (PDF)</label>
                @if($import->commercial_invoice_low_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'commercial_invoice_low_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="commercial_invoice_low_pdf" id="commercial_invoice_low_pdf" class="@error('commercial_invoice_low_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('commercial_invoice_low_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="packing_list_pdf">Packing List (PDF)</label>
                @if($import->packing_list_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'packing_list_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="packing_list_pdf" id="packing_list_pdf" class="@error('packing_list_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('packing_list_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="bl_pdf">Bill of Lading (PDF)</label>
                @if($import->bl_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'bl_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="bl_pdf" id="bl_pdf" class="@error('bl_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('bl_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="apostillamiento_pdf">Apostillamiento (PDF)</label>
                @if($import->apostillamiento_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'apostillamiento_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="apostillamiento_pdf" id="apostillamiento_pdf" class="@error('apostillamiento_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('apostillamiento_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>


            <div class="form-group">
                <label for="shipping_company">Naviera o Agente de Carga</label>
                <input type="text" name="shipping_company" id="shipping_company" class="@error('shipping_company') is-invalid @enderror translate-field" value="{{ old('shipping_company', $import->shipping_company) }}" />
                @error('shipping_company') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="free_days_at_dest">Días libres destino</label>
                <input type="number" name="free_days_at_dest" id="free_days_at_dest" class="@error('free_days_at_dest') is-invalid @enderror" min="0" value="{{ old('free_days_at_dest', $import->free_days_at_dest) }}" />
                @error('free_days_at_dest') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="other_documents_pdf">Otros Documentos (PDF)</label>
                @if($import->other_documents_pdf)
                    <div><a href="{{ route('imports.view', [$import->id, 'other_documents_pdf']) }}" class="file-link" target="_blank"><i class="bi bi-file-pdf me-1"></i>Ver PDF actual</a></div>
                @endif
                <input type="file" name="other_documents_pdf" id="other_documents_pdf" class="@error('other_documents_pdf') is-invalid @enderror" accept="application/pdf" style="margin-top: 8px;" />
                @error('other_documents_pdf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="credit_time">{{ __('common.tiempo_credito') }}</label>
                <select name="credit_time" id="credit_time" class="@error('credit_time') is-invalid @enderror">
                    <option value="" {{ old('credit_time', $import->credit_time) == '' || old('credit_time', $import->credit_time) == null ? 'selected' : '' }}>{{ __('common.sin_credito') }}</option>
                    <option value="15" {{ old('credit_time', $import->credit_time) == '15' ? 'selected' : '' }}>15 {{ __('common.dias') }}</option>
                    <option value="30" {{ old('credit_time', $import->credit_time) == '30' ? 'selected' : '' }}>30 {{ __('common.dias') }}</option>
                    <option value="45" {{ old('credit_time', $import->credit_time) == '45' ? 'selected' : '' }}>45 {{ __('common.dias') }}</option>
                </select>
                @error('credit_time') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="actions">
                <a href="{{ Auth::user()->rol === 'admin' ? route('imports.index') : route('imports.provider-index') }}" class="btn-cancel">Cancelar</a>
                <button type="submit" class="btn-save">Actualizar</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let containerCount = {{ $import->containers ? $import->containers->count() : 1 }};
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
                    <input type="text" name="containers[${containerCount}][reference]" id="container_ref_edit_${containerCount}" class="form-control translate-field" style="padding: 8px 12px; font-size: 14px;" required />
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label style="font-size: 13px; margin-bottom: 4px;">PDF con Información del Contenedor</label>
                    <input type="file" name="containers[${containerCount}][pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="font-size: 13px; margin-bottom: 4px;">PDF con Imágenes del Contenedor</label>
                    <input type="file" name="containers[${containerCount}][image_pdf]" class="form-control" style="padding: 6px; font-size: 13px;" accept="application/pdf" />
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

// Función auxiliar para traducir texto usando Google Translate
async function translateText(text) {
    if (!text || text.trim() === '') return null;
    
    try {
        const response = await fetch(`https://translate.googleapis.com/translate_a/single?client=gtx&sl=es&tl=zh-CN&dt=t&q=${encodeURIComponent(text)}`);
        const data = await response.json();
        
        if (data && data[0] && data[0][0] && data[0][0][0]) {
            return data[0].map(item => item[0]).join('');
        }
    } catch (error) {
        console.error('Error traduciendo texto:', error);
    }
    return null;
}

// Función para traducir todos los elementos del formulario
async function translateAllToChinese() {
    Swal.fire({
        title: 'Traduciendo...',
        html: 'Traduciendo todo el formulario a chino',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    let translatedCount = 0;
    let errorCount = 0;
    
    // 1. Traducir todos los labels
    const labels = document.querySelectorAll('label');
    for (const label of labels) {
        const originalText = label.textContent.trim();
        if (originalText && !label.querySelector('input, select, textarea')) {
            const translated = await translateText(originalText);
            if (translated) {
                label.textContent = translated;
                translatedCount++;
            } else {
                errorCount++;
            }
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    // 2. Traducir todos los placeholders
    const inputsWithPlaceholder = document.querySelectorAll('input[placeholder], textarea[placeholder]');
    for (const input of inputsWithPlaceholder) {
        if (input.placeholder) {
            const translated = await translateText(input.placeholder);
            if (translated) {
                input.placeholder = translated;
                translatedCount++;
            } else {
                errorCount++;
            }
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    // 3. Traducir todos los botones (excepto el botón de traducción)
    const buttons = document.querySelectorAll('button:not(.translate-btn), input[type="submit"]');
    for (const button of buttons) {
        const buttonText = button.textContent.trim() || button.value.trim();
        if (buttonText && buttonText !== 'Traducir Todo a Chino' && buttonText !== 'Traducir') {
            const translated = await translateText(buttonText);
            if (translated) {
                if (button.tagName === 'INPUT') {
                    button.value = translated;
                } else {
                    button.textContent = translated;
                }
                translatedCount++;
            } else {
                errorCount++;
            }
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    // 4. Traducir valores de inputs que tengan texto
    const textInputs = document.querySelectorAll('input[type="text"], textarea');
    for (const input of textInputs) {
        if (input.value && input.value.trim() !== '') {
            const translated = await translateText(input.value);
            if (translated) {
                input.value = translated;
                translatedCount++;
            } else {
                errorCount++;
            }
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    // 5. Traducir textos en elementos small
    const smallElements = document.querySelectorAll('small');
    for (const small of smallElements) {
        const originalText = small.textContent.trim();
        if (originalText) {
            const translated = await translateText(originalText);
            if (translated) {
                small.textContent = translated;
                translatedCount++;
            } else {
                errorCount++;
            }
            await new Promise(resolve => setTimeout(resolve, 150));
        }
    }
    
    Swal.fire({
        icon: translatedCount > 0 ? 'success' : 'error',
        title: translatedCount > 0 ? 'Traducción Completada' : 'Error',
        html: `Se tradujeron ${translatedCount} elemento(s) correctamente${errorCount > 0 ? `<br>${errorCount} elemento(s) no se pudieron traducir` : ''}`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
