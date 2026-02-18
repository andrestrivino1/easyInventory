<?php

namespace App\Http\Controllers;

use App\Models\Itr;
use App\Models\ItrDateHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ItrController extends Controller
{
    /**
     * Listado de ITRs. Admin y funcionario ven todos; proveedor_itr solo ve este módulo (mismo listado).
     */
    public function index()
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'funcionario', 'proveedor_itr'])) {
            abort(403, 'No tiene permiso para acceder al módulo ITR.');
        }

        $itrs = Itr::with(['import', 'dateHistories'])->orderByDesc('fecha_llegada')->paginate(15);

        return view('itrs.index', compact('itrs'));
    }

    /**
     * Actualizar una fecha (retiro, vaciado o devolución) y registrar historial.
     */
    public function updateDate(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'funcionario', 'proveedor_itr'])) {
            abort(403);
        }

        $itr = Itr::findOrFail($id);

        $field = $request->validate([
            'field' => 'required|in:fecha_retiro_contenedor,fecha_vaciado_contenedor,fecha_devolucion_contenedor',
            'value' => 'required|date',
        ]);

        $fieldName = $field['field'];
        $newValue = $field['value'];
        $oldValue = $itr->$fieldName ? $itr->$fieldName->format('Y-m-d') : null;

        if ($oldValue === $newValue) {
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Sin cambios.']);
            }
            return back()->with('info', 'Sin cambios.');
        }

        $itr->$fieldName = $newValue;
        $itr->save();

        ItrDateHistory::create([
            'itr_id' => $itr->id,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fecha actualizada.',
                'formatted' => \Carbon\Carbon::parse($newValue)->format('d/m/Y'),
            ]);
        }

        return back()->with('success', 'Fecha actualizada.');
    }

    /**
     * Subir evidencia PDF (tiquete retiro, tiquete devolución o fotos).
     */
    public function uploadEvidence(Request $request, $id)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'funcionario', 'proveedor_itr'])) {
            abort(403);
        }

        $itr = Itr::findOrFail($id);

        $data = $request->validate([
            'type' => 'required|in:tiquete_retiro,tiquete_devolucion,fotos',
            'file' => 'required|file|mimes:pdf|max:15360',
        ]);

        $typeToColumn = [
            'tiquete_retiro' => 'evidencia_tiquete_retiro_pdf',
            'tiquete_devolucion' => 'evidencia_tiquete_devolucion_pdf',
            'fotos' => 'evidencia_fotos_pdf',
        ];
        $column = $typeToColumn[$data['type']] ?? null;
        if (!$column) {
            abort(404);
        }

        if ($itr->$column) {
            Storage::disk('public')->delete($itr->$column);
        }

        $path = $request->file('file')->store('itr_evidencias', 'public');
        $itr->$column = $path;
        $itr->save();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Evidencia subida.',
                'url' => route('itrs.download-evidence', ['itr' => $itr->id, 'type' => $data['type']]),
            ]);
        }

        return back()->with('success', 'Evidencia subida.');
    }

    /**
     * Descargar evidencia PDF.
     */
    public function downloadEvidence(Itr $itr, string $type)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'funcionario', 'proveedor_itr'])) {
            abort(403);
        }

        $typeToColumn = [
            'tiquete_retiro' => 'evidencia_tiquete_retiro_pdf',
            'tiquete_devolucion' => 'evidencia_tiquete_devolucion_pdf',
            'fotos' => 'evidencia_fotos_pdf',
        ];
        $column = $typeToColumn[$type] ?? null;
        if (!$column) {
            abort(404);
        }

        $path = $itr->$column;
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $path,
            'ITR-' . $itr->do_code . '-' . str_replace('_', '-', $type) . '.pdf'
        );
    }

    /**
     * Historial de cambios de una fecha (para el icono/modal).
     */
    public function dateHistory($id, string $field)
    {
        $user = Auth::user();
        if (!in_array($user->rol, ['admin', 'funcionario', 'proveedor_itr'])) {
            abort(403);
        }

        if (!in_array($field, ['fecha_retiro_contenedor', 'fecha_vaciado_contenedor', 'fecha_devolucion_contenedor'])) {
            abort(404);
        }

        $histories = ItrDateHistory::where('itr_id', $id)
            ->where('field_name', $field)
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['histories' => $histories]);
    }
}
