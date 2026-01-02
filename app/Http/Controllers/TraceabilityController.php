<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Container;
use App\Models\TransferOrder;
use App\Models\Salida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TraceabilityController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::orderBy('nombre')->get();
        $selectedProductId = $request->get('product_id');
        $selectedWarehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $ID_PABLO_ROJAS = 1;
        
        // Admin y funcionario ven todas las bodegas
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // No filtrar, ver todos los productos
            if (!$selectedWarehouseId) {
                $products = Product::orderBy('nombre')->get();
            } else {
                $products = Product::where('almacen_id', $selectedWarehouseId)->orderBy('nombre')->get();
            }
        } elseif ($user->rol !== 'admin' && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
            // Filtrar productos solo de la bodega del usuario
            $products = Product::where('almacen_id', $user->almacen_id)->orderBy('nombre')->get();
        }
        
        $movements = collect();
        
        // Obtener entradas desde contenedores
        $containers = Container::with('products')->get();
        foreach ($containers as $container) {
            foreach ($container->products as $product) {
                // Filtrar por producto si está seleccionado
                if ($selectedProductId && $product->id != $selectedProductId) {
                    continue;
                }
                
                $totalSheets = $product->pivot->boxes * $product->pivot->sheets_per_box;
                
                // Obtener la bodega del producto
                $productModel = Product::find($product->id);
                if (!$productModel) continue;
                
                // Filtrar por almacén si está seleccionado
                if ($selectedWarehouseId && $productModel->almacen_id != $selectedWarehouseId) {
                    continue;
                }
                
                // Filtrar por fecha si está seleccionada
                // Usar la fecha del pivot si existe, sino usar fecha actual
                $containerDate = $product->pivot->created_at ?? now();
                if ($dateFrom) {
                    $dateFromObj = is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom;
                    if ($containerDate < $dateFromObj) continue;
                }
                if ($dateTo) {
                    $dateToObj = is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo;
                    if ($containerDate > $dateToObj) continue;
                }
                
                $movements->push([
                    'date' => $containerDate,
                    'type' => 'entrada',
                    'type_label' => 'Entrada',
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'product_code' => $product->codigo,
                    'product_medidas' => $product->medidas ?? '-',
                    'quantity' => $totalSheets,
                    'warehouse_id' => $productModel->almacen_id,
                    'warehouse_name' => $productModel->almacen->nombre ?? '-',
                    'reference' => $container->reference,
                    'reference_type' => 'Contenedor',
                    'note' => $container->note,
                    'boxes' => $product->pivot->boxes,
                    'sheets_per_box' => $product->pivot->sheets_per_box,
                ]);
            }
        }
        
        // Obtener salidas y entradas desde transferencias
        $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])->get();
        foreach ($transferOrders as $transfer) {
            foreach ($transfer->products as $product) {
                // Filtrar por producto si está seleccionado
                if ($selectedProductId && $product->id != $selectedProductId) {
                    continue;
                }
                
                // Calcular cantidad en unidades
                $quantity = $product->pivot->quantity;
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $quantity = $product->pivot->quantity * $product->unidades_por_caja;
                }
                
                // Filtrar por fecha si está seleccionada
                $transferDate = $transfer->date ?? now();
                $dateFromObj = $dateFrom ? (is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom) : null;
                $dateToObj = $dateTo ? (is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo) : null;
                
                // Salida desde almacén origen (siempre se registra cuando se crea la transferencia)
                if (!$dateFromObj || $transferDate >= $dateFromObj) {
                    if (!$dateToObj || $transferDate <= $dateToObj) {
                        // Filtrar por bodega si está seleccionado
                        if (!$selectedWarehouseId || $transfer->warehouse_from_id == $selectedWarehouseId) {
                            $movements->push([
                                'date' => $transferDate,
                                'type' => 'salida',
                                'type_label' => 'Salida',
                                'product_id' => $product->id,
                                'product_name' => $product->nombre,
                                'product_code' => $product->codigo,
                                'product_medidas' => $product->medidas ?? '-',
                                'quantity' => -$quantity, // Negativo para salida
                                'warehouse_id' => $transfer->warehouse_from_id,
                                'warehouse_name' => $transfer->from->nombre ?? '-',
                                'reference' => $transfer->order_number,
                                'reference_type' => 'Transferencia',
                                'note' => $transfer->note,
                                'destination_warehouse' => $transfer->to->nombre ?? '-',
                                'pivot_quantity' => $product->pivot->quantity,
                                'boxes' => $product->tipo_medida === 'caja' ? $product->pivot->quantity : null,
                                'driver_name' => $transfer->driver->name ?? null,
                                'driver_identity' => $transfer->driver->identity ?? null,
                                'driver_vehicle_plate' => $transfer->driver->vehicle_plate ?? null,
                            ]);
                        }
                    }
                }
                
                // Entrada al almacén destino (solo si la transferencia fue recibida)
                if ($transfer->status === 'recibido') {
                    // Usar updated_at como fecha de recepción (cuando cambió a recibido)
                    $receivedDate = $transfer->updated_at ?? now();
                    
                    if (!$dateFromObj || $receivedDate >= $dateFromObj) {
                        if (!$dateToObj || $receivedDate <= $dateToObj) {
                            // Filtrar por bodega si está seleccionado
                            if (!$selectedWarehouseId || $transfer->warehouse_to_id == $selectedWarehouseId) {
                                $movements->push([
                                    'date' => $receivedDate,
                                    'type' => 'entrada',
                                    'type_label' => 'Entrada',
                                    'product_id' => $product->id,
                                    'product_name' => $product->nombre,
                                    'product_code' => $product->codigo,
                                    'product_medidas' => $product->medidas ?? '-',
                                    'quantity' => $quantity, // Positivo para entrada
                                    'warehouse_id' => $transfer->warehouse_to_id,
                                    'warehouse_name' => $transfer->to->nombre ?? '-',
                                    'reference' => $transfer->order_number,
                                    'reference_type' => 'Transferencia',
                                    'note' => $transfer->note,
                                    'destination_warehouse' => $transfer->from->nombre ?? '-', // Origen de la transferencia
                                    'pivot_quantity' => $product->pivot->quantity,
                                    'boxes' => $product->tipo_medida === 'caja' ? $product->pivot->quantity : null,
                                    'driver_name' => $transfer->driver->name ?? null,
                                    'driver_identity' => $transfer->driver->identity ?? null,
                                    'driver_vehicle_plate' => $transfer->driver->vehicle_plate ?? null,
                                ]);
                            }
                        }
                    }
                }
            }
        }
        
        // Obtener salidas del módulo Salidas
        $salidas = Salida::with(['warehouse', 'products'])->get();
        foreach ($salidas as $salida) {
            foreach ($salida->products as $product) {
                // Filtrar por producto si está seleccionado
                if ($selectedProductId && $product->id != $selectedProductId) {
                    continue;
                }
                
                // Filtrar por bodega si está seleccionado
                if ($selectedWarehouseId && $salida->warehouse_id != $selectedWarehouseId) {
                    continue;
                }
                
                // Filtrar por fecha si está seleccionada
                $salidaDate = $salida->fecha ?? now();
                if ($dateFrom) {
                    $dateFromObj = is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom;
                    if ($salidaDate < $dateFromObj) continue;
                }
                if ($dateTo) {
                    $dateToObj = is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo;
                    if ($salidaDate > $dateToObj) continue;
                }
                
                // La cantidad en salidas siempre es en láminas
                $quantity = $product->pivot->quantity;
                
                // Calcular cajas equivalentes si es tipo caja
                $cajasEquivalentes = null;
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $cajasEquivalentes = floor($quantity / $product->unidades_por_caja);
                }
                
                $movements->push([
                    'date' => $salidaDate,
                    'type' => 'salida',
                    'type_label' => 'Salida',
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'product_code' => $product->codigo,
                    'product_medidas' => $product->medidas ?? '-',
                    'quantity' => -$quantity, // Negativo para salida
                    'warehouse_id' => $salida->warehouse_id,
                    'warehouse_name' => $salida->warehouse->nombre ?? '-',
                    'reference' => $salida->salida_number,
                    'reference_type' => 'Salida',
                    'note' => $salida->note ?? ($salida->a_nombre_de ? "A nombre de: {$salida->a_nombre_de}" : '-'),
                    'destination_warehouse' => $salida->a_nombre_de ?? '-',
                    'boxes' => $cajasEquivalentes,
                ]);
            }
        }
        
        // Ordenar por fecha descendente
        $movements = $movements->sortByDesc('date')->values();
        
        // Paginación manual (ya que es una colección, no un query builder)
        $perPage = 10;
        $currentPage = (int) $request->get('page', 1);
        $total = $movements->count();
        $items = $movements->slice(($currentPage - 1) * $perPage, $perPage)->values();
        
        // Crear paginador manual
        $movements = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        
        // Asegurar que el paginador tenga los métodos necesarios
        $movements->setPath($request->url());
        
        return view('traceability.index', compact(
            'warehouses', 
            'products', 
            'movements', 
            'selectedProductId', 
            'selectedWarehouseId',
            'dateFrom',
            'dateTo'
        ));
    }

    private function getTraceabilityData(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::orderBy('nombre')->get();
        $selectedProductId = $request->get('product_id');
        $selectedWarehouseId = $request->get('warehouse_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        
        $ID_PABLO_ROJAS = 1;
        
        // Admin y funcionario ven todas las bodegas
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // No filtrar, ver todos los productos
            if (!$selectedWarehouseId) {
                $products = Product::orderBy('nombre')->get();
            } else {
                $products = Product::where('almacen_id', $selectedWarehouseId)->orderBy('nombre')->get();
            }
        } elseif ($user->rol !== 'admin' && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
            // Filtrar productos solo de la bodega del usuario
            $products = Product::where('almacen_id', $user->almacen_id)->orderBy('nombre')->get();
        }
        
        $movements = collect();
        
        // Obtener entradas desde contenedores
        $containers = Container::with('products')->get();
        foreach ($containers as $container) {
            foreach ($container->products as $product) {
                if ($selectedProductId && $product->id != $selectedProductId) continue;
                
                $totalSheets = $product->pivot->boxes * $product->pivot->sheets_per_box;
                $productModel = Product::find($product->id);
                if (!$productModel) continue;
                
                if ($selectedWarehouseId && $productModel->almacen_id != $selectedWarehouseId) continue;
                
                // Usar la fecha del pivot si existe, sino usar fecha actual
                $containerDate = $product->pivot->created_at ?? now();
                if ($dateFrom) {
                    $dateFromObj = is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom;
                    if ($containerDate < $dateFromObj) continue;
                }
                if ($dateTo) {
                    $dateToObj = is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo;
                    if ($containerDate > $dateToObj) continue;
                }
                
                $movements->push([
                    'date' => $containerDate,
                    'type' => 'entrada',
                    'type_label' => 'Entrada',
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'product_code' => $product->codigo,
                    'product_medidas' => $product->medidas ?? '-',
                    'quantity' => $totalSheets,
                    'warehouse_id' => $productModel->almacen_id,
                    'warehouse_name' => $productModel->almacen->nombre ?? '-',
                    'reference' => $container->reference,
                    'reference_type' => 'Contenedor',
                    'note' => $container->note,
                    'boxes' => $product->pivot->boxes,
                    'sheets_per_box' => $product->pivot->sheets_per_box,
                ]);
            }
        }
        
        // Obtener salidas y entradas desde transferencias
        $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])->get();
        foreach ($transferOrders as $transfer) {
            foreach ($transfer->products as $product) {
                if ($selectedProductId && $product->id != $selectedProductId) continue;
                
                $quantity = $product->pivot->quantity;
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $quantity = $product->pivot->quantity * $product->unidades_por_caja;
                }
                
                $transferDate = $transfer->date ?? now();
                $dateFromObj = $dateFrom ? (is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom) : null;
                $dateToObj = $dateTo ? (is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo) : null;
                
                // Salida desde almacén origen (siempre se registra cuando se crea la transferencia)
                if (!$dateFromObj || $transferDate >= $dateFromObj) {
                    if (!$dateToObj || $transferDate <= $dateToObj) {
                        if (!$selectedWarehouseId || $transfer->warehouse_from_id == $selectedWarehouseId) {
                            $movements->push([
                                'date' => $transferDate,
                                'type' => 'salida',
                                'type_label' => 'Salida',
                                'product_id' => $product->id,
                                'product_name' => $product->nombre,
                                'product_code' => $product->codigo,
                                'product_medidas' => $product->medidas ?? '-',
                                'quantity' => -$quantity,
                                'warehouse_id' => $transfer->warehouse_from_id,
                                'warehouse_name' => $transfer->from->nombre ?? '-',
                                'reference' => $transfer->order_number,
                                'reference_type' => 'Transferencia',
                                'note' => $transfer->note,
                                'destination_warehouse' => $transfer->to->nombre ?? '-',
                                'pivot_quantity' => $product->pivot->quantity,
                                'boxes' => $product->tipo_medida === 'caja' ? $product->pivot->quantity : null,
                                'driver_name' => $transfer->driver->name ?? null,
                                'driver_identity' => $transfer->driver->identity ?? null,
                                'driver_vehicle_plate' => $transfer->driver->vehicle_plate ?? null,
                            ]);
                        }
                    }
                }
                
                // Entrada al almacén destino (solo si la transferencia fue recibida)
                if ($transfer->status === 'recibido') {
                    $receivedDate = $transfer->updated_at ?? now();
                    
                    if (!$dateFromObj || $receivedDate >= $dateFromObj) {
                        if (!$dateToObj || $receivedDate <= $dateToObj) {
                            if (!$selectedWarehouseId || $transfer->warehouse_to_id == $selectedWarehouseId) {
                                $movements->push([
                                    'date' => $receivedDate,
                                    'type' => 'entrada',
                                    'type_label' => 'Entrada',
                                    'product_id' => $product->id,
                                    'product_name' => $product->nombre,
                                    'product_code' => $product->codigo,
                                    'product_medidas' => $product->medidas ?? '-',
                                    'quantity' => $quantity,
                                    'warehouse_id' => $transfer->warehouse_to_id,
                                    'warehouse_name' => $transfer->to->nombre ?? '-',
                                    'reference' => $transfer->order_number,
                                    'reference_type' => 'Transferencia',
                                    'note' => $transfer->note,
                                    'destination_warehouse' => $transfer->from->nombre ?? '-',
                                    'pivot_quantity' => $product->pivot->quantity,
                                    'boxes' => $product->tipo_medida === 'caja' ? $product->pivot->quantity : null,
                                    'driver_name' => $transfer->driver->name ?? null,
                                    'driver_identity' => $transfer->driver->identity ?? null,
                                    'driver_vehicle_plate' => $transfer->driver->vehicle_plate ?? null,
                                ]);
                            }
                        }
                    }
                }
            }
        }
        
        // Obtener salidas del módulo Salidas
        $salidas = Salida::with(['warehouse', 'products'])->get();
        foreach ($salidas as $salida) {
            foreach ($salida->products as $product) {
                if ($selectedProductId && $product->id != $selectedProductId) continue;
                
                if ($selectedWarehouseId && $salida->warehouse_id != $selectedWarehouseId) continue;
                
                $salidaDate = $salida->fecha ?? now();
                if ($dateFrom) {
                    $dateFromObj = is_string($dateFrom) ? \Carbon\Carbon::parse($dateFrom) : $dateFrom;
                    if ($salidaDate < $dateFromObj) continue;
                }
                if ($dateTo) {
                    $dateToObj = is_string($dateTo) ? \Carbon\Carbon::parse($dateTo)->endOfDay() : $dateTo;
                    if ($salidaDate > $dateToObj) continue;
                }
                
                $quantity = $product->pivot->quantity;
                
                $cajasEquivalentes = null;
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $cajasEquivalentes = floor($quantity / $product->unidades_por_caja);
                }
                
                $movements->push([
                    'date' => $salidaDate,
                    'type' => 'salida',
                    'type_label' => 'Salida',
                    'product_id' => $product->id,
                    'product_name' => $product->nombre,
                    'product_code' => $product->codigo,
                    'product_medidas' => $product->medidas ?? '-',
                    'quantity' => -$quantity,
                    'warehouse_id' => $salida->warehouse_id,
                    'warehouse_name' => $salida->warehouse->nombre ?? '-',
                    'reference' => $salida->salida_number,
                    'reference_type' => 'Salida',
                    'note' => $salida->note ?? ($salida->a_nombre_de ? "A nombre de: {$salida->a_nombre_de}" : '-'),
                    'destination_warehouse' => $salida->a_nombre_de ?? '-',
                    'boxes' => $cajasEquivalentes,
                ]);
            }
        }
        
        $movements = $movements->sortByDesc('date')->values();
        
        return compact('warehouses', 'products', 'movements', 'selectedProductId', 'selectedWarehouseId', 'dateFrom', 'dateTo');
    }

    public function exportPdf(Request $request)
    {
        // Solo admin puede descargar PDF/Excel
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('traceability.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }
        
        $data = $this->getTraceabilityData($request);
        extract($data);
        
        $isExport = true;
        $pdf = Pdf::loadView('traceability.pdf', compact('movements', 'selectedProductId', 'selectedWarehouseId', 'dateFrom', 'dateTo', 'warehouses', 'products', 'isExport'));
        
        // Configurar orientación landscape y tamaño de papel
        $pdf->setPaper('a4', 'landscape');
        $pdf->setOption('margin-top', 10);
        $pdf->setOption('margin-bottom', 10);
        $pdf->setOption('margin-left', 8);
        $pdf->setOption('margin-right', 8);
        
        $productName = $selectedProductId 
            ? $products->where('id', $selectedProductId)->first()->nombre ?? 'Todos' 
            : 'Todos';
        $filename = 'Trazabilidad-' . str_replace(' ', '-', $productName) . '-' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportExcel(Request $request)
    {
        // Solo admin puede descargar PDF/Excel
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('traceability.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }
        
        $data = $this->getTraceabilityData($request);
        extract($data);
        
        $productName = $selectedProductId 
            ? $products->where('id', $selectedProductId)->first()->nombre ?? 'Todos' 
            : 'Todos';
        $filename = 'Trazabilidad-' . str_replace(' ', '-', $productName) . '-' . date('Y-m-d') . '.xls';
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        .info { margin-bottom: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        th { background-color: #0066cc; color: white; font-weight: bold; padding: 8px; border: 1px solid #000; text-align: center; }
        td { padding: 6px; border: 1px solid #000; }
        .entrada { background-color: #d4edda; }
        .salida { background-color: #f8d7da; }
        .quantity-positive { color: #28a745; font-weight: bold; }
        .quantity-negative { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">TRAZABILIDAD DE PRODUCTOS</div>
    <div class="info">Fecha: ' . date('d/m/Y H:i') . '</div>';
        
        if ($selectedProductId) {
            $productName = $products->where('id', $selectedProductId)->first()->nombre ?? '';
            $html .= '<div class="info">Producto: ' . htmlspecialchars($productName) . '</div>';
        } else {
            $html .= '<div class="info">Producto: Todos los productos</div>';
        }
        
        if ($selectedWarehouseId) {
            $warehouseName = $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '';
            $html .= '<div class="info">Bodega: ' . htmlspecialchars($warehouseName) . '</div>';
        } else {
            $html .= '<div class="info">Bodega: Todas las bodegas</div>';
        }
        
        if ($dateFrom || $dateTo) {
            $dateRange = ($dateFrom ? date('d/m/Y', strtotime($dateFrom)) : 'Inicio') . ' - ' . ($dateTo ? date('d/m/Y', strtotime($dateTo)) : 'Fin');
            $html .= '<div class="info">Rango de fechas: ' . htmlspecialchars($dateRange) . '</div>';
        }
        
        $html .= '
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Código</th>
                <th>Medidas</th>
                <th>Cajas</th>
                <th>Cantidad</th>
                <th>Bodega</th>
                <th>Referencia</th>
                <th>Tipo Referencia</th>
                <th>Destino</th>
                <th>Conductor</th>
                <th>Cédula</th>
                <th>Placa</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>';
        
        foreach ($movements as $movement) {
            $rowClass = $movement['type'] === 'entrada' ? 'entrada' : 'salida';
            $quantityClass = $movement['quantity'] > 0 ? 'quantity-positive' : 'quantity-negative';
            $quantityDisplay = $movement['quantity'] > 0 ? '+' . number_format($movement['quantity'], 0) : number_format($movement['quantity'], 0);
            
            $html .= '<tr class="' . $rowClass . '">
                <td>' . htmlspecialchars($movement['date']->format('d/m/Y H:i')) . '</td>
                <td>' . htmlspecialchars($movement['type_label']) . '</td>
                <td>' . htmlspecialchars($movement['product_name']) . '</td>
                <td>' . htmlspecialchars($movement['product_code']) . '</td>
                <td>' . htmlspecialchars($movement['product_medidas'] ?? '-') . '</td>
                <td style="text-align: center;">' . (isset($movement['boxes']) && $movement['boxes'] !== null ? number_format($movement['boxes'], 0) : '-') . '</td>
                <td class="' . $quantityClass . '">' . $quantityDisplay . '</td>
                <td>' . htmlspecialchars($movement['warehouse_name']) . '</td>
                <td>' . htmlspecialchars($movement['reference']) . '</td>
                <td>' . htmlspecialchars($movement['reference_type']) . '</td>
                <td>' . htmlspecialchars($movement['destination_warehouse'] ?? '-') . '</td>
                <td>' . htmlspecialchars($movement['driver_name'] ?? '-') . '</td>
                <td>' . htmlspecialchars($movement['driver_identity'] ?? '-') . '</td>
                <td>' . htmlspecialchars($movement['driver_vehicle_plate'] ?? '-') . '</td>
                <td>' . htmlspecialchars($movement['note'] ?? '-') . '</td>
            </tr>';
        }
        
        $html .= '
        </tbody>
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
