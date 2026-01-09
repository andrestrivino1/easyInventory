<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Import;
use App\Models\ImportContainer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

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
            'product_name' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date',
            'containers' => 'required|array|min:1',
            'containers.*.reference' => 'required|string|max:255',
            'containers.*.pdf' => 'nullable|file|mimes:pdf',
            'containers.*.images' => 'nullable|array',
            'containers.*.images.*' => 'image|mimes:jpeg,png,jpg',
            'proforma_pdf' => 'nullable|file|mimes:pdf',
            'invoice_pdf' => 'nullable|file|mimes:pdf',
            'bl_pdf' => 'nullable|file|mimes:pdf',
            'etd' => 'nullable|string|max:255',
            'shipping_company' => 'nullable|string|max:255',
            'free_days_at_dest' => 'nullable|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'credit_time' => 'required|in:15,30,45',
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

        $import = Import::create([
            'user_id' => Auth::id(),
            'product_name' => $data['product_name'],
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
            'etd' => $data['etd'] ?? null,
            'shipping_company' => $data['shipping_company'] ?? null,
            'free_days_at_dest' => $data['free_days_at_dest'] ?? null,
            'supplier' => $data['supplier'] ?? null,
            'credit_time' => $data['credit_time'],
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

                $images = [];
                if ($request->hasFile("containers.{$index}.images")) {
                    foreach ($request->file("containers.{$index}.images") as $image) {
                        if ($image && $image->isValid()) {
                            $images[] = $image->store('imports');
                        }
                    }
                }

                \App\Models\ImportContainer::create([
                    'import_id' => $import->id,
                    'reference' => $containerData['reference'],
                    'pdf_path' => $pdfPath,
                    'images' => !empty($images) ? $images : null,
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
            'product_name' => 'required|string|max:255',
            'origin' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'arrival_date' => 'required|date',
            'containers' => 'required|array|min:1',
            'containers.*.id' => 'nullable|exists:import_containers,id',
            'containers.*.reference' => 'required|string|max:255',
            'containers.*.pdf' => 'nullable|file|mimes:pdf',
            'containers.*.images' => 'nullable|array',
            'containers.*.images.*' => 'image|mimes:jpeg,png,jpg',
            'proforma_pdf' => 'nullable|file|mimes:pdf',
            'proforma_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'invoice_pdf' => 'nullable|file|mimes:pdf',
            'commercial_invoice_low_pdf' => 'nullable|file|mimes:pdf',
            'bl_pdf' => 'nullable|file|mimes:pdf',
            'packing_list_pdf' => 'nullable|file|mimes:pdf',
            'apostillamiento_pdf' => 'nullable|file|mimes:pdf',
            'etd' => 'nullable|string|max:255',
            'shipping_company' => 'nullable|string|max:255',
            'free_days_at_dest' => 'nullable|integer|min:0',
            'supplier' => 'nullable|string|max:255',
            'credit_time' => 'required|in:15,30,45',
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

        // Calculate credits
        $credits = null;
        if (isset($data['credit_time'])) {
            $credits = floatval($data['credit_time']);
        }
        $data['destination'] = 'Colombia'; // Always Colombia
        $data['credits'] = $credits;
        
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

                $images = [];
                if ($request->hasFile("containers.{$index}.images")) {
                    foreach ($request->file("containers.{$index}.images") as $image) {
                        if ($image && $image->isValid()) {
                            $images[] = $image->store('imports');
                        }
                    }
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
                        if (!empty($images)) {
                            $existingImages = $container->images ?? [];
                            $container->images = array_merge($existingImages, $images);
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
                        'images' => !empty($images) ? $images : null,
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
        } elseif(strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if(count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if($container) {
                    if($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif($parts[2] === 'image' && count($parts) >= 4) {
                        $imageIndex = intval($parts[3]);
                        $images = $container->images;
                        if($images && isset($images[$imageIndex])) {
                            $filePath = $images[$imageIndex];
                        }
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
        } elseif(strpos($fileType, 'container_') === 0) {
            // Handle container files: container_{containerId}_pdf or container_{containerId}_image_{imageIndex}
            $parts = explode('_', $fileType);
            if(count($parts) >= 3) {
                $containerId = $parts[1];
                $container = $import->containers->find($containerId);
                if($container) {
                    if($parts[2] === 'pdf' && $container->pdf_path) {
                        $filePath = $container->pdf_path;
                    } elseif($parts[2] === 'image' && count($parts) >= 4) {
                        $imageIndex = intval($parts[3]);
                        $images = $container->images;
                        if($images && isset($images[$imageIndex])) {
                            $filePath = $images[$imageIndex];
                        }
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
     * Generate PDF report for an import
     */
    public function report($id)
    {
        if (Auth::user()->rol !== 'admin') {
            abort(403);
        }
        
        $import = Import::with(['user', 'containers'])->findOrFail($id);
        
        $isExport = true;
        $currentUser = Auth::user();
        
        $pdf = Pdf::loadView('imports.report', compact('import', 'isExport', 'currentUser'));
        $filename = 'Importacion-' . $import->do_code . '-' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
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
