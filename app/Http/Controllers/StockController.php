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
        $selectedWarehouseId = $request->get('warehouse_id');
        
        // Obtener bodegas según el rol del usuario
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario ven todas las bodegas
            $warehouses = Warehouse::orderBy('nombre')->get();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario ve todas las bodegas (como secretaria)
            $warehouses = Warehouse::orderBy('nombre')->get();
            // Si no hay bodega seleccionada y tiene bodegas, seleccionar la primera
            if (!$selectedWarehouseId && $warehouses->count() > 0) {
                $selectedWarehouseId = $warehouses->first()->id;
            }
            // Validar que la bodega seleccionada sea de Buenaventura
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, $bodegasBuenaventuraIds)) {
                $selectedWarehouseId = $warehouses->count() > 0 ? $warehouses->first()->id : null;
            }
        } elseif ($user->rol === 'clientes') {
            // Clientes ven sus bodegas asignadas + bodegas de Buenaventura que les hicieron transferencias
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            
            // Obtener bodegas de Buenaventura que les hicieron transferencias recibidas
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (!empty($bodegasAsignadasIds)) {
                $bodegasBuenaventuraConTransferencias = \App\Models\TransferOrder::whereIn('warehouse_from_id', $bodegasBuenaventuraIds)
                    ->whereIn('warehouse_to_id', $bodegasAsignadasIds)
                    ->where('status', 'recibido')
                    ->distinct()
                    ->pluck('warehouse_from_id')
                    ->toArray();
                
                $bodegasBuenaventuraVisibles = Warehouse::whereIn('id', $bodegasBuenaventuraConTransferencias)
                    ->orderBy('nombre')
                    ->get();
            } else {
                $bodegasBuenaventuraVisibles = collect();
            }
            
            // Combinar bodegas asignadas + bodegas de Buenaventura con transferencias
            $warehouses = $bodegasAsignadas->merge($bodegasBuenaventuraVisibles)->unique('id')->sortBy('nombre')->values();
            
            // Si no hay bodega seleccionada y tiene bodegas, seleccionar la primera
            if (!$selectedWarehouseId && $warehouses->count() > 0) {
                $selectedWarehouseId = $warehouses->first()->id;
            }
            // Validar que la bodega seleccionada esté en las bodegas visibles
            $bodegasVisiblesIds = $warehouses->pluck('id')->toArray();
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, $bodegasVisiblesIds)) {
                $selectedWarehouseId = $warehouses->count() > 0 ? $warehouses->first()->id : null;
            }
        } else {
            // Otros roles ven solo su bodega (mantener compatibilidad)
            $warehouses = collect();
            if ($user->almacen_id) {
                $warehouses = collect([$user->almacen]);
            }
            if (!$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
            }
        }
        
        // Obtener productos globales (todos los productos sin almacen_id específico)
        // Ordenar por nombre, luego por medidas, luego por código para diferenciar productos similares
        $allProducts = Product::whereNull('almacen_id')
            ->with(['containers'])
            ->orderBy('nombre')
            ->orderBy('medidas')
            ->orderBy('codigo')
            ->get();
        
        // Obtener contenedores (solo bodegas que reciben contenedores tienen contenedores)
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        
        // Calcular stock por bodega para cada producto
        $productosStockPorBodega = $this->calcularStockPorBodega($allProducts, $selectedWarehouseId);
        
        // Siempre mostrar productos relacionados directamente con contenedores cuando existan
        // Crear una fila por cada combinación producto-contenedor
        $productsWithContainers = collect();
        
        // Construir query base para obtener productos en contenedores
        $containerProductQuery = DB::table('container_product')
            ->join('containers', 'container_product.container_id', '=', 'containers.id')
            ->join('products', 'container_product.product_id', '=', 'products.id')
            ->where('container_product.boxes', '>', 0)
            ->select(
                'container_product.container_id',
                'container_product.product_id',
                'container_product.boxes',
                'container_product.sheets_per_box',
                'containers.reference',
                'containers.warehouse_id'
            );
        
        // Si hay bodega seleccionada, filtrar por bodega
        if ($selectedWarehouseId) {
            $containerProductQuery->where('containers.warehouse_id', $selectedWarehouseId);
        }
        
        $containerProductData = $containerProductQuery->get();
        
        // Agrupar por producto y crear objetos producto-contenedor
        foreach ($containerProductData as $cp) {
            $product = $allProducts->firstWhere('id', $cp->product_id);
            if ($product) {
                // Calcular stock real descontando salidas
                $stockInicial = $cp->boxes * $cp->sheets_per_box;
                
                // Descontar salidas de esta bodega para este producto
                $salidas = Salida::where('warehouse_id', $cp->warehouse_id)
                    ->whereHas('products', function($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with(['products' => function($query) use ($product) {
                        $query->where('products.id', $product->id)->withPivot('quantity');
                    }])
                    ->get();
                
                $salidasDescontadas = 0;
                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades)
                        $salidasDescontadas += $productInSalida->pivot->quantity;
                    }
                }
                
                $stockFinal = max(0, $stockInicial - $salidasDescontadas);
                
                // Crear un objeto producto con información del contenedor
                $productCopy = clone $product;
                $productCopy->container_id = $cp->container_id;
                $productCopy->container_reference = $cp->reference;
                $productCopy->container_warehouse_id = $cp->warehouse_id;
                $productCopy->cajas_en_contenedor = $cp->boxes;
                $productCopy->laminas_en_contenedor = $stockFinal; // Stock después de descontar salidas
                $productsWithContainers->push($productCopy);
            }
        }
        
        // Si hay productos en contenedores, usarlos
        if ($productsWithContainers->count() > 0) {
            $products = $productsWithContainers;
        } else {
            // Si no hay productos en contenedores, usar lógica de filtrado normal (para bodegas que no reciben contenedores)
            if ($selectedWarehouseId) {
                $products = $allProducts->filter(function($product) use ($productosStockPorBodega, $selectedWarehouseId) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        $stock = $stockPorBodega->get($selectedWarehouseId, 0);
                        return $stock > 0;
                    }
                    return false;
                })->values();
            } else {
                // Si no hay bodega seleccionada, mostrar todos los productos que tienen stock en al menos una bodega
                $products = $allProducts->filter(function($product) use ($productosStockPorBodega) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        return $stockPorBodega->sum() > 0;
                    }
                    return false;
                })->values();
            }
        }
        
        if ($selectedWarehouseId) {
            // Si se selecciona una bodega que recibe contenedores, mostrar solo sus contenedores
            if (in_array($selectedWarehouseId, $bodegasQueRecibenContenedores)) {
                $containers = Container::where('warehouse_id', $selectedWarehouseId)
                    ->with('products')
                    ->orderByDesc('id')
                    ->get();
            } else {
                // Si se selecciona otra bodega, no mostrar contenedores
                $containers = collect();
            }
        } else {
            // Si no hay filtro, mostrar todos los contenedores
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
        
        // Obtener contenedores de origen desde transferencias recibidas para cada producto (filtrar por bodega si hay una seleccionada)
        $productosContenedoresOrigen = $this->obtenerContenedoresOrigen($products, $selectedWarehouseId);
        
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
        
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        return view('stock.index', compact('warehouses', 'products', 'containers', 'transferOrders', 'salidas', 'selectedWarehouseId', 'bodegasQueRecibenContenedores', 'productosCantidadesPorContenedor', 'productosContenedoresOrigen', 'productosStockPorBodega'));
    }

    private function getStockData(Request $request)
    {
        $user = Auth::user();
        $selectedWarehouseId = $request->get('warehouse_id');
        
        // Obtener bodegas según el rol del usuario
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario ven todas las bodegas
            $warehouses = Warehouse::orderBy('nombre')->get();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario ve todas las bodegas (como secretaria)
            $warehouses = Warehouse::orderBy('nombre')->get();
            // Si no hay bodega seleccionada y tiene bodegas, seleccionar la primera
            if (!$selectedWarehouseId && $warehouses->count() > 0) {
                $selectedWarehouseId = $warehouses->first()->id;
            }
            // Validar que la bodega seleccionada sea de Buenaventura
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, $bodegasBuenaventuraIds)) {
                $selectedWarehouseId = $warehouses->count() > 0 ? $warehouses->first()->id : null;
            }
        } elseif ($user->rol === 'clientes') {
            // Clientes ven sus bodegas asignadas + bodegas de Buenaventura que les hicieron transferencias
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            
            // Obtener bodegas de Buenaventura que les hicieron transferencias recibidas
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (!empty($bodegasAsignadasIds)) {
                $bodegasBuenaventuraConTransferencias = \App\Models\TransferOrder::whereIn('warehouse_from_id', $bodegasBuenaventuraIds)
                    ->whereIn('warehouse_to_id', $bodegasAsignadasIds)
                    ->where('status', 'recibido')
                    ->distinct()
                    ->pluck('warehouse_from_id')
                    ->toArray();
                
                $bodegasBuenaventuraVisibles = Warehouse::whereIn('id', $bodegasBuenaventuraConTransferencias)
                    ->orderBy('nombre')
                    ->get();
            } else {
                $bodegasBuenaventuraVisibles = collect();
            }
            
            // Combinar bodegas asignadas + bodegas de Buenaventura con transferencias
            $warehouses = $bodegasAsignadas->merge($bodegasBuenaventuraVisibles)->unique('id')->sortBy('nombre')->values();
            
            // Si no hay bodega seleccionada y tiene bodegas, seleccionar la primera
            if (!$selectedWarehouseId && $warehouses->count() > 0) {
                $selectedWarehouseId = $warehouses->first()->id;
            }
            // Validar que la bodega seleccionada esté en las bodegas visibles
            $bodegasVisiblesIds = $warehouses->pluck('id')->toArray();
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, $bodegasVisiblesIds)) {
                $selectedWarehouseId = $warehouses->count() > 0 ? $warehouses->first()->id : null;
            }
        } else {
            // Otros roles ven solo su bodega (mantener compatibilidad)
            $warehouses = collect();
            if ($user->almacen_id) {
                $warehouses = collect([$user->almacen]);
            }
            if (!$selectedWarehouseId) {
            $selectedWarehouseId = $user->almacen_id;
            }
        }
        
        // Obtener productos globales (todos los productos sin almacen_id específico)
        // Ordenar por nombre, luego por medidas, luego por código para diferenciar productos similares
        $allProducts = Product::whereNull('almacen_id')
            ->with(['containers'])
            ->orderBy('nombre')
            ->orderBy('medidas')
            ->orderBy('codigo')
            ->get();
        
        // Obtener contenedores (solo bodegas que reciben contenedores tienen contenedores)
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        
        // Calcular stock por bodega para cada producto
        $productosStockPorBodega = $this->calcularStockPorBodega($allProducts, $selectedWarehouseId);
        
        // Siempre mostrar productos relacionados directamente con contenedores cuando existan
        // Crear una fila por cada combinación producto-contenedor
        $productsWithContainers = collect();
        
        // Construir query base para obtener productos en contenedores
        $containerProductQuery = DB::table('container_product')
            ->join('containers', 'container_product.container_id', '=', 'containers.id')
            ->join('products', 'container_product.product_id', '=', 'products.id')
            ->where('container_product.boxes', '>', 0)
            ->select(
                'container_product.container_id',
                'container_product.product_id',
                'container_product.boxes',
                'container_product.sheets_per_box',
                'containers.reference',
                'containers.warehouse_id'
            );
        
        // Si hay bodega seleccionada, filtrar por bodega
        if ($selectedWarehouseId) {
            $containerProductQuery->where('containers.warehouse_id', $selectedWarehouseId);
        }
        
        $containerProductData = $containerProductQuery->get();
        
        // Agrupar por producto y crear objetos producto-contenedor
        foreach ($containerProductData as $cp) {
            $product = $allProducts->firstWhere('id', $cp->product_id);
            if ($product) {
                // Calcular stock real descontando salidas
                $stockInicial = $cp->boxes * $cp->sheets_per_box;
                
                // Descontar salidas de esta bodega para este producto
                $salidas = Salida::where('warehouse_id', $cp->warehouse_id)
                    ->whereHas('products', function($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with(['products' => function($query) use ($product) {
                        $query->where('products.id', $product->id)->withPivot('quantity');
                    }])
                    ->get();
                
                $salidasDescontadas = 0;
                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades)
                        $salidasDescontadas += $productInSalida->pivot->quantity;
                    }
                }
                
                $stockFinal = max(0, $stockInicial - $salidasDescontadas);
                
                // Crear un objeto producto con información del contenedor
                $productCopy = clone $product;
                $productCopy->container_id = $cp->container_id;
                $productCopy->container_reference = $cp->reference;
                $productCopy->container_warehouse_id = $cp->warehouse_id;
                $productCopy->cajas_en_contenedor = $cp->boxes;
                $productCopy->laminas_en_contenedor = $stockFinal; // Stock después de descontar salidas
                $productsWithContainers->push($productCopy);
            }
        }
        
        // Si hay productos en contenedores, usarlos
        if ($productsWithContainers->count() > 0) {
            $products = $productsWithContainers;
        } else {
            // Si no hay productos en contenedores, usar lógica de filtrado normal (para bodegas que no reciben contenedores)
            if ($selectedWarehouseId) {
                $products = $allProducts->filter(function($product) use ($productosStockPorBodega, $selectedWarehouseId) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        $stock = $stockPorBodega->get($selectedWarehouseId, 0);
                        return $stock > 0;
                    }
                    return false;
                })->values();
            } else {
                // Si no hay bodega seleccionada, mostrar todos los productos que tienen stock en al menos una bodega
                $products = $allProducts->filter(function($product) use ($productosStockPorBodega) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        return $stockPorBodega->sum() > 0;
                    }
                    return false;
                })->values();
            }
        }
        if ($selectedWarehouseId) {
            if (in_array($selectedWarehouseId, $bodegasQueRecibenContenedores)) {
                $containers = Container::where('warehouse_id', $selectedWarehouseId)
                    ->with('products')
                    ->orderByDesc('id')
                    ->get();
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
        // Solo refrescar si son modelos Eloquent reales (no productos clonados con contenedores)
        foreach ($products as $product) {
            // Solo refrescar si no es un producto clonado con información de contenedor
            if (!isset($product->container_reference) && method_exists($product, 'refresh')) {
                $product->refresh();
                // Recargar relaciones para asegurar que estén actualizadas
                if (method_exists($product, 'load')) {
                    $product->load('almacen', 'containers');
                }
            }
        }
        
        // Calcular cantidades por contenedor para cada producto (filtrar por bodega si hay una seleccionada)
        $productosCantidadesPorContenedor = $this->calcularCantidadesPorContenedor($products, $selectedWarehouseId);
        
        // Obtener contenedores de origen desde transferencias recibidas para cada producto (filtrar por bodega si hay una seleccionada)
        $productosContenedoresOrigen = $this->obtenerContenedoresOrigen($products, $selectedWarehouseId);
        
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
        
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        return compact('warehouses', 'products', 'containers', 'transferOrders', 'salidas', 'selectedWarehouseId', 'bodegasQueRecibenContenedores', 'productosCantidadesPorContenedor', 'productosContenedoresOrigen', 'productosStockPorBodega');
    }
    
    /**
     * Calcula las cantidades por contenedor para cada producto basado en:
     * 1. Contenedores relacionados directamente (tabla container_product)
     * 2. Transferencias recibidas
     */
    private function calcularCantidadesPorContenedor($products, $selectedWarehouseId = null)
    {
        $resultado = collect();
        
        // Obtener todas las transferencias recibidas de una vez para optimizar
        $allReceivedTransfers = TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id', 'quantity', 'good_sheets', 'bad_sheets');
            }])
            ->get();
        
        // Obtener todos los contenedores de una vez con su warehouse
        $allContainers = Container::with('warehouse')->get()->keyBy('id');
        
        // Cargar relaciones de contenedores para todos los productos de una vez
        // Solo si es una colección Eloquent (no productos clonados con contenedores)
        if ($products instanceof \Illuminate\Database\Eloquent\Collection && $products->count() > 0) {
            $firstProduct = $products->first();
            // Verificar si es un modelo Eloquent sin contenedor asignado (no clonado)
            if ($firstProduct instanceof \Illuminate\Database\Eloquent\Model && 
                !isset($firstProduct->container_reference)) {
                $products->load('containers');
            }
        }
        
        foreach ($products as $producto) {
            // Agrupar cantidades por contenedor
            $cantidadesPorContenedor = collect();
            
            // 1. Obtener contenedores relacionados directamente (tabla container_product)
            // Filtrar por bodega si hay una seleccionada
            $containerProductQuery = DB::table('container_product')
                ->where('product_id', $producto->id);
            
            // Si hay una bodega seleccionada, solo contar contenedores de esa bodega
            if ($selectedWarehouseId) {
                $containerIds = Container::where('warehouse_id', $selectedWarehouseId)->pluck('id')->toArray();
                if (!empty($containerIds)) {
                    $containerProductQuery->whereIn('container_id', $containerIds);
                } else {
                    // Si no hay contenedores en esa bodega, continuar con el siguiente producto
                    $resultado->put($producto->id, $cantidadesPorContenedor);
                    continue;
                }
            }
            
            $containerProductData = $containerProductQuery->get();
            
            foreach ($containerProductData as $cp) {
                $containerId = $cp->container_id;
                $container = $allContainers->get($containerId);
                if ($container) {
                    // Si hay bodega seleccionada, solo incluir si pertenece a esa bodega
                    if ($selectedWarehouseId && $container->warehouse_id != $selectedWarehouseId) {
                        continue;
                    }
                    
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
            
            // 2. Obtener cantidades de transferencias recibidas (solo para bodegas que NO reciben contenedores)
            // Para bodegas que reciben contenedores, solo mostramos las cajas que quedan en el contenedor (ya descontadas)
            // Para otras bodegas, mostramos las cantidades recibidas por transferencia
            // Solo si hay una bodega seleccionada y esa bodega NO recibe contenedores
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, Warehouse::getBodegasQueRecibenContenedores())) {
                $receivedTransfers = $allReceivedTransfers->filter(function($transfer) use ($producto, $selectedWarehouseId) {
                    if ($transfer->warehouse_to_id != $selectedWarehouseId) {
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
                        
                        // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                        $goodSheets = $productInTransfer->pivot->good_sheets;
                        if ($goodSheets !== null) {
                            // Ya está en láminas buenas
                            $laminas = $goodSheets;
                            // Calcular cajas si es necesario
                            $quantity = $producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0 
                                ? ceil($goodSheets / $producto->unidades_por_caja) 
                                : $goodSheets;
                        } else {
                            // Transferencia antigua sin good_sheets
                            $quantity = $productInTransfer->pivot->quantity;
                        // Calcular láminas si es tipo caja
                        $laminas = $quantity;
                        if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                            $laminas = $quantity * $producto->unidades_por_caja;
                            }
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
    private function obtenerContenedoresOrigen($products, $selectedWarehouseId = null)
    {
        $resultado = collect();
        
        // Obtener todas las transferencias recibidas de una vez para optimizar
        $allReceivedTransfers = TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id');
            }])
            ->get();
        
        foreach ($products as $producto) {
            // Si hay bodega seleccionada, filtrar transferencias recibidas para esa bodega
            // Si no hay bodega seleccionada, obtener todas las transferencias que contengan este producto
            $receivedTransfers = $allReceivedTransfers->filter(function($transfer) use ($producto, $selectedWarehouseId) {
                // Si hay bodega seleccionada, solo transferencias recibidas en esa bodega
                if ($selectedWarehouseId && $transfer->warehouse_to_id != $selectedWarehouseId) {
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
        $pdf = Pdf::loadView('stock.pdf', compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'bodegasQueRecibenContenedores', 'isExport'));
        
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
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        if (!$selectedWarehouseId || in_array($selectedWarehouseId, $bodegasQueRecibenContenedores)) {
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
    
    /**
     * Calcula el stock de cada producto por bodega
     * Para bodegas que reciben contenedores: stock desde container_product
     * Para otras bodegas: stock desde transferencias recibidas
     */
    private function calcularStockPorBodega($products, $selectedWarehouseId = null)
    {
        $resultado = collect();
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        
        // Obtener todas las transferencias recibidas
        $receivedTransfers = TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets');
            }])
            ->get();
        
        // Obtener todos los datos de container_product con container_id
        $containerProducts = DB::table('container_product')
            ->select('container_id', 'product_id', 'boxes', 'sheets_per_box')
            ->get()
            ->groupBy('product_id');
        
        // Obtener todos los contenedores con su warehouse_id
        $containersByWarehouse = DB::table('containers')
            ->select('id', 'warehouse_id')
            ->get()
            ->groupBy('warehouse_id');
        
        foreach ($products as $product) {
            $stockPorBodega = collect();
            
            // Para cada bodega, calcular el stock
            $warehouses = Warehouse::all();
            foreach ($warehouses as $warehouse) {
                $stock = 0;
                
                if (in_array($warehouse->id, $bodegasQueRecibenContenedores)) {
                    // Bodega que recibe contenedores: stock desde container_product
                    // Solo contar contenedores asignados a esta bodega específica
                    $containersInWarehouse = isset($containersByWarehouse[$warehouse->id]) 
                        ? $containersByWarehouse[$warehouse->id]->pluck('id')->toArray() 
                        : [];
                    
                    if (!empty($containersInWarehouse) && $containerProducts->has($product->id)) {
                        $cpData = $containerProducts->get($product->id);
                        foreach ($cpData as $cp) {
                            // Verificar que el contenedor pertenezca a esta bodega
                            $containerId = $cp->container_id ?? null;
                            if ($containerId && in_array($containerId, $containersInWarehouse)) {
                                $boxes = $cp->boxes ?? 0;
                                $sheetsPerBox = $cp->sheets_per_box ?? 0;
                                $stock += $boxes * $sheetsPerBox;
                            }
                        }
                    }
                } else {
                    // Otra bodega: stock desde transferencias recibidas
                    $transfersToWarehouse = $receivedTransfers->filter(function($transfer) use ($warehouse, $product) {
                        if ($transfer->warehouse_to_id != $warehouse->id) {
                            return false;
                        }
                        return $transfer->products->contains('id', $product->id);
                    });
                    
                    foreach ($transfersToWarehouse as $transfer) {
                        $productInTransfer = $transfer->products->first(function($p) use ($product) {
                            return $p->id === $product->id;
                        });
                        
                        if ($productInTransfer) {
                            // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                            $goodSheets = $productInTransfer->pivot->good_sheets;
                            if ($goodSheets !== null) {
                                // Ya está en láminas buenas
                                $stock += $goodSheets;
                            } else {
                                // Transferencia antigua sin good_sheets
                            $quantity = $productInTransfer->pivot->quantity;
                            // Si es tipo caja, convertir a unidades
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $quantity = $quantity * $product->unidades_por_caja;
                            }
                            $stock += $quantity;
                            }
                        }
                    }
                }
                
                // Descontar salidas
                $salidas = Salida::where('warehouse_id', $warehouse->id)
                    ->whereHas('products', function($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with(['products' => function($query) use ($product) {
                        $query->where('products.id', $product->id)->withPivot('quantity');
                    }])
                    ->get();
                
                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades), no en cajas
                        // No necesitamos convertir porque la cantidad ya está en unidades
                        $quantity = $productInSalida->pivot->quantity;
                        $stock -= $quantity;
                    }
                }
                
                $stockPorBodega->put($warehouse->id, max(0, $stock));
            }
            
            $resultado->put($product->id, $stockPorBodega);
        }
        
        return $resultado;
    }
}
