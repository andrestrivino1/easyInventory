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
    public function index(Request $request)
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }

        $query = Import::with(['user', 'containers']);

        // Filtro de búsqueda (texto)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro de fechas (fecha de llegada)
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }

        // Filtro de tiempo de crédito
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Update status based on dates
        $this->updateImportStatuses($imports);

        // Reload to get updated statuses
        $query = Import::with(['user', 'containers']);

        // Aplicar los mismos filtros después de actualizar estados
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Filtro de porcentaje de progreso (aplicar después de calcular)
        $progressMinValue = $request->input('progress_min');
        if ($progressMinValue !== null && $progressMinValue !== '') {
            $progressMin = (float) $progressMinValue;

            if ($progressMin >= 0 && $progressMin <= 100) {
                $imports = $imports->filter(function ($import) use ($progressMin) {
                    $progress = $this->calculateProgress($import);
                    // Redondear el progreso igual que en la vista para comparar
                    $progressRounded = round($progress);
                    // Solo incluir las que tienen exactamente el porcentaje buscado
                    return $progressRounded == $progressMin;
                })->values(); // Reindexar la colección
            }
        }

        // Ordenar: pendientes por % descendente, luego completadas
        $imports = $this->sortImportsByProgress($imports);

        // Paginación manual (porque aplicamos filtros después de obtener los datos)
        $perPage = 10;
        $currentPage = (int) $request->get('page', 1);
        $total = $imports->count();

        if ($total > $perPage) {
            $items = $imports->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $imports = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $imports->setPath($request->url());
        }

        return view('imports.index', compact('imports'));
    }

    // IMPORT_VIEWER: Show all imports (read-only)
    public function viewerIndex()
    {
        if (Auth::user()->rol !== 'import_viewer') {
            abort(403);
        }
        $imports = Import::with(['user', 'containers'])->get();

        // Update status based on dates
        $this->updateImportStatuses($imports);

        // Reload to get updated statuses
        $imports = Import::with(['user', 'containers'])->get();

        // Ordenar: pendientes por % descendente, luego completadas
        $imports = $this->sortImportsByProgress($imports);

        // Paginación manual
        $perPage = 10;
        $currentPage = (int) request()->get('page', 1);
        $total = $imports->count();

        if ($total > $perPage) {
            $items = $imports->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $imports = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
            $imports->setPath(request()->url());
        }

        return view('imports.viewer-index', compact('imports'));
    }

    // PROVIDER: Show their imports
    public function providerIndex()
    {
        if (!in_array(Auth::user()->rol, ['importer', 'admin'])) {
            abort(403);
        }
        $imports = Import::with('containers')->where('user_id', Auth::id())->get();

        // Update status based on dates
        $this->updateImportStatuses($imports);

        // Reload to get updated statuses
        $imports = Import::with('containers')->where('user_id', Auth::id())->get();

        // Ordenar: pendientes por % descendente, luego completadas
        $imports = $this->sortImportsByProgress($imports);

        // Paginación manual
        $perPage = 10;
        $currentPage = (int) request()->get('page', 1);
        $total = $imports->count();

        if ($total > $perPage) {
            $items = $imports->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $imports = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]
            );
            $imports->setPath(request()->url());
        }

        return view('imports.provider-index', compact('imports'));
    }

    // PROVIDER: Form to create an import
    public function create()
    {
        if (!in_array(Auth::user()->rol, ['importer', 'admin'])) {
            abort(403);
        }

        $providers = [];
        if (Auth::user()->rol === 'admin') {
            $providers = User::where('rol', 'importer')->get();
        }

        return view('imports.create', compact('providers'));
    }

    // PROVIDER: Store new import
    public function store(Request $request)
    {
        if (!in_array(Auth::user()->rol, ['importer', 'admin'])) {
            abort(403);
        }
        $data = $request->validate([
            'commercial_invoice_number' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'departure_date' => 'nullable|date',
            'arrival_date' => 'nullable|date',
            'proforma_invoice_number' => 'nullable|string|max:255',
            'bl_number' => 'nullable|string|max:255',
            'containers' => 'nullable|array',
            'containers.*.reference' => 'nullable|string|max:255',
            'containers.*.pdf' => 'nullable|file|mimes:pdf',
            'containers.*.image_pdf' => 'nullable|file|mimes:pdf',
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
            'provider_id' => 'nullable|exists:users,id',
        ]);

        $proformaPdfPath = null;
        if ($request->hasFile('proforma_pdf')) {
            $proformaPdfPath = $request->file('proforma_pdf')->store('imports');
        }

        $invoicePdfPath = null;
        if ($request->hasFile('invoice_pdf')) {
            $invoicePdfPath = $request->file('invoice_pdf')->store('imports');
        }

        $blPdfPath = null;
        if ($request->hasFile('bl_pdf')) {
            $blPdfPath = $request->file('bl_pdf')->store('imports');
        }

        $proformaInvoiceLowPdfPath = null;
        if ($request->hasFile('proforma_invoice_low_pdf')) {
            $proformaInvoiceLowPdfPath = $request->file('proforma_invoice_low_pdf')->store('imports');
        }

        $commercialInvoiceLowPdfPath = null;
        if ($request->hasFile('commercial_invoice_low_pdf')) {
            $commercialInvoiceLowPdfPath = $request->file('commercial_invoice_low_pdf')->store('imports');
        }

        $packingListPdfPath = null;
        if ($request->hasFile('packing_list_pdf')) {
            $packingListPdfPath = $request->file('packing_list_pdf')->store('imports');
        }

        $apostillamientoPdfPath = null;
        if ($request->hasFile('apostillamiento_pdf')) {
            $apostillamientoPdfPath = $request->file('apostillamiento_pdf')->store('imports');
        }

        $otherDocumentsPdfPath = null;
        if ($request->hasFile('other_documents_pdf')) {
            $otherDocumentsPdfPath = $request->file('other_documents_pdf')->store('imports');
        }

        // DO code calculation based on arrival_date or current year
        $arrivalDate = $data['arrival_date'] ?? null;
        if ($arrivalDate) {
            $year = date('y', strtotime($arrivalDate));
        } else {
            $year = date('y'); // Usar año actual si no hay fecha de llegada
        }

        $lastImport = Import::whereRaw('SUBSTRING(do_code, 4, 2) = ?', [$year])
            ->where(function ($query) use ($year, $arrivalDate) {
                if ($arrivalDate) {
                    $query->whereYear('arrival_date', '20' . $year);
                } else {
                    $query->whereYear('created_at', '20' . $year);
                }
            })
            ->orderByDesc('do_code')
            ->first();
        $next = 1;
        if ($lastImport && preg_match('/VJP' . $year . '-(\d{3})/', $lastImport->do_code, $m)) {
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

        // Determine user_id
        $userId = Auth::id();
        if (Auth::user()->rol === 'admin' && !empty($data['provider_id'])) {
            $userId = $data['provider_id'];
        }

        $import = Import::create([
            'user_id' => $userId,
            'commercial_invoice_number' => $data['commercial_invoice_number'] ?? null,
            'proforma_invoice_number' => $data['proforma_invoice_number'] ?? null,
            'bl_number' => $data['bl_number'] ?? null,
            'origin' => $data['origin'] ?? null,
            'destination' => 'Colombia', // Always Colombia
            'departure_date' => $data['departure_date'] ?? null,
            'arrival_date' => $data['arrival_date'] ?? null,
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

        // Handle multiple containers (solo si tienen referencia)
        if ($request->has('containers') && is_array($request->containers)) {
            foreach ($request->containers as $index => $containerData) {
                // Solo crear contenedor si tiene referencia
                if (!empty($containerData['reference'])) {
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
            'commercial_invoice_number' => 'nullable|string|max:255',
            'origin' => 'nullable|string|max:255',
            'departure_date' => 'nullable|date',
            'arrival_date' => 'nullable|date',
            'proforma_invoice_number' => 'nullable|string|max:255',
            'bl_number' => 'nullable|string|max:255',
            'containers' => 'nullable|array',
            'containers.*.id' => 'nullable|exists:import_containers,id',
            'containers.*.reference' => 'nullable|string|max:255',
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
        if ($request->hasFile('proforma_pdf')) {
            if ($import->proforma_pdf) {
                Storage::delete($import->proforma_pdf);
            }
            $data['proforma_pdf'] = $request->file('proforma_pdf')->store('imports');
        }

        if ($request->hasFile('invoice_pdf')) {
            if ($import->invoice_pdf) {
                Storage::delete($import->invoice_pdf);
            }
            $data['invoice_pdf'] = $request->file('invoice_pdf')->store('imports');
        }

        if ($request->hasFile('bl_pdf')) {
            if ($import->bl_pdf) {
                Storage::delete($import->bl_pdf);
            }
            $data['bl_pdf'] = $request->file('bl_pdf')->store('imports');
        }

        if ($request->hasFile('proforma_invoice_low_pdf')) {
            if ($import->proforma_invoice_low_pdf) {
                Storage::delete($import->proforma_invoice_low_pdf);
            }
            $data['proforma_invoice_low_pdf'] = $request->file('proforma_invoice_low_pdf')->store('imports');
        }

        if ($request->hasFile('commercial_invoice_low_pdf')) {
            if ($import->commercial_invoice_low_pdf) {
                Storage::delete($import->commercial_invoice_low_pdf);
            }
            $data['commercial_invoice_low_pdf'] = $request->file('commercial_invoice_low_pdf')->store('imports');
        }

        if ($request->hasFile('packing_list_pdf')) {
            if ($import->packing_list_pdf) {
                Storage::delete($import->packing_list_pdf);
            }
            $data['packing_list_pdf'] = $request->file('packing_list_pdf')->store('imports');
        }

        if ($request->hasFile('apostillamiento_pdf')) {
            if ($import->apostillamiento_pdf) {
                Storage::delete($import->apostillamiento_pdf);
            }
            $data['apostillamiento_pdf'] = $request->file('apostillamiento_pdf')->store('imports');
        }

        if ($request->hasFile('other_documents_pdf')) {
            if ($import->other_documents_pdf) {
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

        // Handle containers (solo si tienen referencia)
        $existingContainerIds = [];
        if ($request->has('containers') && is_array($request->containers)) {
            foreach ($request->containers as $index => $containerData) {
                // Solo procesar contenedor si tiene referencia
                if (empty($containerData['reference'])) {
                    continue;
                }

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

    // ADMIN/PROVIDER/IMPORT_VIEWER/FUNCIONARIO: View files
    public function viewFile($id, $fileType)
    {
        $import = Import::with('containers')->findOrFail($id);
        // Only admin, funcionario, owner, or import_viewer can view
        if (!in_array(Auth::user()->rol, ['admin', 'funcionario', 'import_viewer']) && $import->user_id !== Auth::id()) {
            abort(403);
        }

        $filePath = null;
        if ($fileType === 'proforma_pdf' && $import->proforma_pdf) {
            $filePath = $import->proforma_pdf;
        } elseif ($fileType === 'proforma_invoice_low_pdf' && $import->proforma_invoice_low_pdf) {
            $filePath = $import->proforma_invoice_low_pdf;
        } elseif ($fileType === 'invoice_pdf' && $import->invoice_pdf) {
            $filePath = $import->invoice_pdf;
        } elseif ($fileType === 'commercial_invoice_low_pdf' && $import->commercial_invoice_low_pdf) {
            $filePath = $import->commercial_invoice_low_pdf;
        } elseif ($fileType === 'packing_list_pdf' && $import->packing_list_pdf) {
            $filePath = $import->packing_list_pdf;
        } elseif ($fileType === 'bl_pdf' && $import->bl_pdf) {
            $filePath = $import->bl_pdf;
        } elseif ($fileType === 'apostillamiento_pdf' && $import->apostillamiento_pdf) {
            $filePath = $import->apostillamiento_pdf;
        } elseif ($fileType === 'other_documents_pdf' && $import->other_documents_pdf) {
            $filePath = $import->other_documents_pdf;
        } elseif (strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if (count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if ($container) {
                    if ($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif ($parts[2] === 'image' && $parts[3] === 'pdf' && $container->image_pdf_path) {
                        $filePath = $container->image_pdf_path;
                    }
                }
            }
        }

        if (!$filePath || !Storage::exists($filePath)) {
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

    // ADMIN/PROVIDER/FUNCIONARIO: Download files
    public function downloadFile($id, $fileType)
    {
        $import = Import::with('containers')->findOrFail($id);
        // Only admin, funcionario, or owner can download
        if (!in_array(Auth::user()->rol, ['admin', 'funcionario']) && $import->user_id !== Auth::id()) {
            abort(403);
        }

        $filePath = null;
        if ($fileType === 'proforma_pdf' && $import->proforma_pdf) {
            $filePath = $import->proforma_pdf;
        } elseif ($fileType === 'proforma_invoice_low_pdf' && $import->proforma_invoice_low_pdf) {
            $filePath = $import->proforma_invoice_low_pdf;
        } elseif ($fileType === 'invoice_pdf' && $import->invoice_pdf) {
            $filePath = $import->invoice_pdf;
        } elseif ($fileType === 'commercial_invoice_low_pdf' && $import->commercial_invoice_low_pdf) {
            $filePath = $import->commercial_invoice_low_pdf;
        } elseif ($fileType === 'packing_list_pdf' && $import->packing_list_pdf) {
            $filePath = $import->packing_list_pdf;
        } elseif ($fileType === 'bl_pdf' && $import->bl_pdf) {
            $filePath = $import->bl_pdf;
        } elseif ($fileType === 'apostillamiento_pdf' && $import->apostillamiento_pdf) {
            $filePath = $import->apostillamiento_pdf;
        } elseif ($fileType === 'other_documents_pdf' && $import->other_documents_pdf) {
            $filePath = $import->other_documents_pdf;
        } elseif (strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if (count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if ($container) {
                    if ($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif ($parts[2] === 'image' && $parts[3] === 'pdf' && $container->image_pdf_path) {
                        $filePath = $container->image_pdf_path;
                    }
                }
            }
        }

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404);
        }

        return Storage::download($filePath);
    }

    /**
     * Generate PDF report for an import with all uploaded PDFs unified
     */
    public function report($id)
    {
        // Permitir acceso a admin, funcionario e import_viewer
        if (!in_array(Auth::user()->rol, ['admin', 'funcionario', 'import_viewer'])) {
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
    /**
     * Export imports to Excel
     */
    public function exportExcel(Request $request)
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }

        // Aplicar los mismos filtros que en index()
        $query = Import::with(['user', 'containers']);

        // Filtro de búsqueda (texto)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro de fechas (fecha de llegada)
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }

        // Filtro de tiempo de crédito
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Update status based on dates
        $this->updateImportStatuses($imports);

        // Reload to get updated statuses
        $query = Import::with(['user', 'containers']);

        // Aplicar los mismos filtros después de actualizar estados
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Filtro de porcentaje de progreso (aplicar después de calcular)
        $progressMinValue = $request->input('progress_min');
        if ($progressMinValue !== null && $progressMinValue !== '') {
            $progressMin = (float) $progressMinValue;

            if ($progressMin >= 0 && $progressMin <= 100) {
                $imports = $imports->filter(function ($import) use ($progressMin) {
                    $progress = $this->calculateProgress($import);
                    $progressRounded = round($progress);
                    return $progressRounded == $progressMin;
                })->values();
            }
        }

        // Ordenar: pendientes por % descendente, luego completadas
        $imports = $this->sortImportsByProgress($imports);

        $filename = 'Importaciones-' . date('Y-m-d') . '.xls';

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        .info { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; margin-bottom: 20px; }
        th { background-color: #0066cc; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; }
        td { padding: 6px; border: 1px solid #000; }
    </style>
</head>
<body>
    <div class="header">INFORME DE IMPORTACIONES</div>
    <div class="info">Fecha: ' . date('d/m/Y H:i') . '</div>
    <table>
        <thead>
            <tr>
                <th>DO</th>
                <th>Usuario</th>
                <th>N° Comercial Invoice</th>
                <th>N° Proforma Invoice</th>
                <th>N° Bill of Lading</th>
                <th>Origen</th>
                <th>Destino</th>
                <th>Fecha Salida</th>
                <th>Fecha Llegada</th>
                <th>Fecha Creación</th>
                <th>Naviera/Agencia</th>
                <th>Días Libres Destino</th>
                <th>Estado</th>
                <th>Créditos</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($imports as $import) {
            $departureDate = $import->departure_date ? \Carbon\Carbon::parse($import->departure_date) : null;
            $arrivalDate = $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date) : null;
            $progress = $this->calculateProgress($import);
            $progressRounded = round($progress);

            // Determinar estado
            $statusText = '';
            if ($import->status === 'pending') {
                $statusText = 'Pendiente';
            } elseif ($import->status === 'completed') {
                $statusText = 'Completado';
            } elseif ($import->status === 'in_transit') {
                $statusText = 'En tránsito';
            } elseif ($import->status === 'recibido') {
                $statusText = 'Recibido';
            } elseif ($import->status === 'pendiente_por_confirmar') {
                $statusText = 'Pendiente por confirmar';
            } else {
                $statusText = ucfirst($import->status);
            }

            $html .= '<tr>
                <td>' . htmlspecialchars($import->do_code) . '</td>
                <td>' . htmlspecialchars($import->user->nombre_completo ?? $import->user->email) . '</td>
                <td>' . htmlspecialchars($import->commercial_invoice_number ?? '-') . '</td>
                <td>' . htmlspecialchars($import->proforma_invoice_number ?? '-') . '</td>
                <td>' . htmlspecialchars($import->bl_number ?? '-') . '</td>
                <td>' . htmlspecialchars($import->origin ?? '-') . '</td>
                <td>' . htmlspecialchars($import->destination ?? 'Colombia') . '</td>
                <td>' . ($departureDate ? $departureDate->format('d/m/Y') : '-') . '</td>
                <td>' . ($arrivalDate ? $arrivalDate->format('d/m/Y') : '-') . '</td>
                <td>' . ($import->created_at ? $import->created_at->format('d/m/Y H:i') : '-') . '</td>
                <td>' . htmlspecialchars($import->shipping_company ?? '-') . '</td>
                <td>' . ($import->free_days_at_dest ?? '-') . '</td>
                <td>' . htmlspecialchars($statusText) . ' (' . $progressRounded . '%)</td>
                <td>' . ($import->credit_time ? $import->credit_time . ' días' : 'Sin crédito') . '</td>
            </tr>';
        }

        $html .= '</tbody>
    </table>
</body>
</html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response($html, 200, $headers);
    }

    /**
     * Calculate progress percentage for an import
     */
    private function calculateProgress($import)
    {
        if (!$import->departure_date || !$import->arrival_date) {
            return 0;
        }

        $departureDate = \Carbon\Carbon::parse($import->departure_date);
        $arrivalDate = \Carbon\Carbon::parse($import->arrival_date);
        $today = \Carbon\Carbon::now();

        $totalDays = $departureDate->diffInDays($arrivalDate);

        if ($totalDays <= 0) {
            return 0;
        }

        $elapsedDays = $departureDate->diffInDays($today);

        if ($elapsedDays < 0) {
            return 0;
        } elseif ($elapsedDays >= $totalDays) {
            return 100;
        } else {
            return ($elapsedDays / $totalDays) * 100;
        }
    }

    /**
     * Sort imports: pending by progress % descending, then completed by arrival date descending
     */
    private function sortImportsByProgress($imports)
    {
        // Separar importaciones: las que faltan por llegar primero, luego las completadas
        $pendingImports = $imports->filter(function ($import) {
            return $import->status !== 'completed';
        })->values();

        $completedImports = $imports->filter(function ($import) {
            return $import->status === 'completed';
        })->values();

        // Ordenar las que faltan por llegar: por porcentaje de progreso descendente (las más cercanas a completarse primero)
        $pendingImports = $pendingImports->sortByDesc(function ($import) {
            return $this->calculateProgress($import);
        })->values();

        // Ordenar las completadas: por fecha de llegada (más recientes primero)
        $completedImports = $completedImports->sortByDesc(function ($import) {
            // Usar fecha real de llegada si existe, sino fecha estimada, sino fecha de creación
            if ($import->actual_arrival_date) {
                return \Carbon\Carbon::parse($import->actual_arrival_date)->timestamp;
            } elseif ($import->arrival_date) {
                return \Carbon\Carbon::parse($import->arrival_date)->timestamp;
            } else {
                return $import->created_at->timestamp;
            }
        })->values();

        // Combinar: primero las que faltan por llegar (ordenadas por % descendente), luego las completadas
        return $pendingImports->merge($completedImports)->values();
    }

    public function exportAllReports()
    {
        try {
            if (Auth::user()->rol !== 'admin') {
                abort(403);
            }

            // Obtener todas las importaciones con sus relaciones
            $imports = Import::with(['user', 'containers'])->get();

            if ($imports->isEmpty()) {
                return redirect()->route('imports.index')->with('error', 'No hay importaciones para generar el informe.');
            }

            // Ordenar: pendientes por % descendente, luego completadas
            $imports = $this->sortImportsByProgress($imports);

            \Log::info('Iniciando generación de informe general. Total de importaciones: ' . $imports->count());

            // Generar el PDF del informe general con todas las importaciones en formato tabla
            $pdf = Pdf::loadView('imports.general-report', compact('imports'));

            $filename = 'Informe-General-Todos-los-DO-' . date('Y-m-d') . '.pdf';

            \Log::info('Informe general generado exitosamente. Archivo: ' . $filename);

            // Descargar el PDF
            return $pdf->download($filename);

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
                    if (
                        $currentMergedPath !== $allPdfsToMerge[0]['path'] &&
                        strpos($currentMergedPath, $tempDir) !== false &&
                        File::exists($currentMergedPath)
                    ) {
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
    public function funcionarioIndex(Request $request)
    {
        if (!in_array(Auth::user()->rol, ['funcionario', 'admin'])) {
            abort(403);
        }

        $query = Import::with(['user', 'containers']);

        // Filtro de búsqueda (texto)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }

        // Filtro de fechas (fecha de llegada)
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }

        // Filtro de tiempo de crédito
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Update status based on dates
        $this->updateImportStatuses($imports);

        // Reload to get updated statuses
        $query = Import::with(['user', 'containers']);

        // Aplicar los mismos filtros después de actualizar estados
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('do_code', 'like', "%{$search}%")
                    ->orWhere('commercial_invoice_number', 'like', "%{$search}%")
                    ->orWhere('proforma_invoice_number', 'like', "%{$search}%")
                    ->orWhere('bl_number', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('nombre_completo', 'like', "%{$search}%");
                    });
            });
        }
        if ($request->filled('date_from')) {
            $query->where('arrival_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('arrival_date', '<=', $request->date_to);
        }
        if ($request->filled('credit_time')) {
            if ($request->credit_time === 'sin_credito') {
                $query->whereNull('credit_time');
            } else {
                $query->where('credit_time', $request->credit_time);
            }
        }

        $imports = $query->get();

        // Filtro de porcentaje de progreso (aplicar después de calcular)
        $progressMinValue = $request->input('progress_min');
        if ($progressMinValue !== null && $progressMinValue !== '') {
            $progressMin = (float) $progressMinValue;

            if ($progressMin >= 0 && $progressMin <= 100) {
                $imports = $imports->filter(function ($import) use ($progressMin) {
                    $progress = $this->calculateProgress($import);
                    $progressRounded = round($progress);
                    return $progressRounded == $progressMin;
                })->values();
            }
        }

        // Ordenar: pendientes por % descendente, luego completadas
        $imports = $this->sortImportsByProgress($imports);

        // Paginación manual
        $perPage = 10;
        $currentPage = (int) $request->get('page', 1);
        $total = $imports->count();

        if ($total > $perPage) {
            $items = $imports->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $imports = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
            $imports->setPath($request->url());
        }

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

    // FUNCIONARIO/ADMIN: Update estimated arrival date when import is delayed
    public function updateEstimatedArrival(Request $request, $id)
    {
        if (!in_array(Auth::user()->rol, ['funcionario', 'admin'])) {
            abort(403);
        }

        $import = Import::findOrFail($id);

        $data = $request->validate([
            'arrival_date' => 'required|date',
        ]);

        // Actualizar la fecha estimada y cambiar el estado a pending
        // También limpiar received_at si existe, ya que si se retrasa, no debería estar marcado como recibido
        $import->update([
            'arrival_date' => $data['arrival_date'],
            'status' => 'pending',
            'received_at' => null,
            'actual_arrival_date' => null, // Limpiar también la fecha real si existe
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Fecha de llegada estimada actualizada. La importación ha vuelto al estado pendiente.'
            ]);
        }

        return redirect()->back()->with('success', 'Fecha de llegada estimada actualizada. La importación ha vuelto al estado pendiente.');
    }

    // ADMIN: Mark import as nationalized
    public function markAsNationalized($id)
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }

        $import = Import::findOrFail($id);

        // Verificar que esté completado y en 100%
        $progress = $this->calculateProgress($import);
        if ($import->status !== 'completed' || $progress < 100) {
            return redirect()->back()->with('error', 'Solo se pueden nacionalizar importaciones completadas al 100%.');
        }

        // Al nacionalizar, asegurar que el estado sea 'completed' (ya que nacionalizada significa que llegó)
        $import->update([
            'nationalized' => true,
            'status' => 'completed', // Asegurar que esté como completado
        ]);

        return redirect()->back()->with('success', 'Importación marcada como nacionalizada.');
    }

    // ADMIN: Mark credit as paid
    public function markCreditAsPaid($id)
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }

        $import = Import::findOrFail($id);

        $import->update([
            'credit_paid' => true,
        ]);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Crédito marcado como pagado.'
            ]);
        }

        return redirect()->back()->with('success', 'Crédito marcado como pagado.');
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
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Solo admin puede eliminar importaciones
        if (Auth::user()->rol !== 'admin') {
            return redirect()->route('imports.index')->with('error', 'No tienes permiso para eliminar importaciones.');
        }

        $import = Import::with('containers')->findOrFail($id);

        try {
            // Eliminar archivos PDF de la importación
            $pdfFields = [
                'proforma_pdf',
                'proforma_invoice_low_pdf',
                'invoice_pdf',
                'commercial_invoice_low_pdf',
                'bl_pdf',
                'packing_list_pdf',
                'apostillamiento_pdf',
                'other_documents_pdf'
            ];

            foreach ($pdfFields as $field) {
                if ($import->$field) {
                    Storage::delete($import->$field);
                }
            }

            // Eliminar archivos PDF de los contenedores
            foreach ($import->containers as $container) {
                if ($container->pdf_path) {
                    Storage::delete($container->pdf_path);
                }
                if ($container->image_pdf_path) {
                    Storage::delete($container->image_pdf_path);
                }
            }

            // Eliminar la importación (esto eliminará automáticamente los contenedores por cascade delete)
            $import->delete();

            return redirect()->route('imports.index')->with('success', 'Importación eliminada correctamente.');

        } catch (\Exception $e) {
            \Log::error('IMPORT destroy - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'import_id' => $import->id ?? null
            ]);
            return back()->with('error', 'Error al eliminar la importación: ' . $e->getMessage());
        }
    }

    private function updateImportStatuses($imports)
    {
        $today = now()->startOfDay();

        foreach ($imports as $import) {
            if ($import->arrival_date) {
                $arrivalDate = \Carbon\Carbon::parse($import->arrival_date)->startOfDay();

                // Si la fecha estimada ha llegado o pasado
                if ($today->greaterThanOrEqualTo($arrivalDate)) {
                    // Si no ha sido confirmado el recibido, cambiar a "pendiente_por_confirmar"
                    if (!$import->received_at) {
                        if (in_array($import->status, ['pending', 'completed'])) {
                            $import->status = 'pendiente_por_confirmar';
                            $import->save();
                        }
                    }
                }
            }
        }
    }
}
