<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;
use App\Models\ImportContainer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use iio\libmergepdf\Merger;
use Illuminate\Support\Facades\File;

class ImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ADMIN: Show all imports
    public function index()
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }
        $imports = Import::with(['user', 'containers'])->orderByDesc('created_at')->get();
        
        // Update status based on dates
        $this->updateImportStatuses($imports);
        
        // Reload to get updated statuses
        $imports = Import::with(['user', 'containers'])->orderByDesc('created_at')->get();
        
        return view('imports.index', compact('imports'));
    }

    // PROVIDER: Show their imports
    public function providerIndex()
    {
        if (!in_array(Auth::user()->rol, ['importer','admin'])) {
            abort(403);
        }
        $imports = Import::with('containers')->where('user_id', Auth::id())->orderByDesc('created_at')->get();
        
        // Update status based on dates
        $this->updateImportStatuses($imports);
        
        // Reload to get updated statuses
        $imports = Import::with('containers')->where('user_id', Auth::id())->orderByDesc('created_at')->get();
        
        return view('imports.provider-index', compact('imports'));
    }

    // PROVIDER: Form to create an import
    public function create()
    {
        if (!in_array(Auth::user()->rol, ['importer','admin'])) {
            abort(403);
        }
        return view('imports.create');
    }

    // PROVIDER: Store new import
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->rol, ['importer','admin'])) {
            abort(403);
        }
        $data = $request->validate([
            'commercial_invoice_number' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date',
            'proforma_invoice_number' => 'nullable|string|max:255',
            'bl_number' => 'nullable|string|max:255',
            'containers' => 'required|array|min:1',
            'containers.*.reference' => 'required|string|max:255',
            'containers.*.pdf' => 'nullable|file|mimes:pdf',
            'proforma_pdf' => 'nullable|file|mimes:pdf',
            'invoice_pdf' => 'nullable|file|mimes:pdf',
            'bl_pdf' => 'nullable|file|mimes:pdf',
            'proforma_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'commercial_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'packing_list_pdf' => 'nullable|file|mimes:pdf',
            'apostillamiento_pdf' => 'nullable|file|mimes:pdf',
            'other_documents_pdf' => 'nullable|file|mimes:pdf',
            'shipping_company' => 'nullable|string|max:255',
            'free_days_at_dest' => 'nullable|integer|min:0',
            'credit_time' => 'nullable|in:15,30,45',
        ]);

        $proformaPdfPath = null;
        if($request->hasFile('proforma_pdf')) {
            $proformaPdfPath = $request->file('proforma_pdf')->store('imports');
        }

        $invoicePdfPath = null;
        if($request->hasFile('invoice_pdf')) {
            $invoicePdfPath = $request->file('invoice_pdf')->store('imports');
        }

        $blPdfPath = null;
        if($request->hasFile('bl_pdf')) {
            $blPdfPath = $request->file('bl_pdf')->store('imports');
        }

        $proformaInvoiceLowPdfPath = null;
        if($request->hasFile('proforma_invoice_low_pdf')) {
            $proformaInvoiceLowPdfPath = $request->file('proforma_invoice_low_pdf')->store('imports');
        }

        $commercialInvoiceLowPdfPath = null;
        if($request->hasFile('commercial_invoice_low_pdf')) {
            $commercialInvoiceLowPdfPath = $request->file('commercial_invoice_low_pdf')->store('imports');
        }

        $packingListPdfPath = null;
        if($request->hasFile('packing_list_pdf')) {
            $packingListPdfPath = $request->file('packing_list_pdf')->store('imports');
        }

        $apostillamientoPdfPath = null;
        if($request->hasFile('apostillamiento_pdf')) {
            $apostillamientoPdfPath = $request->file('apostillamiento_pdf')->store('imports');
        }

        $otherDocumentsPdfPath = null;
        if($request->hasFile('other_documents_pdf')) {
            $otherDocumentsPdfPath = $request->file('other_documents_pdf')->store('imports');
        }

        // DO code calculation based on arrival_date
        $arrivalDate = $data['arrival_date'];
        $year = date('y', strtotime($arrivalDate));
        
        $lastImport = Import::whereRaw('SUBSTRING(do_code, 4, 2) = ?', [$year])
            ->whereYear('arrival_date', '20'.$year)
            ->orderByDesc('do_code')
            ->first();
        $next = 1;
        if ($lastImport && preg_match('/VJP'.$year.'-(\d{3})/', $lastImport->do_code, $m)) {
            $next = intval($m[1]) + 1;
        }
        $doCode = sprintf('VJP%s-%03d', $year, $next);

        // Calculate credits based on credit_time (assuming credit_time is in days)
        // You can adjust this calculation based on your business logic
        $credits = null;
        if (isset($data['credit_time'])) {
            // Example: credits = credit_time * some_factor
            // For now, we'll store it as the number of days
            $credits = floatval($data['credit_time']);
        }

        // Get supplier name from user
        $supplierName = Auth::user()->name ?? Auth::user()->email;

        $import = Import::create([
            'user_id' => Auth::id(),
            'commercial_invoice_number' => $data['commercial_invoice_number'],
            'proforma_invoice_number' => $data['proforma_invoice_number'] ?? null,
            'bl_number' => $data['bl_number'] ?? null,
            'origin' => $data['origin'],
            'destination' => 'Colombia', // Always Colombia
            'departure_date' => $data['departure_date'],
            'arrival_date' => $data['arrival_date'],
            'proforma_pdf' => $proformaPdfPath,
            'proforma_invoice_low_pdf' => $proformaInvoiceLowPdfPath,
            'invoice_pdf' => $invoicePdfPath,
            'commercial_invoice_low_pdf' => $commercialInvoiceLowPdfPath,
            'bl_pdf' => $blPdfPath,
            'packing_list_pdf' => $packingListPdfPath,
            'apostillamiento_pdf' => $apostillamientoPdfPath,
            'other_documents_pdf' => $otherDocumentsPdfPath,
            'shipping_company' => $data['shipping_company'] ?? null,
            'free_days_at_dest' => $data['free_days_at_dest'] ?? null,
            'credit_time' => !empty($data['credit_time']) ? $data['credit_time'] : null,
            'credits' => $credits,
            'status' => 'pending',
            'do_code' => $doCode,
        ]);

        // Handle multiple containers
        if ($request->has('containers')) {
            foreach ($request->containers as $index => $containerData) {
                $pdfPath = null;
                if ($request->hasFile("containers.{$index}.pdf")) {
                    $pdfPath = $request->file("containers.{$index}.pdf")->store('imports');
                }

                $imagePdfPath = null;
                if ($request->hasFile("containers.{$index}.image_pdf")) {
                    $imagePdfPath = $request->file("containers.{$index}.image_pdf")->store('imports');
                }

                \App\Models\ImportContainer::create([
                    'import_id' => $import->id,
                    'reference' => $containerData['reference'],
                    'pdf_path' => $pdfPath,
                    'image_pdf_path' => $imagePdfPath,
                ]);
            }
        }

        return redirect()->route('imports.provider-index')->with('success', 'Import submitted successfully!');
    }

    // PROVIDER: Edit their own import
    public function edit($id)
    {
        $import = Import::with('containers')->where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        return view('imports.edit', compact('import'));
    }

    public function update(Request $request, $id)
    {
        $import = Import::with('containers')->where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $data = $request->validate([
            'commercial_invoice_number' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date',
            'proforma_invoice_number' => 'nullable|string|max:255',
            'bl_number' => 'nullable|string|max:255',
            'containers' => 'required|array|min:1',
            'containers.*.id' => 'nullable|exists:import_containers,id',
            'containers.*.reference' => 'required|string|max:255',
            'containers.*.pdf' => 'nullable|file|mimes:pdf',
            'containers.*.image_pdf' => 'nullable|file|mimes:pdf',
            'proforma_pdf' => 'nullable|file|mimes:pdf',
            'proforma_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'invoice_pdf' => 'nullable|file|mimes:pdf',
            'commercial_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'bl_pdf' => 'nullable|file|mimes:pdf',
            'packing_list_pdf' => 'nullable|file|mimes:pdf',
            'apostillamiento_pdf' => 'nullable|file|mimes:pdf',
            'other_documents_pdf' => 'nullable|file|mimes:pdf',
            'shipping_company' => 'nullable|string|max:255',
            'free_days_at_dest' => 'nullable|integer|min:0',
            'credit_time' => 'nullable|in:15,30,45',
        ]);

        // File handling for other documents
        if($request->hasFile('proforma_pdf')) {
            if($import->proforma_pdf) {
                Storage::delete($import->proforma_pdf);
            }
            $data['proforma_pdf'] = $request->file('proforma_pdf')->store('imports');
        }

        if($request->hasFile('invoice_pdf')) {
            if($import->invoice_pdf) {
                Storage::delete($import->invoice_pdf);
            }
            $data['invoice_pdf'] = $request->file('invoice_pdf')->store('imports');
        }

        if($request->hasFile('bl_pdf')) {
            if($import->bl_pdf) {
                Storage::delete($import->bl_pdf);
            }
            $data['bl_pdf'] = $request->file('bl_pdf')->store('imports');
        }

        if($request->hasFile('proforma_invoice_low_pdf')) {
            if($import->proforma_invoice_low_pdf) {
                Storage::delete($import->proforma_invoice_low_pdf);
            }
            $data['proforma_invoice_low_pdf'] = $request->file('proforma_invoice_low_pdf')->store('imports');
        }

        if($request->hasFile('commercial_invoice_low_pdf')) {
            if($import->commercial_invoice_low_pdf) {
                Storage::delete($import->commercial_invoice_low_pdf);
            }
            $data['commercial_invoice_low_pdf'] = $request->file('commercial_invoice_low_pdf')->store('imports');
        }

        if($request->hasFile('packing_list_pdf')) {
            if($import->packing_list_pdf) {
                Storage::delete($import->packing_list_pdf);
            }
            $data['packing_list_pdf'] = $request->file('packing_list_pdf')->store('imports');
        }

        if($request->hasFile('apostillamiento_pdf')) {
            if($import->apostillamiento_pdf) {
                Storage::delete($import->apostillamiento_pdf);
            }
            $data['apostillamiento_pdf'] = $request->file('apostillamiento_pdf')->store('imports');
        }

        if($request->hasFile('other_documents_pdf')) {
            if($import->other_documents_pdf) {
                Storage::delete($import->other_documents_pdf);
            }
            $data['other_documents_pdf'] = $request->file('other_documents_pdf')->store('imports');
        }

        // Calculate credits
        $credits = null;
        if (isset($data['credit_time']) && !empty($data['credit_time'])) {
            $credits = floatval($data['credit_time']);
        }
        $data['destination'] = 'Colombia'; // Always Colombia
        $data['credits'] = $credits;
        $data['credit_time'] = !empty($data['credit_time']) ? $data['credit_time'] : null;
        
        $import->update($data);

        // Handle containers
        $existingContainerIds = [];
        if ($request->has('containers')) {
            foreach ($request->containers as $index => $containerData) {
                $containerId = $containerData['id'] ?? null;
                
                $pdfPath = null;
                if ($request->hasFile("containers.{$index}.pdf")) {
                    $pdfPath = $request->file("containers.{$index}.pdf")->store('imports');
                }

                $imagePdfPath = null;
                if ($request->hasFile("containers.{$index}.image_pdf")) {
                    $imagePdfPath = $request->file("containers.{$index}.image_pdf")->store('imports');
                }

                if ($containerId) {
                    // Update existing container
                    $container = \App\Models\ImportContainer::find($containerId);
                    if ($container && $container->import_id == $import->id) {
                        $container->reference = $containerData['reference'];
                        if ($pdfPath) {
                            if ($container->pdf_path) {
                                Storage::delete($container->pdf_path);
                            }
                            $container->pdf_path = $pdfPath;
                        }
                        if ($imagePdfPath) {
                            if ($container->image_pdf_path) {
                                Storage::delete($container->image_pdf_path);
                            }
                            $container->image_pdf_path = $imagePdfPath;
                        }
                        $container->save();
                        $existingContainerIds[] = $containerId;
                    }
                } else {
                    // Create new container
                    \App\Models\ImportContainer::create([
                        'import_id' => $import->id,
                        'reference' => $containerData['reference'],
                        'pdf_path' => $pdfPath,
                        'image_pdf_path' => $imagePdfPath,
                    ]);
                }
            }
        }

        // Delete containers that were removed
        $import->containers()->whereNotIn('id', $existingContainerIds)->delete();

        return redirect()->route('imports.provider-index')->with('success', 'Import updated successfully!');
    }

    // ADMIN/PROVIDER: View files
    public function viewFile($id, $fileType)
    {
        $import = Import::with('containers')->findOrFail($id);
        // Only admin or owner can view
        if (Auth::user()->rol !== 'admin' && $import->user_id !== Auth::id()) {
            abort(403);
        }
        
        $filePath = null;
        if($fileType === 'proforma_pdf' && $import->proforma_pdf) {
            $filePath = $import->proforma_pdf;
        } elseif($fileType === 'proforma_invoice_low_pdf' && $import->proforma_invoice_low_pdf) {
            $filePath = $import->proforma_invoice_low_pdf;
        } elseif($fileType === 'invoice_pdf' && $import->invoice_pdf) {
            $filePath = $import->invoice_pdf;
        } elseif($fileType === 'commercial_invoice_low_pdf' && $import->commercial_invoice_low_pdf) {
            $filePath = $import->commercial_invoice_low_pdf;
        } elseif($fileType === 'packing_list_pdf' && $import->packing_list_pdf) {
            $filePath = $import->packing_list_pdf;
        } elseif($fileType === 'bl_pdf' && $import->bl_pdf) {
            $filePath = $import->bl_pdf;
        } elseif($fileType === 'apostillamiento_pdf' && $import->apostillamiento_pdf) {
            $filePath = $import->apostillamiento_pdf;
        } elseif($fileType === 'other_documents_pdf' && $import->other_documents_pdf) {
            $filePath = $import->other_documents_pdf;
        } elseif(strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if(count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if($container) {
                    if($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif($parts[2] === 'image' && $parts[3] === 'pdf' && $container->image_pdf_path) {
                        $filePath = $container->image_pdf_path;
                    }
                }
            }
        }
        
        if(!$filePath || !Storage::exists($filePath)) {
            abort(404);
        }
        
        $fullPath = Storage::path($filePath);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        
        $mimeType = Storage::mimeType($filePath);
        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
        ]);
    }

    // ADMIN/PROVIDER: Download files
    public function downloadFile($id, $fileType)
    {
        $import = Import::with('containers')->findOrFail($id);
        // Only admin or owner can download
        if (Auth::user()->rol !== 'admin' && $import->user_id !== Auth::id()) {
            abort(403);
        }
        
        $filePath = null;
        if($fileType === 'proforma_pdf' && $import->proforma_pdf) {
            $filePath = $import->proforma_pdf;
        } elseif($fileType === 'proforma_invoice_low_pdf' && $import->proforma_invoice_low_pdf) {
            $filePath = $import->proforma_invoice_low_pdf;
        } elseif($fileType === 'invoice_pdf' && $import->invoice_pdf) {
            $filePath = $import->invoice_pdf;
        } elseif($fileType === 'commercial_invoice_low_pdf' && $import->commercial_invoice_low_pdf) {
            $filePath = $import->commercial_invoice_low_pdf;
        } elseif($fileType === 'packing_list_pdf' && $import->packing_list_pdf) {
            $filePath = $import->packing_list_pdf;
        } elseif($fileType === 'bl_pdf' && $import->bl_pdf) {
            $filePath = $import->bl_pdf;
        } elseif($fileType === 'apostillamiento_pdf' && $import->apostillamiento_pdf) {
            $filePath = $import->apostillamiento_pdf;
        } elseif($fileType === 'other_documents_pdf' && $import->other_documents_pdf) {
            $filePath = $import->other_documents_pdf;
        } elseif(strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if(count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if($container) {
                    if($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif($parts[2] === 'image' && $parts[3] === 'pdf' && $container->image_pdf_path) {
                        $filePath = $container->image_pdf_path;
                    }
                }
            }
        }
        
        if(!$filePath || !Storage::exists($filePath)) {
            abort(404);
        }
        
        return Storage::download($filePath);
    }

    /**
     * Generate PDF report for an import with all uploaded PDFs unified
     */
    public function report($id)
    {
        // Permitir acceso a admin y funcionario
        if (!in_array(Auth::user()->rol, ['admin', 'funcionario'])) {
            abort(403);
        }
        
        // Limpiar información de PDFs omitidos de sesiones anteriores
        session()->forget('pdfs_omitted_info');
        
        $import = Import::with(['user', 'containers'])->findOrFail($id);
        
        $isExport = true;
        $currentUser = Auth::user();
        
        // Generar el PDF del reporte
        $pdf = Pdf::loadView('imports.report', compact('import', 'isExport', 'currentUser'));
        
        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/temp_pdfs');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }
        
        // Guardar el PDF del reporte temporalmente
        $reportPdfPath = $tempDir . '/report_' . $import->id . '_' . time() . '.pdf';
        file_put_contents($reportPdfPath, $pdf->output());
        
        // Verificar que el reporte se guardó correctamente
        if (!File::exists($reportPdfPath) || filesize($reportPdfPath) === 0) {
            \Log::error('Error al guardar el PDF del reporte');
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        }
        
        // Lista de PDFs a unificar en orden específico (después del reporte)
        $pdfsToMerge = [];
        
        // Verificar el rol del usuario para determinar qué PDFs puede ver
        $userRole = Auth::user()->rol;
        $isFuncionario = ($userRole === 'funcionario');
        
        if ($isFuncionario) {
            // FUNCIONARIO: Solo puede ver estos PDFs:
            // 1. Proforma Invoice Low
            if ($import->proforma_invoice_low_pdf && Storage::exists($import->proforma_invoice_low_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->proforma_invoice_low_pdf),
                    'name' => 'Proforma Invoice Low'
                ];
            }
            
            // 2. Commercial Invoice Low
            if ($import->commercial_invoice_low_pdf && Storage::exists($import->commercial_invoice_low_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->commercial_invoice_low_pdf),
                    'name' => 'Commercial Invoice Low'
                ];
            }
            
            // 3. Packing List
            if ($import->packing_list_pdf && Storage::exists($import->packing_list_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->packing_list_pdf),
                    'name' => 'Packing List'
                ];
            }
            
            // 4. Bill of Lading (BL)
            if ($import->bl_pdf && Storage::exists($import->bl_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->bl_pdf),
                    'name' => 'Bill of Lading'
                ];
            }
            
            // 5. Apostillamiento
            if ($import->apostillamiento_pdf && Storage::exists($import->apostillamiento_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->apostillamiento_pdf),
                    'name' => 'Apostillamiento'
                ];
            }
            
            // 6. Otros Documentos
            if ($import->other_documents_pdf && Storage::exists($import->other_documents_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->other_documents_pdf),
                    'name' => 'Otros Documentos'
                ];
            }
            
            // 7. PDFs de contenedores (por cada contenedor)
            if ($import->containers && $import->containers->count() > 0) {
                foreach ($import->containers as $container) {
                    // PDF de información del contenedor
                    if ($container->pdf_path && Storage::exists($container->pdf_path)) {
                        $pdfsToMerge[] = [
                            'path' => storage_path('app/' . $container->pdf_path),
                            'name' => 'Info Contenedor ' . $container->reference
                        ];
                    }
                    
                    // PDF de imágenes del contenedor
                    if ($container->image_pdf_path && Storage::exists($container->image_pdf_path)) {
                        $pdfsToMerge[] = [
                            'path' => storage_path('app/' . $container->image_pdf_path),
                            'name' => 'Imágenes Contenedor ' . $container->reference
                        ];
                    }
                }
            }
        } else {
            // ADMIN: Puede ver todos los PDFs
            // 1. Proforma Invoice
            if ($import->proforma_pdf && Storage::exists($import->proforma_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->proforma_pdf),
                    'name' => 'Proforma Invoice'
                ];
            }
            
            // 2. Proforma Invoice Low
            if ($import->proforma_invoice_low_pdf && Storage::exists($import->proforma_invoice_low_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->proforma_invoice_low_pdf),
                    'name' => 'Proforma Invoice Low'
                ];
            }
            
            // 3. Commercial Invoice
            if ($import->invoice_pdf && Storage::exists($import->invoice_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->invoice_pdf),
                    'name' => 'Commercial Invoice'
                ];
            }
            
            // 4. Commercial Invoice Low
            if ($import->commercial_invoice_low_pdf && Storage::exists($import->commercial_invoice_low_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->commercial_invoice_low_pdf),
                    'name' => 'Commercial Invoice Low'
                ];
            }
            
            // 5. Packing List
            if ($import->packing_list_pdf && Storage::exists($import->packing_list_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->packing_list_pdf),
                    'name' => 'Packing List'
                ];
            }
            
            // 6. Bill of Lading (BL)
            if ($import->bl_pdf && Storage::exists($import->bl_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->bl_pdf),
                    'name' => 'Bill of Lading'
                ];
            }
            
            // 7. Apostillamiento
            if ($import->apostillamiento_pdf && Storage::exists($import->apostillamiento_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->apostillamiento_pdf),
                    'name' => 'Apostillamiento'
                ];
            }
            
            // 8. Otros Documentos
            if ($import->other_documents_pdf && Storage::exists($import->other_documents_pdf)) {
                $pdfsToMerge[] = [
                    'path' => storage_path('app/' . $import->other_documents_pdf),
                    'name' => 'Otros Documentos'
                ];
            }
            
            // 9. PDFs de contenedores (por cada contenedor)
            if ($import->containers && $import->containers->count() > 0) {
                foreach ($import->containers as $container) {
                    // PDF de información del contenedor
                    if ($container->pdf_path && Storage::exists($container->pdf_path)) {
                        $pdfsToMerge[] = [
                            'path' => storage_path('app/' . $container->pdf_path),
                            'name' => 'Info Contenedor ' . $container->reference
                        ];
                    }
                    
                    // PDF de imágenes del contenedor
                    if ($container->image_pdf_path && Storage::exists($container->image_pdf_path)) {
                        $pdfsToMerge[] = [
                            'path' => storage_path('app/' . $container->image_pdf_path),
                            'name' => 'Imágenes Contenedor ' . $container->reference
                        ];
                    }
                }
            }
        }
        
        // Usar método mejorado que intenta mergear PDFs de forma más robusta
        return $this->mergePdfsRobust($import, $reportPdfPath, $pdfsToMerge, $tempDir, $pdf);
    }
    
    /**
     * Mergear PDFs de forma robusta, intentando identificar y omitir PDFs problemáticos
     */
    private function mergePdfsRobust($import, $reportPdfPath, $pdfsToMerge, $tempDir, $pdf)
    {
        // Si no hay PDFs adicionales para unificar, devolver solo el reporte
        if (empty($pdfsToMerge)) {
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            return response()->download($reportPdfPath, $filename)->deleteFileAfterSend(true);
        }
        
        // Inicializar el mergeador de PDFs
        $merger = new Merger();
        
        // Agregar el PDF del reporte primero (este siempre se incluye como primera página)
        $merger->addFile($reportPdfPath);
        
        // Agregar todos los PDFs adicionales al mergeador
        // IMPORTANTE: Si un PDF falla, se continúa con los demás
        $pdfsAdded = 0;
        $pdfsFailed = [];
        $pdfsSuccessfullyAdded = []; // Guardar los PDFs que se agregaron exitosamente
        
        foreach ($pdfsToMerge as $pdfInfo) {
            try {
                if (File::exists($pdfInfo['path'])) {
                    $fileSize = filesize($pdfInfo['path']);
                    if ($fileSize > 0) {
                        // Intentar agregar el PDF al mergeador
                        // Si el PDF tiene compresión no soportada, esto puede lanzar una excepción
                        // PERO algunos PDFs problemáticos solo fallan en el merge(), no en addFile()
                        $merger->addFile($pdfInfo['path']);
                        $pdfsAdded++;
                        $pdfsSuccessfullyAdded[] = $pdfInfo; // Guardar para uso posterior si el merge falla
                        \Log::info('PDF agregado al merge: ' . $pdfInfo['name'] . ' - Tamaño: ' . $fileSize . ' bytes');
                    } else {
                        \Log::warning('PDF vacío ignorado: ' . $pdfInfo['name']);
                        $pdfsFailed[] = $pdfInfo['name'] . ' (archivo vacío)';
                    }
                } else {
                    \Log::warning('PDF no encontrado: ' . $pdfInfo['name'] . ' - Path: ' . $pdfInfo['path']);
                    $pdfsFailed[] = $pdfInfo['name'] . ' (no encontrado)';
                }
            } catch (\Exception $e) {
                // Si hay un error al agregar un PDF (p.ej., compresión no soportada), continuar con los demás
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'compression technique') !== false || strpos($errorMsg, 'FPDI') !== false) {
                    \Log::warning('PDF con formato no compatible ignorado (addFile): ' . $pdfInfo['name'] . ' - El PDF usa una compresión no soportada por la versión gratuita de FPDI');
                } else {
                    \Log::warning('Error al agregar PDF al merge: ' . $pdfInfo['name'] . ' - ' . $errorMsg);
                }
                $pdfsFailed[] = $pdfInfo['name'] . ' (error: ' . substr($errorMsg, 0, 100) . ')';
                continue;
            }
        }
        
        // Registrar resumen de PDFs procesados
        if (!empty($pdfsFailed)) {
            \Log::info('PDFs que no se pudieron agregar al merge: ' . implode(', ', $pdfsFailed));
            
            // Almacenar información sobre PDFs que fallaron en addFile()
            $omittedPdfsInfo = [
                'do_code' => $import->do_code,
                'total_pdfs' => count($pdfsToMerge) + 1, // +1 por el reporte
                'included_pdfs' => $pdfsAdded + 1, // +1 por el reporte
                'omitted_pdfs' => count($pdfsFailed),
                'omitted_list' => $pdfsFailed,
                'reason' => 'compresión no soportada o error al agregar'
            ];
            session(['pdfs_omitted_info' => $omittedPdfsInfo]);
        }
        
        // Si no se agregó ningún PDF adicional, devolver solo el reporte
        if ($pdfsAdded === 0) {
            \Log::warning('No se agregó ningún PDF adicional para unificar');
            // Almacenar información sobre que no se pudo agregar ningún PDF
            $omittedPdfsInfo = [
                'do_code' => $import->do_code,
                'total_pdfs' => count($pdfsToMerge) + 1, // +1 por el reporte
                'included_pdfs' => 1, // Solo el reporte
                'omitted_pdfs' => count($pdfsToMerge),
                'omitted_list' => $pdfsFailed,
                'reason' => 'compresión no soportada o error al agregar',
                'timestamp' => time()
            ];
            session(['pdfs_omitted_info' => $omittedPdfsInfo]);
            
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            // Nota: La información de PDFs omitidos está almacenada en la sesión
            // Se mostrará cuando el usuario vuelva a cargar la página después de descargar
            return response()->download($reportPdfPath, $filename)->deleteFileAfterSend(true);
        }
        
        try {
            // Generar el PDF unificado (incluye el reporte + todos los PDFs agregados)
            \Log::info('Iniciando merge de PDFs para importación ' . $import->do_code . '. Total archivos a unificar: ' . ($pdfsAdded + 1)); // +1 por el reporte
            
            // Intentar mergear. Si falla, intentar mergear PDFs de uno en uno para identificar el problemático
            try {
                $mergedPdf = $merger->merge();
            } catch (\Exception $mergeError) {
                // Si el merge falla, intentar mergear PDFs de uno en uno para identificar y omitir el problemático
                $errorMsg = $mergeError->getMessage();
                \Log::warning('Merge inicial falló, intentando mergear PDFs individualmente: ' . substr($errorMsg, 0, 200));
                // Pasar solo los PDFs que se agregaron exitosamente, ya que sabemos que estos pasaron el addFile()
                // El merge individual intentará agregarlos uno por uno para identificar cuál causa el problema
                return $this->mergePdfsIndividually($import, $reportPdfPath, $pdfsSuccessfullyAdded, $tempDir, $pdf);
            }
            
            // Verificar que el merge generó contenido
            if (empty($mergedPdf) || strlen($mergedPdf) === 0) {
                throw new \Exception('El merge no generó contenido. Tamaño del resultado: ' . strlen($mergedPdf ?? 'null') . ' bytes');
            }
            
            \Log::info('Merge completado exitosamente. Tamaño del PDF unificado generado: ' . strlen($mergedPdf) . ' bytes');
            
            // Si hay PDFs omitidos, la información ya está en la sesión desde antes
            // Si no hay PDFs omitidos, limpiar la información de la sesión
            if (empty($pdfsFailed)) {
                session()->forget('pdfs_omitted_info');
            }
            
            // Guardar el PDF unificado temporalmente
            $mergedPdfPath = $tempDir . '/merged_' . $import->id . '_' . time() . '.pdf';
            $bytesWritten = file_put_contents($mergedPdfPath, $mergedPdf);
            
            if ($bytesWritten === false) {
                throw new \Exception('Error al escribir el archivo PDF unificado en disco');
            }
            
            // Verificar que el PDF unificado se creó correctamente
            if (!File::exists($mergedPdfPath)) {
                throw new \Exception('El archivo PDF unificado no se creó en la ruta: ' . $mergedPdfPath);
            }
            
            $finalFileSize = filesize($mergedPdfPath);
            if ($finalFileSize === 0 || $finalFileSize === false) {
                throw new \Exception('El archivo PDF unificado está vacío. Tamaño: ' . $finalFileSize);
            }
            
            \Log::info('PDF unificado guardado exitosamente. Ruta: ' . $mergedPdfPath . ' - Tamaño: ' . $finalFileSize . ' bytes - Bytes escritos: ' . $bytesWritten);
            
            // Limpiar el PDF del reporte temporal ahora que tenemos el unificado exitosamente
            if (File::exists($reportPdfPath)) {
                File::delete($reportPdfPath);
            }
            
            // Nombre del archivo final
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            
            // Descargar el PDF unificado
            // Nota: La información de PDFs omitidos está almacenada en la sesión
            // Se mostrará cuando el usuario vuelva a cargar la página después de descargar
            return response()->download($mergedPdfPath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            // Si hay un error al unificar (p.ej., PDF con compresión no soportada)
            $errorMessage = $e->getMessage();
            $isCompressionError = (strpos($errorMessage, 'compression technique') !== false || 
                                  strpos($errorMessage, 'FPDI') !== false ||
                                  strpos($errorMessage, 'parser') !== false);
            
            \Log::error('Error al unificar PDFs: ' . $errorMessage, [
                'import_id' => $import->id,
                'do_code' => $import->do_code,
                'pdfs_added' => $pdfsAdded,
                'pdfs_failed' => !empty($pdfsFailed) ? implode(', ', $pdfsFailed) : 'ninguno',
                'is_compression_error' => $isCompressionError,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si es un error de compresión, informar que algunos PDFs no pudieron ser incluidos
            if ($isCompressionError && $pdfsAdded > 0) {
                // Intentar crear un nuevo merger solo con el reporte y los PDFs que sí funcionaron
                // Pero como no sabemos cuál PDF causó el problema, devolvemos solo el reporte
                \Log::warning('Algunos PDFs no pudieron ser unificados debido a compresión no soportada. Se devuelve solo el reporte.');
            }
            
            // Devolver solo el reporte como fallback
            if (File::exists($reportPdfPath)) {
                $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
                \Log::info('Devolviendo solo el reporte debido a error en el merge');
                return response()->download($reportPdfPath, $filename)->deleteFileAfterSend(true);
            } else {
                // Si no existe el reporte temporal, generar uno nuevo sin unificar
                $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
                \Log::warning('El reporte temporal no existe, generando uno nuevo');
                return $pdf->download($filename);
            }
        }
    }
    
    /**
     * Mergear PDFs de uno en uno para identificar y omitir PDFs problemáticos
     */
    private function mergePdfsIndividually($import, $reportPdfPath, $pdfsToMerge, $tempDir, $pdf)
    {
        \Log::info('Iniciando merge individual de PDFs para identificar problemas. Total PDFs a procesar: ' . count($pdfsToMerge));
        
        // Verificar que el reporte existe
        if (!File::exists($reportPdfPath) || filesize($reportPdfPath) === 0) {
            \Log::error('El reporte no existe o está vacío: ' . $reportPdfPath);
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            return $pdf->download($filename);
        }
        
        $currentMergedPath = $reportPdfPath;
        $pdfsSuccessfullyAdded = 0;
        $pdfsSkipped = [];
        
        \Log::info('Reporte base incluido. Iniciando agregado de PDFs individuales...');
        
        // Mergear cada PDF de uno en uno
        foreach ($pdfsToMerge as $index => $pdfInfo) {
            try {
                if (!File::exists($pdfInfo['path']) || filesize($pdfInfo['path']) === 0) {
                    \Log::warning('PDF ' . ($index + 1) . '/' . count($pdfsToMerge) . ' omitido (no encontrado o vacío): ' . $pdfInfo['name']);
                    $pdfsSkipped[] = $pdfInfo['name'] . ' (no encontrado o vacío)';
                    continue;
                }
                
                \Log::info('Intentando agregar PDF ' . ($index + 1) . '/' . count($pdfsToMerge) . ': ' . $pdfInfo['name']);
                
                // Crear un nuevo merger con el PDF actualmente mergeado + el nuevo PDF
                $tempMerger = new Merger();
                $tempMerger->addFile($currentMergedPath);
                $tempMerger->addFile($pdfInfo['path']);
                
                // Intentar mergear
                $tempMerged = $tempMerger->merge();
                
                if (empty($tempMerged) || strlen($tempMerged) === 0) {
                    throw new \Exception('Merge vacío o sin contenido');
                }
                
                // Guardar el resultado temporal
                $newMergedPath = $tempDir . '/temp_merged_' . $import->id . '_' . time() . '_' . $pdfsSuccessfullyAdded . '.pdf';
                $bytesWritten = file_put_contents($newMergedPath, $tempMerged);
                
                if ($bytesWritten === false || !File::exists($newMergedPath) || filesize($newMergedPath) === 0) {
                    throw new \Exception('Error al guardar el PDF mergeado temporal');
                }
                
                // Si el merge anterior no era el reporte original, eliminarlo
                if ($currentMergedPath !== $reportPdfPath && File::exists($currentMergedPath)) {
                    File::delete($currentMergedPath);
                }
                
                $currentMergedPath = $newMergedPath;
                $pdfsSuccessfullyAdded++;
                \Log::info('✓ PDF agregado exitosamente (' . $pdfsSuccessfullyAdded . '): ' . $pdfInfo['name'] . ' - Tamaño mergeado: ' . filesize($newMergedPath) . ' bytes');
                
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'compression technique') !== false || strpos($errorMsg, 'FPDI') !== false) {
                    \Log::warning('✗ PDF omitido (compresión no soportada) ' . ($index + 1) . '/' . count($pdfsToMerge) . ': ' . $pdfInfo['name']);
                } else {
                    \Log::warning('✗ PDF omitido (error) ' . ($index + 1) . '/' . count($pdfsToMerge) . ': ' . $pdfInfo['name'] . ' - ' . substr($errorMsg, 0, 150));
                }
                $pdfsSkipped[] = $pdfInfo['name'];
                continue;
            }
        }
        
        // Si no se agregó ningún PDF adicional, devolver solo el reporte
        if ($pdfsSuccessfullyAdded === 0) {
            \Log::warning('No se pudo agregar ningún PDF adicional con merge individual. Devolviendo solo el reporte.');
            $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
            return response()->download($reportPdfPath, $filename)->deleteFileAfterSend(true);
        }
        
        // Renombrar el archivo final
        $finalMergedPath = $tempDir . '/merged_' . $import->id . '_' . time() . '.pdf';
        if ($currentMergedPath !== $finalMergedPath) {
            if (File::exists($finalMergedPath)) {
                File::delete($finalMergedPath);
            }
            File::move($currentMergedPath, $finalMergedPath);
        }
        
        $finalFileSize = filesize($finalMergedPath);
        \Log::info('Merge individual completado. PDFs agregados: ' . $pdfsSuccessfullyAdded . ', PDFs omitidos: ' . count($pdfsSkipped) . ', Tamaño final: ' . $finalFileSize . ' bytes');
        \Log::info('Total documentos en PDF final: ' . ($pdfsSuccessfullyAdded + 1) . ' (1 reporte + ' . $pdfsSuccessfullyAdded . ' PDFs adjuntos)');
        
        if (!empty($pdfsSkipped)) {
            \Log::info('PDFs omitidos (' . count($pdfsSkipped) . '): ' . implode(', ', $pdfsSkipped));
            
            // Almacenar información sobre PDFs omitidos en sesión para mostrar al usuario
            $omittedPdfsInfo = [
                'do_code' => $import->do_code,
                'total_pdfs' => count($pdfsToMerge) + 1, // +1 por el reporte
                'included_pdfs' => $pdfsSuccessfullyAdded + 1, // +1 por el reporte
                'omitted_pdfs' => count($pdfsSkipped),
                'omitted_list' => $pdfsSkipped,
                'reason' => 'compresión no soportada',
                'timestamp' => time()
            ];
            
            // Almacenar en sesión para mostrar cuando el usuario vuelva a cargar la página
            session(['pdfs_omitted_info' => $omittedPdfsInfo]);
        } else {
            // Limpiar información de PDFs omitidos si no hay
            session()->forget('pdfs_omitted_info');
        }
        
        // Limpiar el reporte temporal solo si no es el archivo final
        if (File::exists($reportPdfPath) && $reportPdfPath !== $finalMergedPath) {
            File::delete($reportPdfPath);
        }
        
        $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
        
        // Nota: La información de PDFs omitidos está almacenada en la sesión
        // Se mostrará cuando el usuario vuelva a cargar la página después de descargar
        return response()->download($finalMergedPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Export all DO reports - Genera un PDF unificado con todos los reportes de importaciones y sus PDFs adjuntos
     * Solo el admin puede acceder a este método
     */
    public function exportAllReports()
    {
        try {
            if (Auth::user()->rol !== 'admin') {
                abort(403);
            }
            
            // Obtener todas las importaciones ordenadas por fecha de creación
            $imports = Import::with(['user', 'containers'])->orderBy('created_at', 'desc')->get();
            
            if ($imports->isEmpty()) {
                return redirect()->route('imports.index')->with('error', 'No hay importaciones para generar el informe.');
            }
            
            \Log::info('Iniciando generación de informe general. Total de importaciones: ' . $imports->count());
            
            $isExport = true;
            $currentUser = Auth::user();
            
            // Crear directorio temporal si no existe
            $tempDir = storage_path('app/temp_pdfs');
            if (!File::exists($tempDir)) {
                File::makeDirectory($tempDir, 0755, true);
            }
            
            // Lista de TODOS los PDFs a unificar (reportes + PDFs adjuntos de cada importación)
            $allPdfsToMerge = [];
            $timestamp = time();
            
            // Para cada importación, generar el reporte y recopilar sus PDFs adjuntos
            foreach ($imports as $index => $import) {
                try {
                    \Log::info('Procesando importación ' . ($index + 1) . '/' . $imports->count() . ' - DO: ' . $import->do_code);
                    
                    // 1. Generar el PDF del reporte individual
                    $pdf = Pdf::loadView('imports.report', compact('import', 'isExport', 'currentUser'));
                    
                    // Guardar el PDF del reporte temporalmente
                    $reportPdfPath = $tempDir . '/report_all_' . $import->id . '_' . $timestamp . '.pdf';
                    file_put_contents($reportPdfPath, $pdf->output());
                    
                    // Verificar que el reporte se guardó correctamente
                    if (File::exists($reportPdfPath) && filesize($reportPdfPath) > 0) {
                        // Agregar el reporte primero
                        $allPdfsToMerge[] = [
                            'path' => $reportPdfPath,
                            'name' => 'Reporte ' . $import->do_code,
                            'is_temp' => true
                        ];
                        \Log::info('Reporte generado exitosamente para DO: ' . $import->do_code);
                    } else {
                        \Log::warning('El reporte PDF no se generó correctamente para DO: ' . $import->do_code);
                        continue; // Continuar con la siguiente importación si no se pudo generar el reporte
                    }
                    
                    // 2. Recopilar todos los PDFs adjuntos de esta importación (ADMIN puede ver todos)
                    // Proforma Invoice
                    if ($import->proforma_pdf && Storage::exists($import->proforma_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->proforma_pdf),
                            'name' => 'Proforma Invoice - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Proforma Invoice Low
                    if ($import->proforma_invoice_low_pdf && Storage::exists($import->proforma_invoice_low_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->proforma_invoice_low_pdf),
                            'name' => 'Proforma Invoice Low - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Commercial Invoice
                    if ($import->invoice_pdf && Storage::exists($import->invoice_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->invoice_pdf),
                            'name' => 'Commercial Invoice - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Commercial Invoice Low
                    if ($import->commercial_invoice_low_pdf && Storage::exists($import->commercial_invoice_low_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->commercial_invoice_low_pdf),
                            'name' => 'Commercial Invoice Low - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Packing List
                    if ($import->packing_list_pdf && Storage::exists($import->packing_list_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->packing_list_pdf),
                            'name' => 'Packing List - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Bill of Lading (BL)
                    if ($import->bl_pdf && Storage::exists($import->bl_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->bl_pdf),
                            'name' => 'Bill of Lading - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Apostillamiento
                    if ($import->apostillamiento_pdf && Storage::exists($import->apostillamiento_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->apostillamiento_pdf),
                            'name' => 'Apostillamiento - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // Otros Documentos
                    if ($import->other_documents_pdf && Storage::exists($import->other_documents_pdf)) {
                        $allPdfsToMerge[] = [
                            'path' => storage_path('app/' . $import->other_documents_pdf),
                            'name' => 'Otros Documentos - ' . $import->do_code,
                            'is_temp' => false
                        ];
                    }
                    
                    // PDFs de contenedores (por cada contenedor)
                    if ($import->containers && $import->containers->count() > 0) {
                        foreach ($import->containers as $container) {
                            // PDF de información del contenedor
                            if ($container->pdf_path && Storage::exists($container->pdf_path)) {
                                $allPdfsToMerge[] = [
                                    'path' => storage_path('app/' . $container->pdf_path),
                                    'name' => 'Info Contenedor ' . $container->reference . ' - ' . $import->do_code,
                                    'is_temp' => false
                                ];
                            }
                            
                            // PDF de imágenes del contenedor
                            if ($container->image_pdf_path && Storage::exists($container->image_pdf_path)) {
                                $allPdfsToMerge[] = [
                                    'path' => storage_path('app/' . $container->image_pdf_path),
                                    'name' => 'Imágenes Contenedor ' . $container->reference . ' - ' . $import->do_code,
                                    'is_temp' => false
                                ];
                            }
                        }
                    }
                    
                } catch (\Exception $e) {
                    \Log::error('Error al procesar importación ' . $import->do_code . ': ' . $e->getMessage());
                    continue; // Continuar con la siguiente importación
                }
            }
            
            if (empty($allPdfsToMerge)) {
                \Log::error('No se generó ningún PDF para unificar');
                return redirect()->route('imports.index')->with('error', 'No se pudieron generar los PDFs para unificar.');
            }
            
            \Log::info('Total de PDFs a unificar: ' . count($allPdfsToMerge) . '. Iniciando unificación...');
            
            // Usar el método robusto de merge (similar al método report)
            return $this->mergeAllPdfsRobust($allPdfsToMerge, $tempDir, $timestamp);
            
        } catch (\Exception $e) {
            \Log::error('Error fatal en exportAllReports: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('imports.index')->with('error', 'Error al generar el informe general: ' . $e->getMessage());
        }
    }
    
    /**
     * Mergear todos los PDFs de todas las importaciones en un solo documento
     * Similar al método mergePdfsRobust pero para múltiples importaciones
     */
    private function mergeAllPdfsRobust($allPdfsToMerge, $tempDir, $timestamp)
    {
        // Inicializar el mergeador de PDFs
        $merger = new Merger();
        
        // Agregar todos los PDFs al mergeador
        $pdfsAdded = 0;
        $pdfsFailed = [];
        
        foreach ($allPdfsToMerge as $pdfInfo) {
            try {
                if (File::exists($pdfInfo['path'])) {
                    $fileSize = filesize($pdfInfo['path']);
                    if ($fileSize > 0) {
                        // Intentar agregar el PDF al mergeador
                        $merger->addFile($pdfInfo['path']);
                        $pdfsAdded++;
                        \Log::info('PDF agregado al merge: ' . $pdfInfo['name'] . ' - Tamaño: ' . $fileSize . ' bytes');
                    } else {
                        \Log::warning('PDF vacío ignorado: ' . $pdfInfo['name']);
                        $pdfsFailed[] = $pdfInfo['name'] . ' (archivo vacío)';
                    }
                } else {
                    \Log::warning('PDF no encontrado: ' . $pdfInfo['name'] . ' - Path: ' . $pdfInfo['path']);
                    $pdfsFailed[] = $pdfInfo['name'] . ' (no encontrado)';
                }
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'compression technique') !== false || strpos($errorMsg, 'FPDI') !== false) {
                    \Log::warning('PDF con formato no compatible ignorado: ' . $pdfInfo['name']);
                } else {
                    \Log::warning('Error al agregar PDF al merge: ' . $pdfInfo['name'] . ' - ' . substr($errorMsg, 0, 100));
                }
                $pdfsFailed[] = $pdfInfo['name'] . ' (error: ' . substr($errorMsg, 0, 100) . ')';
                continue;
            }
        }
        
        if ($pdfsAdded === 0) {
            \Log::error('No se agregó ningún PDF para unificar');
            // Limpiar PDFs temporales
            foreach ($allPdfsToMerge as $pdfInfo) {
                if (isset($pdfInfo['is_temp']) && $pdfInfo['is_temp'] && File::exists($pdfInfo['path'])) {
                    File::delete($pdfInfo['path']);
                }
            }
            return redirect()->route('imports.index')->with('error', 'No se pudieron procesar los PDFs para unificar.');
        }
        
        try {
            // Generar el PDF unificado
            \Log::info('Iniciando merge de todos los PDFs. Total de archivos a unificar: ' . $pdfsAdded);
            
            // Intentar mergear todos los PDFs de una vez
            try {
                $mergedPdf = $merger->merge();
            } catch (\Exception $mergeError) {
                // Si el merge falla, intentar mergear PDFs de uno en uno para identificar y omitir los problemáticos
                \Log::warning('Merge inicial falló, intentando mergear PDFs individualmente: ' . $mergeError->getMessage());
                return $this->mergeAllPdfsIndividually($allPdfsToMerge, $tempDir, $timestamp);
            }
            
            if (empty($mergedPdf) || strlen($mergedPdf) === 0) {
                throw new \Exception('El merge no generó contenido.');
            }
            
            \Log::info('Merge completado exitosamente. Tamaño del PDF unificado: ' . strlen($mergedPdf) . ' bytes');
            
            // Guardar el PDF unificado temporalmente
            $mergedPdfPath = $tempDir . '/merged_all_reports_' . $timestamp . '.pdf';
            $bytesWritten = file_put_contents($mergedPdfPath, $mergedPdf);
            
            if ($bytesWritten === false || !File::exists($mergedPdfPath) || filesize($mergedPdfPath) === 0) {
                throw new \Exception('Error al guardar el PDF unificado.');
            }
            
            // Limpiar PDFs temporales (solo los que son temporales, no los almacenados)
            foreach ($allPdfsToMerge as $pdfInfo) {
                if (isset($pdfInfo['is_temp']) && $pdfInfo['is_temp'] && File::exists($pdfInfo['path'])) {
                    File::delete($pdfInfo['path']);
                }
            }
            
            $filename = 'Informe-General-Todos-los-DO-' . date('Y-m-d') . '.pdf';
            
            \Log::info('Informe general generado exitosamente. Archivo: ' . $filename);
            
            // Descargar el PDF unificado
            return response()->download($mergedPdfPath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error al unificar todos los PDFs: ' . $e->getMessage(), [
                'pdfs_added' => $pdfsAdded,
                'pdfs_failed' => $pdfsFailed,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Si el error es de compresión, intentar mergear PDFs individualmente como último recurso
            $errorMessage = $e->getMessage();
            $isCompressionError = (strpos($errorMessage, 'compression technique') !== false || 
                                  strpos($errorMessage, 'FPDI') !== false ||
                                  strpos($errorMessage, 'parser') !== false);
            
            if ($isCompressionError && $pdfsAdded > 0) {
                \Log::warning('Error de compresión detectado, intentando merge individual como fallback');
                return $this->mergeAllPdfsIndividually($allPdfsToMerge, $tempDir, $timestamp);
            }
            
            // Limpiar PDFs temporales en caso de error
            foreach ($allPdfsToMerge as $pdfInfo) {
                if (isset($pdfInfo['is_temp']) && $pdfInfo['is_temp'] && File::exists($pdfInfo['path'])) {
                    File::delete($pdfInfo['path']);
                }
            }
            
            return redirect()->route('imports.index')->with('error', 'Error al generar el PDF unificado: ' . $e->getMessage());
        }
    }
    
    /**
     * Mergear todos los PDFs de uno en uno para identificar y omitir PDFs problemáticos
     * Similar a mergePdfsIndividually pero para múltiples importaciones
     */
    private function mergeAllPdfsIndividually($allPdfsToMerge, $tempDir, $timestamp)
    {
        \Log::info('Iniciando merge individual de todos los PDFs para identificar problemas');
        
        if (empty($allPdfsToMerge)) {
            \Log::error('No hay PDFs para mergear individualmente');
            return redirect()->route('imports.index')->with('error', 'No hay PDFs para unificar.');
        }
        
        // El primer PDF será el base
        $currentMergedPath = null;
        $pdfsSuccessfullyAdded = 0;
        $pdfsSkipped = [];
        
        // Mergear cada PDF de uno en uno
        foreach ($allPdfsToMerge as $pdfInfo) {
            try {
                if (!File::exists($pdfInfo['path']) || filesize($pdfInfo['path']) === 0) {
                    $pdfsSkipped[] = $pdfInfo['name'] . ' (no encontrado o vacío)';
                    continue;
                }
                
                if ($currentMergedPath === null) {
                    // Primer PDF: simplemente usarlo como base
                    $currentMergedPath = $pdfInfo['path'];
                    $pdfsSuccessfullyAdded++;
                    \Log::info('Primer PDF establecido como base: ' . $pdfInfo['name']);
                } else {
                    // Crear un nuevo merger con el PDF actualmente mergeado + el nuevo PDF
                    $tempMerger = new Merger();
                    $tempMerger->addFile($currentMergedPath);
                    $tempMerger->addFile($pdfInfo['path']);
                    
                    // Intentar mergear
                    $tempMerged = $tempMerger->merge();
                    
                    if (empty($tempMerged)) {
                        throw new \Exception('Merge vacío');
                    }
                    
                    // Guardar el resultado temporal
                    $newMergedPath = $tempDir . '/temp_merged_all_' . $timestamp . '_' . $pdfsSuccessfullyAdded . '.pdf';
                    file_put_contents($newMergedPath, $tempMerged);
                    
                    // Si el merge anterior era un archivo temporal, eliminarlo
                    if ($currentMergedPath !== $allPdfsToMerge[0]['path'] && 
                        strpos($currentMergedPath, $tempDir) !== false &&
                        File::exists($currentMergedPath)) {
                        File::delete($currentMergedPath);
                    }
                    
                    $currentMergedPath = $newMergedPath;
                    $pdfsSuccessfullyAdded++;
                    \Log::info('PDF agregado exitosamente (merge individual): ' . $pdfInfo['name']);
                }
                
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, 'compression technique') !== false || strpos($errorMsg, 'FPDI') !== false) {
                    \Log::warning('PDF omitido (compresión no soportada): ' . $pdfInfo['name']);
                } else {
                    \Log::warning('PDF omitido (error): ' . $pdfInfo['name'] . ' - ' . substr($errorMsg, 0, 100));
                }
                $pdfsSkipped[] = $pdfInfo['name'];
                continue;
            }
        }
        
        if ($pdfsSuccessfullyAdded === 0) {
            \Log::error('No se pudo agregar ningún PDF con merge individual');
            // Limpiar PDFs temporales
            foreach ($allPdfsToMerge as $pdfInfo) {
                if (isset($pdfInfo['is_temp']) && $pdfInfo['is_temp'] && File::exists($pdfInfo['path'])) {
                    File::delete($pdfInfo['path']);
                }
            }
            return redirect()->route('imports.index')->with('error', 'No se pudieron procesar los PDFs para unificar.');
        }
        
        // Renombrar el archivo final
        $finalMergedPath = $tempDir . '/merged_all_reports_' . $timestamp . '.pdf';
        if ($currentMergedPath !== $finalMergedPath) {
            if (File::exists($finalMergedPath)) {
                File::delete($finalMergedPath);
            }
            File::move($currentMergedPath, $finalMergedPath);
        }
        
        \Log::info('Merge individual completado. PDFs agregados: ' . $pdfsSuccessfullyAdded . ', PDFs omitidos: ' . count($pdfsSkipped));
        if (!empty($pdfsSkipped)) {
            \Log::info('PDFs omitidos: ' . implode(', ', $pdfsSkipped));
        }
        
        // Limpiar PDFs temporales individuales (solo los que son temporales)
        foreach ($allPdfsToMerge as $pdfInfo) {
            if (isset($pdfInfo['is_temp']) && $pdfInfo['is_temp'] && File::exists($pdfInfo['path']) && $pdfInfo['path'] !== $finalMergedPath) {
                File::delete($pdfInfo['path']);
            }
        }
        
        // Limpiar archivos temporales intermedios
        $tempFiles = glob($tempDir . '/temp_merged_all_' . $timestamp . '_*.pdf');
        foreach ($tempFiles as $tempFile) {
            if (File::exists($tempFile) && $tempFile !== $finalMergedPath) {
                File::delete($tempFile);
            }
        }
        
        $filename = 'Informe-General-Todos-los-DO-' . date('Y-m-d') . '.pdf';
        
        if (!empty($pdfsSkipped)) {
            \Log::warning('Algunos PDFs no pudieron ser incluidos en el informe general debido a formato no compatible');
        }
        
        return response()->download($finalMergedPath, $filename)->deleteFileAfterSend(true);
    }

    // FUNCIONARIO: Show imports assigned to them
    public function funcionarioIndex()
    {
        if (!in_array(Auth::user()->rol, ['funcionario', 'admin'])) {
            abort(403);
        }
        $imports = Import::with(['user', 'containers'])->orderByDesc('created_at')->get();
        
        // Update status based on dates
        $this->updateImportStatuses($imports);
        
        // Reload to get updated statuses
        $imports = Import::with(['user', 'containers'])->orderByDesc('created_at')->get();
        
        return view('imports.funcionario-index', compact('imports'));
    }

    // FUNCIONARIO/ADMIN: Update actual arrival date and mark as received
    public function updateArrival(Request $request, $id)
    {
        if (!in_array(Auth::user()->rol, ['funcionario', 'admin'])) {
            abort(403);
        }
        
        $import = Import::findOrFail($id);
        
        $data = $request->validate([
            'actual_arrival_date' => 'required|date',
        ]);
        
        $import->update([
            'actual_arrival_date' => $data['actual_arrival_date'],
            'received_at' => now(),
            'status' => 'recibido',
        ]);
        
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Fecha de llegada actualizada y marcada como recibido.'
            ]);
        }
        
        return redirect()->back()->with('success', 'Fecha de llegada actualizada y marcada como recibido.');
    }

    /**
     * Clear omitted PDFs info from session
     * Called via AJAX after user acknowledges the message
     */
    public function clearOmittedInfo()
    {
        session()->forget('pdfs_omitted_info');
        return response()->json(['success' => true]);
    }

    /**
     * Update import statuses based on dates
     * Changes status to 'completed' when arrival date has passed
     */
    private function updateImportStatuses($imports)
    {
        $today = now()->startOfDay();
        
        foreach ($imports as $import) {
            if ($import->arrival_date && $import->status === 'pending') {
                $arrivalDate = \Carbon\Carbon::parse($import->arrival_date)->startOfDay();
                
                // If arrival date has passed, change status to completed
                if ($today->greaterThanOrEqualTo($arrivalDate)) {
                    $import->status = 'completed';
                    $import->save();
                }
            }
        }
    }
}
