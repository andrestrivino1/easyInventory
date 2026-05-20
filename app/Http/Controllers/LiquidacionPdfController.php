<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\Liquidacion;
use Barryvdh\DomPDF\Facade\Pdf;

class LiquidacionPdfController extends Controller
{
    public function download(Liquidacion $liquidacion)
    {
        $this->authorize('downloadPdf', $liquidacion);

        $liquidacion->load(['driver', 'route', 'expenses.category', 'tolls']);
        $categories = ExpenseCategory::ordered()->get();

        $pdf = Pdf::loadView('liquidaciones.pdf', [
            'liq' => $liquidacion,
            'categories' => $categories,
        ])->setPaper('letter', 'portrait');

        $plate = preg_replace('/[^A-Z0-9]/i', '', $liquidacion->vehicle_plate) ?: 'liquidacion';
        $fecha = $liquidacion->fecha_inicio?->format('Ymd') ?? 'sin-fecha';
        $filename = "liquidacion_{$plate}_{$fecha}.pdf";

        return $pdf->download($filename);
    }
}
