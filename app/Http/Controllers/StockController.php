<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Container;
use App\Models\TransferOrder;
use App\Models\Salida;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $selectedWarehouseId = $request->get('warehouse_id');
        $ID_PABLO_ROJAS = 1;
        
        // Si el usuario no es admin ni secretaria, filtrar automáticamente por su bodega
        // Funcionario solo ve Pablo Rojas
        if ($user->rol === 'funcionario') {
            $selectedWarehouseId = $ID_PABLO_ROJAS;
        } elseif (!in_array($user->rol, ['admin', 'secretaria']) && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
        }
        
        // Obtener productos
        if ($selectedWarehouseId) {
            $products = Product::where('almacen_id', $selectedWarehouseId)
                ->with(['almacen', 'containers'])
                ->orderBy('nombre')
                ->get();
        } else {
            $products = Product::with(['almacen', 'containers'])->orderBy('nombre')->get();
        }
        
        // Obtener contenedores (solo Pablo Rojas tiene contenedores)
        if ($selectedWarehouseId) {
            // Si se selecciona Pablo Rojas, mostrar sus contenedores
            if ($selectedWarehouseId == $ID_PABLO_ROJAS) {
                $containers = Container::with('products')->orderByDesc('id')->get();
            } else {
                // Si se selecciona otra bodega, no mostrar contenedores
                $containers = collect();
            }
        } else {
            // Si no hay filtro, mostrar todos los contenedores (solo Pablo Rojas tiene)
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
        
        // Calcular cantidades por contenedor para cada producto
        $productosCantidadesPorContenedor = $this->calcularCantidadesPorContenedor($products);
        
        // Obtener contenedores de origen desde transferencias recibidas para cada producto
        $productosContenedoresOrigen = $this->obtenerContenedoresOrigen($products);
        
        // Obtener salidas
        if ($selectedWarehouseId) {
            $salidas = Salida::with(['warehouse', 'products'])
                ->where('warehouse_id', $selectedWarehouseId)
                ->orderByDesc('fecha')
                ->get();
        } else {
            $salidas = Salida::with(['warehouse', 'products'])
                ->orderByDesc('fecha')
                ->get();
        }
        
        return view('stock.index', compact('warehouses', 'products', 'containers', 'transferOrders', 'salidas', 'selectedWarehouseId', 'ID_PABLO_ROJAS', 'productosCantidadesPorContenedor', 'productosContenedoresOrigen'));
    }

    private function getStockData(Request $request)
    {
        $user = Auth::user();
        $warehouses = Warehouse::orderBy('nombre')->get();
        $selectedWarehouseId = $request->get('warehouse_id');
        $ID_PABLO_ROJAS = 1;
        
        // Si el usuario no es admin ni secretaria, filtrar automáticamente por su bodega
        // Funcionario solo ve Pablo Rojas
        if ($user->rol === 'funcionario') {
            $selectedWarehouseId = $ID_PABLO_ROJAS;
        } elseif (!in_array($user->rol, ['admin', 'secretaria']) && !$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
        }
        
        // Obtener productos
        if ($selectedWarehouseId) {
            $products = Product::where('almacen_id', $selectedWarehouseId)
                ->with(['almacen', 'containers'])
                ->orderBy('nombre')
                ->get();
        } else {
            $products = Product::with(['almacen', 'containers'])->orderBy('nombre')->get();
        }
        
        // Obtener contenedores
        if ($selectedWarehouseId) {
            if ($selectedWarehouseId == $ID_PABLO_ROJAS) {
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
        
        // Refrescar los productos ANTES de calcular las cantidades para obtener los valores actualizados de la base de datos
        foreach ($products as $product) {
            $product->refresh();
            // Recargar relaciones para asegurar que estén actualizadas
            $product->load('almacen', 'containers');
        }
        
        // Calcular cantidades por contenedor para cada producto (después de refrescar)
        $productosCantidadesPorContenedor = $this->calcularCantidadesPorContenedor($products);
        
        // Obtener contenedores de origen desde transferencias recibidas para cada producto
        $productosContenedoresOrigen = $this->obtenerContenedoresOrigen($products);
        
        // Obtener salidas
        if ($selectedWarehouseId) {
            $salidas = Salida::with(['warehouse', 'products'])
                ->where('warehouse_id', $selectedWarehouseId)
                ->orderByDesc('fecha')
                ->get();
        } else {
            $salidas = Salida::with(['warehouse', 'products'])
                ->orderByDesc('fecha')
                ->get();
        }
        
        return compact('warehouses', 'products', 'containers', 'transferOrders', 'salidas', 'selectedWarehouseId', 'ID_PABLO_ROJAS', 'productosCantidadesPorContenedor', 'productosContenedoresOrigen');
    }
    
    /**
     * Calcula las cantidades por contenedor para cada producto basado en:
     * 1. Contenedores relacionados directamente (tabla container_product)
     * 2. Transferencias recibidas
     */
    private function calcularCantidadesPorContenedor($products)
    {
        $resultado = collect();
        
        // Obtener todas las transferencias recibidas de una vez para optimizar
        $allReceivedTransfers = TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id', 'quantity');
            }])
            ->get();
        
        // Obtener todos los contenedores de una vez
        $allContainers = Container::all()->keyBy('id');
        
        // Cargar relaciones de contenedores para todos los productos de una vez
        $products->load('containers');
        
        foreach ($products as $producto) {
            // Agrupar cantidades por contenedor
            $cantidadesPorContenedor = collect();
            
            // 1. Obtener contenedores relacionados directamente (tabla container_product)
            // Leer directamente de la base de datos para obtener los valores actualizados
            $containerProductData = DB::table('container_product')
                ->where('product_id', $producto->id)
                ->get();
            
            foreach ($containerProductData as $cp) {
                $containerId = $cp->container_id;
                $container = $allContainers->get($containerId);
                if ($container) {
                    $boxes = $cp->boxes ?? 0;
                    $sheetsPerBox = $cp->sheets_per_box ?? 0;
                    $laminas = $boxes * $sheetsPerBox;
                    
                    $cantidadesPorContenedor[$containerId] = [
                        'container_reference' => $container->reference,
                        'cajas' => $boxes,
                        'laminas' => $laminas,
                    ];
                }
            }
            
            // 2. Obtener cantidades de transferencias recibidas (solo para bodegas que NO son Pablo Rojas)
            // Para Pablo Rojas, solo mostramos las cajas que quedan en el contenedor (ya descontadas)
            // Para otras bodegas, mostramos las cantidades recibidas por transferencia
            $ID_PABLO_ROJAS = 1;
            if ($producto->almacen_id != $ID_PABLO_ROJAS) {
                $receivedTransfers = $allReceivedTransfers->filter(function($transfer) use ($producto) {
                    if ($transfer->warehouse_to_id != $producto->almacen_id) {
                        return false;
                    }
                    // Buscar por nombre del producto (ya que el ID puede ser diferente)
                    return $transfer->products->contains(function($p) use ($producto) {
                        return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                    });
                });
                
                foreach ($receivedTransfers as $transfer) {
                    // Buscar el producto en la transferencia por nombre y código
                    $productInTransfer = $transfer->products->first(function($p) use ($producto) {
                        return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                    });
                    
                    if ($productInTransfer && $productInTransfer->pivot->container_id) {
                        $containerId = $productInTransfer->pivot->container_id;
                        $quantity = $productInTransfer->pivot->quantity;
                        
                        // Calcular láminas si es tipo caja
                        $laminas = $quantity;
                        if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                            $laminas = $quantity * $producto->unidades_por_caja;
                        }
                        
                        // Agregar o sumar a las cantidades existentes del contenedor
                        if ($cantidadesPorContenedor->has($containerId)) {
                            $cantidadesPorContenedor[$containerId]['cajas'] += $quantity;
                            $cantidadesPorContenedor[$containerId]['laminas'] += $laminas;
                        } else {
                            $container = $allContainers->get($containerId);
                            $cantidadesPorContenedor[$containerId] = [
                                'container_reference' => $container ? $container->reference : 'N/A',
                                'cajas' => $quantity,
                                'laminas' => $laminas,
                            ];
                        }
                    }
                }
            }
            
            $resultado->put($producto->id, $cantidadesPorContenedor);
        }
        
        return $resultado;
    }
    
    /**
     * Obtiene los contenedores de origen desde transferencias recibidas para cada producto
     */
    private function obtenerContenedoresOrigen($products)
    {
        $resultado = collect();
        
        // Obtener todas las transferencias recibidas de una vez para optimizar
        $allReceivedTransfers = TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id');
            }])
            ->get();
        
        foreach ($products as $producto) {
            // Filtrar transferencias recibidas para este producto en este almacén
            $receivedTransfers = $allReceivedTransfers->filter(function($transfer) use ($producto) {
                if ($transfer->warehouse_to_id != $producto->almacen_id) {
                    return false;
                }
                // Buscar por nombre del producto (ya que el ID puede ser diferente)
                return $transfer->products->contains(function($p) use ($producto) {
                    return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                });
            });
            
            // Obtener contenedores de origen desde transferencias recibidas
            $containerIds = collect();
            foreach ($receivedTransfers as $transfer) {
                // Buscar el producto en la transferencia por nombre y código
                $productInTransfer = $transfer->products->first(function($p) use ($producto) {
                    return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                });
                
                if ($productInTransfer && $productInTransfer->pivot->container_id) {
                    $containerIds->push($productInTransfer->pivot->container_id);
                }
            }
            
            // Cargar contenedores únicos
            $containersFromTransfers = collect();
            if ($containerIds->isNotEmpty()) {
                $containers = Container::whereIn('id', $containerIds->unique())->get();
                $containersFromTransfers = $containers;
            }
            
            $resultado->put($producto->id, $containersFromTransfers);
        }
        
        return $resultado;
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
        $pdf = Pdf::loadView('stock.pdf', compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'ID_PABLO_ROJAS', 'isExport'));
        
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
                    <th>Medidas</th>
                    <th>Cajas</th>
                    <th>Láminas</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($products as $product) {
            $cajas = ($product->tipo_medida === 'caja' && $product->cajas !== null) ? number_format($product->cajas, 0) : '-';
            $html .= '<tr>
                <td>' . htmlspecialchars($product->codigo) . '</td>
                <td>' . htmlspecialchars($product->nombre) . '</td>
                <td>' . htmlspecialchars($product->almacen->nombre ?? '-') . '</td>
                <td>' . htmlspecialchars($product->medidas ?? '-') . '</td>
                <td>' . $cajas . '</td>
                <td>' . number_format($product->stock, 0) . '</td>
                <td>' . ($product->estado ? 'Activo' : 'Inactivo') . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
        </table>';
        
        // Sección de Contenedores
        if ($selectedWarehouseId == $ID_PABLO_ROJAS || !$selectedWarehouseId) {
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
