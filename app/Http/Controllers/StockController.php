<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Container;
use App\Models\TransferOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $selectedWarehouseId = $request->get('warehouse_id');
        $ID_BUENAVENTURA = 1;
        
        // Si el usuario no es admin ni secretaria, filtrar automáticamente por su almacén
        // Funcionario solo ve Buenaventura
        if ($user->rol === 'funcionario') {
            $selectedWarehouseId = $ID_BUENAVENTURA;
        } elseif (!in_array($user->rol, ['admin', 'secretaria']) && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
        }
        
        // Obtener productos
        if ($selectedWarehouseId) {
            $products = Product::where('almacen_id', $selectedWarehouseId)
                ->with('almacen')
                ->orderBy('nombre')
                ->get();
        } else {
            $products = Product::with('almacen')->orderBy('nombre')->get();
        }
        
        // Obtener contenedores (solo Buenaventura tiene contenedores)
        if ($selectedWarehouseId) {
            // Si se selecciona Buenaventura, mostrar sus contenedores
            if ($selectedWarehouseId == $ID_BUENAVENTURA) {
                $containers = Container::with('products')->orderByDesc('id')->get();
            } else {
                // Si se selecciona otro almacén, no mostrar contenedores
                $containers = collect();
            }
        } else {
            // Si no hay filtro, mostrar todos los contenedores (solo Buenaventura tiene)
            $containers = Container::with('products')->orderByDesc('id')->get();
        }
        
        // Obtener transferencias
        if ($selectedWarehouseId) {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->where(function($query) use ($selectedWarehouseId) {
                    $query->where('warehouse_from_id', $selectedWarehouseId)
                          ->orWhere('warehouse_to_id', $selectedWarehouseId);
                })
                ->orderByDesc('date')
                ->get();
        } else {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->orderByDesc('date')
                ->get();
        }
        
        return view('stock.index', compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'ID_BUENAVENTURA'));
    }

    private function getStockData(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $selectedWarehouseId = $request->get('warehouse_id');
        $ID_BUENAVENTURA = 1;
        
        // Si el usuario no es admin ni secretaria, filtrar automáticamente por su almacén
        // Funcionario solo ve Buenaventura
        if ($user->rol === 'funcionario') {
            $selectedWarehouseId = $ID_BUENAVENTURA;
        } elseif (!in_array($user->rol, ['admin', 'secretaria']) && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
        }
        
        // Obtener productos
        if ($selectedWarehouseId) {
            $products = Product::where('almacen_id', $selectedWarehouseId)
                ->with('almacen')
                ->orderBy('nombre')
                ->get();
        } else {
            $products = Product::with('almacen')->orderBy('nombre')->get();
        }
        
        // Obtener contenedores
        if ($selectedWarehouseId) {
            if ($selectedWarehouseId == $ID_BUENAVENTURA) {
                $containers = Container::with('products')->orderByDesc('id')->get();
            } else {
                $containers = collect();
            }
        } else {
            $containers = Container::with('products')->orderByDesc('id')->get();
        }
        
        // Obtener transferencias
        if ($selectedWarehouseId) {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->where(function($query) use ($selectedWarehouseId) {
                    $query->where('warehouse_from_id', $selectedWarehouseId)
                          ->orWhere('warehouse_to_id', $selectedWarehouseId);
                })
                ->orderByDesc('date')
                ->get();
        } else {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->orderByDesc('date')
                ->get();
        }
        
        return compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'ID_BUENAVENTURA');
    }

    public function exportPdf(Request $request)
    {
        // Solo admin puede descargar PDF/Excel
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }
        
        $data = $this->getStockData($request);
        extract($data);
        
        $isExport = true;
        $pdf = Pdf::loadView('stock.pdf', compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'ID_BUENAVENTURA', 'isExport'));
        
        $warehouseName = $selectedWarehouseId 
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos' 
            : 'Todos';
        $filename = 'Inventario-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        // Solo admin puede descargar PDF/Excel
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }
        
        $data = $this->getStockData($request);
        extract($data);
        
        $warehouseName = $selectedWarehouseId 
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos' 
            : 'Todos';
        $filename = 'Inventario-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.xls';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        .info { margin-bottom: 5px; }
        .section-title { font-size: 13px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; color: #0066cc; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; margin-bottom: 20px; }
        th { background-color: #0066cc; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; }
        td { padding: 6px; border: 1px solid #000; }
    </style>
</head>
<body>
    <div class="header">INVENTARIO DE STOCK</div>
    <div class="info">Fecha: ' . date('d/m/Y H:i') . '</div>';
        
        if ($selectedWarehouseId) {
            $warehouseName = $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '';
            $html .= '<div class="info">Almacén: ' . htmlspecialchars($warehouseName) . '</div>';
        } else {
            $html .= '<div class="info">Almacén: Todos los almacenes</div>';
        }
        
        // Sección de Productos
        $html .= '<div class="section-title">SECCIÓN: PRODUCTOS</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Almacén</th>
                    <th>Láminas</th>
                    <th>Tipo Medida</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($products as $product) {
            $html .= '<tr>
                <td>' . htmlspecialchars($product->codigo) . '</td>
                <td>' . htmlspecialchars($product->nombre) . '</td>
                <td>' . htmlspecialchars($product->almacen->nombre ?? '-') . '</td>
                <td>' . number_format($product->stock, 0) . '</td>
                <td>' . htmlspecialchars(ucfirst($product->tipo_medida)) . '</td>
                <td>' . ($product->estado ? 'Activo' : 'Inactivo') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
        // Sección de Contenedores
        if ($selectedWarehouseId == $ID_BUENAVENTURA || !$selectedWarehouseId) {
            $html .= '<div class="section-title">SECCIÓN: CONTENEDORES</div>
            <table>
                <thead>
                    <tr>
                        <th>Referencia</th>
                        <th>Productos</th>
                        <th>Total Cajas</th>
                        <th>Total Láminas</th>
                        <th>Observación</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($containers as $container) {
                $totalBoxes = 0;
                $totalSheets = 0;
                $productNames = [];
                foreach($container->products as $product) {
                    $totalBoxes += $product->pivot->boxes;
                    $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                    $productNames[] = $product->nombre . ' (' . $product->pivot->boxes . ' cajas × ' . $product->pivot->sheets_per_box . ' láminas)';
                }
                $html .= '<tr>
                    <td>' . htmlspecialchars($container->reference) . '</td>
                    <td>' . htmlspecialchars(implode(' | ', $productNames)) . '</td>
                    <td>' . number_format($totalBoxes, 0) . '</td>
                    <td>' . number_format($totalSheets, 0) . '</td>
                    <td>' . htmlspecialchars($container->note ?? '-') . '</td>
                </tr>';
            }
            
            $html .= '</tbody>
            </table>';
        }
        
        // Sección de Transferencias
        $html .= '<div class="section-title">SECCIÓN: TRANSFERENCIAS</div>
        <table>
            <thead>
                <tr>
                    <th>No. Orden</th>
                    <th>Origen</th>
                    <th>Destino</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Productos</th>
                    <th>Conductor</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($transferOrders as $transfer) {
            $productNames = [];
            foreach($transfer->products as $prod) {
                $productNames[] = $prod->nombre . ' (' . $prod->pivot->quantity . ' ' . ($prod->tipo_medida === 'caja' ? 'cajas' : 'unidades') . ')';
            }
            $html .= '<tr>
                <td>' . htmlspecialchars($transfer->order_number) . '</td>
                <td>' . htmlspecialchars($transfer->from->nombre ?? '-') . '</td>
                <td>' . htmlspecialchars($transfer->to->nombre ?? '-') . '</td>
                <td>' . htmlspecialchars(ucfirst($transfer->status)) . '</td>
                <td>' . htmlspecialchars($transfer->date->format('d/m/Y H:i')) . '</td>
                <td>' . htmlspecialchars(implode(' | ', $productNames)) . '</td>
                <td>' . htmlspecialchars($transfer->driver->name ?? '-') . '</td>
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
}
