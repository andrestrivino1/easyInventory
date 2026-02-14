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
        $transfersDateFrom = $request->get('transfers_date_from');
        $transfersDateTo = $request->get('transfers_date_to');
        $salidasDateFrom = $request->get('salidas_date_from');
        $salidasDateTo = $request->get('salidas_date_to');

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
            // ->where('container_product.boxes', '>', 0) // Commented to include empty containers
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

        // UNIFICAR: Agrupar productos iguales (mismo código/nombre/medidas) de diferentes contenedores
        $productsGroupedByKey = collect();

        foreach ($containerProductData as $cp) {
            $product = $allProducts->firstWhere('id', $cp->product_id);
            if ($product) {
                // Crear una clave única por producto (código + nombre + medidas) para unificar
                $productKey = $product->codigo . '|' . $product->nombre . '|' . ($product->medidas ?? '');

                // Si el producto no está agrupado, inicializarlo
                if (!$productsGroupedByKey->has($productKey)) {
                    $productCopy = clone $product;
                    $productCopy->container_ids = collect();
                    $productCopy->container_references = collect();
                    $productCopy->container_warehouse_id = $cp->warehouse_id;
                    $productCopy->cajas_en_contenedor = 0;
                    $productCopy->laminas_en_contenedor = 0;
                    $productCopy->stock_inicial_total = 0; // Para calcular proporción de salidas
                    $productsGroupedByKey->put($productKey, $productCopy);
                }

                // Acumular cantidades de todos los contenedores para este producto
                $productGroup = $productsGroupedByKey->get($productKey);
                $productGroup->container_ids->push($cp->container_id);
                $productGroup->container_references->push($cp->reference);
                $productGroup->cajas_en_contenedor += $cp->boxes;

                // Calcular láminas iniciales de este contenedor
                $laminasContenedor = $cp->boxes * $cp->sheets_per_box;
                $productGroup->stock_inicial_total += $laminasContenedor;
            }
        }

        // Calcular salidas y stock final para cada producto unificado
        foreach ($productsGroupedByKey as $productKey => $productGroup) {
            // Obtener el producto original para calcular salidas
            $originalProduct = $allProducts->firstWhere('id', $productGroup->id);
            if ($originalProduct) {
                // Descontar salidas de esta bodega para este producto (se descuentan del total, no por contenedor)
                $salidas = Salida::where('warehouse_id', $productGroup->container_warehouse_id)
                    ->whereHas('products', function ($query) use ($originalProduct) {
                        $query->where('products.id', $originalProduct->id);
                    })
                    ->with([
                        'products' => function ($query) use ($originalProduct) {
                            $query->where('products.id', $originalProduct->id)->withPivot('quantity');
                        }
                    ])
                    ->get();

                $salidasDescontadas = 0;
                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades)
                        $salidasDescontadas += $productInSalida->pivot->quantity;
                    }
                }

                // Calcular stock final (descontar salidas del total unificado)
                $stockFinal = max(0, $productGroup->stock_inicial_total - $salidasDescontadas);
                $productGroup->laminas_en_contenedor = $stockFinal;

                // Crear referencia de contenedores unificada (todas las referencias separadas por coma)
                $productGroup->container_reference = $productGroup->container_references->unique()->implode(', ');

                // Limpiar propiedades temporales
                unset($productGroup->stock_inicial_total);
                unset($productGroup->container_ids);
                unset($productGroup->container_references);

                $productsWithContainers->push($productGroup);
            }
        }

        // Si no hay bodega seleccionada (warehouse_id vacío = "all"), obtener productos de TODAS las bodegas
        if (!$selectedWarehouseId) {
            // Obtener productos de bodegas que reciben contenedores (ya en productsWithContainers)
            $products = $productsWithContainers;

            // Agregar productos de bodegas que NO reciben contenedores
            $allWarehouses = Warehouse::all();
            $bodegasQueNoRecibenContenedores = $allWarehouses->filter(function ($warehouse) use ($bodegasQueRecibenContenedores) {
                return !in_array($warehouse->id, $bodegasQueRecibenContenedores);
            });

            $productsFromOtherWarehouses = collect();

            foreach ($bodegasQueNoRecibenContenedores as $warehouse) {
                $productosCantidadesPorContenedorTemp = $this->calcularCantidadesPorContenedor($allProducts, $warehouse->id);

                foreach ($allProducts as $product) {
                    $cantidadesPorContenedor = collect();
                    if ($productosCantidadesPorContenedorTemp->has($product->id)) {
                        $cantidadesPorContenedor = $productosCantidadesPorContenedorTemp->get($product->id);
                    }

                    // Verificar si tiene stock en esta bodega (ya sea por contenedores o por transferencias)
                    $hasStock = false;
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        $stock = $stockPorBodega->get($warehouse->id, 0);
                        $hasStock = $stock > 0;
                    }

                    // Si tiene contenedores o tiene stock, agregarlo
                    if ($cantidadesPorContenedor->count() > 0 || $hasStock) {
                        $productKey = $product->codigo . '|' . $product->nombre . '|' . ($product->medidas ?? '');

                        // Verificar si ya existe este producto en esta bodega específica
                        $exists = $products->contains(function ($p) use ($productKey, $warehouse) {
                            $pKey = $p->codigo . '|' . $p->nombre . '|' . ($p->medidas ?? '');
                            return $pKey === $productKey && isset($p->container_warehouse_id) && $p->container_warehouse_id == $warehouse->id;
                        });

                        if (!$exists) {
                            $productCopy = clone $product;
                            $productCopy->container_ids = collect();
                            $productCopy->container_references = collect();
                            $productCopy->container_warehouse_id = $warehouse->id;
                            $productCopy->cajas_en_contenedor = 0;
                            $productCopy->laminas_en_contenedor = 0;

                            if ($cantidadesPorContenedor->count() > 0) {
                                foreach ($cantidadesPorContenedor as $containerId => $cantidad) {
                                    if (is_numeric($containerId) && $containerId > 0) {
                                        $productCopy->container_ids->push($containerId);
                                        $productCopy->container_references->push($cantidad['container_reference'] ?? '-');
                                        $productCopy->cajas_en_contenedor += $cantidad['cajas'] ?? 0;
                                        $productCopy->laminas_en_contenedor += $cantidad['laminas'] ?? 0;
                                    }
                                }

                                if ($productCopy->container_references->count() > 0) {
                                    $productCopy->container_reference = $productCopy->container_references->unique()->filter()->implode(', ');
                                }
                            } else {
                                // Si no tiene contenedores pero tiene stock, usar el stock calculado
                                if ($productosStockPorBodega->has($product->id)) {
                                    $stockPorBodega = $productosStockPorBodega->get($product->id);
                                    $stock = $stockPorBodega->get($warehouse->id, 0);
                                    $productCopy->laminas_en_contenedor = $stock;
                                    $productCopy->container_reference = '-';
                                }
                            }

                            unset($productCopy->container_ids);
                            unset($productCopy->container_references);
                            $productsFromOtherWarehouses->push($productCopy);
                        }
                    }
                }
            }

            // Combinar productos de contenedores con productos de otras bodegas
            $products = $products->merge($productsFromOtherWarehouses);
        } elseif ($productsWithContainers->count() > 0) {
            // Si hay bodega seleccionada y hay productos en contenedores, usar solo esos
            $products = $productsWithContainers;
        } else {
            // Si no hay productos en contenedores (bodegas que NO reciben contenedores)
            // Crear entradas separadas por contenedor si hay productos recibidos por transferencias
            $productsFromTransfers = collect();

            if ($selectedWarehouseId && !in_array($selectedWarehouseId, $bodegasQueRecibenContenedores)) {
                // Para bodegas que NO reciben contenedores, crear una fila por cada combinación producto-contenedor
                // Calcular cantidades por contenedor ANTES de preparar los productos
                $productosCantidadesPorContenedorTemp = $this->calcularCantidadesPorContenedor($allProducts, $selectedWarehouseId);

                // Agrupar productos por código/nombre/medidas para unificar
                $productsGrouped = collect();

                foreach ($allProducts as $product) {
                    // Obtener cantidades por contenedor para este producto
                    $cantidadesPorContenedor = collect();
                    if ($productosCantidadesPorContenedorTemp->has($product->id)) {
                        $cantidadesPorContenedor = $productosCantidadesPorContenedorTemp->get($product->id);
                    }

                    if ($cantidadesPorContenedor->count() > 0) {
                        // Crear una clave única por producto (código + nombre + medidas)
                        $productKey = $product->codigo . '|' . $product->nombre . '|' . ($product->medidas ?? '');

                        // Inicializar o actualizar el grupo de productos
                        if (!$productsGrouped->has($productKey)) {
                            $productCopy = clone $product;
                            $productCopy->container_ids = collect();
                            $productCopy->container_references = collect();
                            $productCopy->container_warehouse_id = $selectedWarehouseId;
                            $productCopy->cajas_en_contenedor = 0;
                            $productCopy->laminas_en_contenedor = 0;
                            $productsGrouped->put($productKey, $productCopy);
                        }

                        // Sumar cantidades de todos los contenedores para este producto
                        $productGroup = $productsGrouped->get($productKey);
                        foreach ($cantidadesPorContenedor as $containerId => $cantidad) {
                            // Solo procesar contenedores reales (IDs positivos), ignorar IDs negativos unificados
                            if (is_numeric($containerId) && $containerId > 0) {
                                $productGroup->container_ids->push($containerId);
                                $productGroup->container_references->push($cantidad['container_reference'] ?? '-');
                                $productGroup->cajas_en_contenedor += $cantidad['cajas'] ?? 0;
                                $productGroup->laminas_en_contenedor += $cantidad['laminas'] ?? 0;
                            }
                        }
                    } else {
                        // Si no hay contenedores, verificar si tiene stock y agregarlo sin contenedor
                        if ($productosStockPorBodega->has($product->id)) {
                            $stockPorBodega = $productosStockPorBodega->get($product->id);
                            $stock = $stockPorBodega->get($selectedWarehouseId, 0);
                            if ($stock > 0) {
                                $productKey = $product->codigo . '|' . $product->nombre . '|' . ($product->medidas ?? '');
                                if (!$productsGrouped->has($productKey)) {
                                    $productsGrouped->put($productKey, $product);
                                }
                            }
                        }
                    }
                }

                // Procesar productos agrupados: crear referencia de contenedores unificada y marcar como producto con contenedor
                foreach ($productsGrouped as $productKey => $productGroup) {
                    if (isset($productGroup->container_references) && $productGroup->container_references->count() > 0) {
                        // Crear referencia de contenedores unificada (todas las referencias separadas por coma)
                        $productGroup->container_reference = $productGroup->container_references->unique()->filter()->implode(', ');
                        // Marcar como producto con contenedor para que se muestre correctamente en la vista
                        $productGroup->has_container = true;
                        // Limpiar propiedades temporales
                        unset($productGroup->container_ids);
                        unset($productGroup->container_references);
                    }
                }

                // Convertir productos agrupados a colección normal
                $productsFromTransfers = $productsGrouped->values();
                $products = $productsFromTransfers;
            } else {
                // Si hay bodega seleccionada pero NO recibe contenedores, usar lógica normal
                $products = $allProducts->filter(function ($product) use ($productosStockPorBodega, $selectedWarehouseId) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        $stock = $stockPorBodega->get($selectedWarehouseId, 0);
                        return $stock > 0;
                    }
                    return false;
                })->values();
            }
        }

        if ($selectedWarehouseId) {
            // Si se selecciona una bodega que recibe contenedores, mostrar solo sus contenedores
            if (in_array($selectedWarehouseId, $bodegasQueRecibenContenedores)) {
                $containers = Container::where('warehouse_id', $selectedWarehouseId)
                    ->with(['products', 'warehouse'])
                    ->orderByDesc('id')
                    ->get();
            } else {
                // Si se selecciona otra bodega, no mostrar contenedores
                $containers = collect();
            }
        } else {
            // Si no hay filtro, mostrar todos los contenedores
            $containers = Container::with(['products', 'warehouse'])->orderByDesc('id')->get();
        }

        // Obtener transferencias con filtro de fechas
        $transferOrdersQuery = TransferOrder::with(['from', 'to', 'products', 'driver']);

        if ($selectedWarehouseId) {
            $transferOrdersQuery->where(function ($query) use ($selectedWarehouseId) {
                $query->where('warehouse_from_id', $selectedWarehouseId)
                    ->orWhere('warehouse_to_id', $selectedWarehouseId);
            });
        }

        // Filtro de fechas para transferencias
        if ($transfersDateFrom) {
            $transferOrdersQuery->whereDate('date', '>=', $transfersDateFrom);
        }
        if ($transfersDateTo) {
            $transferOrdersQuery->whereDate('date', '<=', $transfersDateTo);
        }

        $transferOrders = $transferOrdersQuery->orderByDesc('date')->get();

        // Guardar una copia de la colección completa antes de paginar (para cálculos)
        $allProductsForCalculations = $products;

        // Obtener salidas con filtro de fechas
        $salidasQuery = Salida::with(['warehouse', 'products']);

        if ($selectedWarehouseId) {
            $salidasQuery->where('warehouse_id', $selectedWarehouseId);
        }

        // Filtro de fechas para salidas
        if ($salidasDateFrom) {
            $salidasQuery->whereDate('fecha', '>=', $salidasDateFrom);
        }
        if ($salidasDateTo) {
            $salidasQuery->whereDate('fecha', '<=', $salidasDateTo);
        }

        $salidas = $salidasQuery->orderByDesc('fecha')->get();

        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();

        // Paginación manual para productos (ya que es una colección procesada, no un query builder)
        $productsPerPage = 10;
        $productsCurrentPage = (int) $request->get('products_page', 1);
        $productsTotal = $products->count();

        // Solo paginar si hay más de 10 productos
        if ($productsTotal > $productsPerPage) {
            $productsItems = $products->slice(($productsCurrentPage - 1) * $productsPerPage, $productsPerPage)->values();

            // Crear paginador manual
            $products = new \Illuminate\Pagination\LengthAwarePaginator(
                $productsItems,
                $productsTotal,
                $productsPerPage,
                $productsCurrentPage,
                [
                    'path' => $request->url(),
                    'pageName' => 'products_page',
                    'query' => $request->query(),
                ]
            );

            // Asegurar que el paginador tenga los métodos necesarios
            $products->setPath($request->url());
        }

        // Paginación manual para contenedores
        $containersPerPage = 10;
        $containersCurrentPage = (int) $request->get('containers_page', 1);
        $containersTotal = $containers->count();

        $containersItems = $containers->slice(($containersCurrentPage - 1) * $containersPerPage, $containersPerPage)->values();
        $containers = new \Illuminate\Pagination\LengthAwarePaginator(
            $containersItems,
            $containersTotal,
            $containersPerPage,
            $containersCurrentPage,
            [
                'path' => $request->url(),
                'pageName' => 'containers_page',
                'query' => $request->query(),
            ]
        );
        $containers->setPath($request->url());

        // Paginación manual para transferencias
        $transfersPerPage = 10;
        $transfersCurrentPage = (int) $request->get('transfers_page', 1);
        $transfersTotal = $transferOrders->count();

        $transfersItems = $transferOrders->slice(($transfersCurrentPage - 1) * $transfersPerPage, $transfersPerPage)->values();
        $transferOrders = new \Illuminate\Pagination\LengthAwarePaginator(
            $transfersItems,
            $transfersTotal,
            $transfersPerPage,
            $transfersCurrentPage,
            [
                'path' => $request->url(),
                'pageName' => 'transfers_page',
                'query' => $request->query(),
            ]
        );
        $transferOrders->setPath($request->url());

        // Paginación manual para salidas
        $salidasPerPage = 10;
        $salidasCurrentPage = (int) $request->get('salidas_page', 1);
        $salidasTotal = $salidas->count();

        $salidasItems = $salidas->slice(($salidasCurrentPage - 1) * $salidasPerPage, $salidasPerPage)->values();
        $salidas = new \Illuminate\Pagination\LengthAwarePaginator(
            $salidasItems,
            $salidasTotal,
            $salidasPerPage,
            $salidasCurrentPage,
            [
                'path' => $request->url(),
                'pageName' => 'salidas_page',
                'query' => $request->query(),
            ]
        );
        $salidas->setPath($request->url());

        // Calcular cantidades por contenedor para cada producto (usar la colección completa)
        $productosCantidadesPorContenedor = $this->calcularCantidadesPorContenedor($allProductsForCalculations);

        // Obtener contenedores de origen desde transferencias recibidas para cada producto (usar la colección completa)
        $productosContenedoresOrigen = $this->obtenerContenedoresOrigen($allProductsForCalculations, $selectedWarehouseId);

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
            // ->where('container_product.boxes', '>', 0)
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

        // UNIFICAR: Agrupar productos iguales (mismo código/nombre/medidas) de diferentes contenedores
        $productsGroupedByKey = collect();

        foreach ($containerProductData as $cp) {
            $product = $allProducts->firstWhere('id', $cp->product_id);
            if ($product) {
                // Crear una clave única por producto (código + nombre + medidas) para unificar
                $productKey = $product->codigo . '|' . $product->nombre . '|' . ($product->medidas ?? '');

                // Si el producto no está agrupado, inicializarlo
                if (!$productsGroupedByKey->has($productKey)) {
                    $productCopy = clone $product;
                    $productCopy->container_ids = collect();
                    $productCopy->container_references = collect();
                    $productCopy->container_warehouse_id = $cp->warehouse_id;
                    $productCopy->cajas_en_contenedor = 0;
                    $productCopy->laminas_en_contenedor = 0;
                    $productCopy->stock_inicial_total = 0; // Para calcular proporción de salidas
                    $productCopy->related_warehouse_ids = collect(); // Init collection
                    $productsGroupedByKey->put($productKey, $productCopy);
                }

                // Acumular cantidades de todos los contenedores para este producto
                $productGroup = $productsGroupedByKey->get($productKey);
                $productGroup->related_warehouse_ids->push($cp->warehouse_id); // Collect warehouse ID
                $productGroup->container_ids->push($cp->container_id);
                $productGroup->container_references->push($cp->reference);
                $productGroup->cajas_en_contenedor += $cp->boxes;

                // Calcular láminas iniciales de este contenedor
                $laminasContenedor = $cp->boxes * $cp->sheets_per_box;
                $productGroup->stock_inicial_total += $laminasContenedor;
            }
        }

        // Calcular salidas y stock final para cada producto unificado
        foreach ($productsGroupedByKey as $productKey => $productGroup) {
            // Obtener el producto original para calcular salidas
            $originalProduct = $allProducts->firstWhere('id', $productGroup->id);
            if ($originalProduct) {
                // Descontar salidas de esta bodega para este producto (se descuentan del total, no por contenedor)
                $salidas = Salida::where('warehouse_id', $productGroup->container_warehouse_id)
                    ->whereHas('products', function ($query) use ($originalProduct) {
                        $query->where('products.id', $originalProduct->id);
                    })
                    ->with([
                        'products' => function ($query) use ($originalProduct) {
                            $query->where('products.id', $originalProduct->id)->withPivot('quantity');
                        }
                    ])
                    ->get();

                $salidasDescontadas = 0;
                foreach ($salidas as $salida) {
                    $productInSalida = $salida->products->first();
                    if ($productInSalida) {
                        // Las salidas ya se guardan en láminas (unidades)
                        $salidasDescontadas += $productInSalida->pivot->quantity;
                    }
                }

                // Calcular transferencias en tránsito o recibidas para descontar
                // FIX: Incluir 'recibido' porque no generan salida automática
                // FIX: Usar related_warehouse_ids para buscar transferencias de CUALQUIER bodega involucrada
                // FIX: Usar related_warehouse_ids o container_ids para buscar transferencias
                $warehouseIds = isset($productGroup->related_warehouse_ids) ? $productGroup->related_warehouse_ids->unique() : [$productGroup->container_warehouse_id];
                $containerIds = isset($productGroup->container_ids) ? $productGroup->container_ids : collect();

                $transfersInTransit = TransferOrder::whereIn('status', ['en_transito', 'recibido'])
                    ->where(function ($q) use ($warehouseIds, $containerIds, $originalProduct) {
                        $q->whereIn('warehouse_from_id', $warehouseIds)
                            ->orWhereHas('products', function ($pq) use ($containerIds, $originalProduct) {
                                $pq->where('products.id', $originalProduct->id)
                                    ->whereIn('transfer_order_products.container_id', $containerIds);
                            });
                    })
                    ->with([
                        'products' => function ($query) use ($originalProduct) {
                            $query->where('products.id', $originalProduct->id)->withPivot('quantity', 'sheets_per_box', 'container_id');
                        }
                    ])
                    ->get();

                $transitosDescontados = 0;
                foreach ($transfersInTransit as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        $quantity = $productInTransfer->pivot->quantity;
                        // Si es tipo caja, convertir a láminas
                        if ($originalProduct->tipo_medida === 'caja' && $originalProduct->unidades_por_caja > 0) {
                            $quantity = $quantity * $originalProduct->unidades_por_caja;
                        }
                        $transitosDescontados += $quantity;
                    }
                }

                // Calcular stock final (descontar salidas y tránsitos del total unificado)
                $stockFinal = max(0, $productGroup->stock_inicial_total - $salidasDescontadas - $transitosDescontados);
                $productGroup->laminas_en_contenedor = $stockFinal;

                // Disminuir cajas_en_contenedor también acorde al nuevo stock en láminas
                if ($originalProduct->tipo_medida === 'caja' && $originalProduct->unidades_por_caja > 0) {
                    $productGroup->cajas_en_contenedor = floor($stockFinal / $originalProduct->unidades_por_caja);
                }



                // Crear referencia de contenedores unificada (todas las referencias separadas por coma)
                $productGroup->container_reference = $productGroup->container_references->unique()->implode(', ');

                // Limpiar propiedades temporales
                unset($productGroup->stock_inicial_total);
                unset($productGroup->container_ids);
                unset($productGroup->container_references);

                $productsWithContainers->push($productGroup);
            }
        }

        // Si hay productos en contenedores, usarlos
        if ($productsWithContainers->count() > 0) {
            $products = $productsWithContainers;
        } else {
            // Si no hay productos en contenedores, usar lógica de filtrado normal (para bodegas que no reciben contenedores)
            if ($selectedWarehouseId) {
                $products = $allProducts->filter(function ($product) use ($productosStockPorBodega, $selectedWarehouseId) {
                    if ($productosStockPorBodega->has($product->id)) {
                        $stockPorBodega = $productosStockPorBodega->get($product->id);
                        $stock = $stockPorBodega->get($selectedWarehouseId, 0);
                        return $stock > 0;
                    }
                    return false;
                })->values();
            } else {
                // Si no hay bodega seleccionada, mostrar todos los productos que tienen stock en al menos una bodega
                $products = $allProducts->filter(function ($product) use ($productosStockPorBodega) {
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
                    ->with(['products', 'warehouse'])
                    ->orderByDesc('id')
                    ->get();
            } else {
                $containers = collect();
            }
        } else {
            $containers = Container::with(['products', 'warehouse'])->orderByDesc('id')->get();
        }

        // Obtener transferencias
        if ($selectedWarehouseId) {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->where(function ($query) use ($selectedWarehouseId) {
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

        // Calcular cantidades por contenedor para cada producto (para mostrar en la vista)
        // Si los productos ya tienen container_reference (productos clonados separados por contenedor),
        // no necesitamos recalcular, solo crear un mapa vacío. Si no, usar los productos originales.
        $hasPreparedProducts = false;
        foreach ($products as $product) {
            if (isset($product->container_reference)) {
                $hasPreparedProducts = true;
                break;
            }
        }

        if ($hasPreparedProducts) {
            // Si los productos ya están preparados (clonados con contenedor), crear un mapa vacío
            // porque la vista ya tiene toda la información necesaria en las propiedades del producto
            $productosCantidadesPorContenedor = collect();
        } else {
            // Si los productos no están preparados, calcular las cantidades por contenedor
            $productosCantidadesPorContenedor = $this->calcularCantidadesPorContenedor($products, $selectedWarehouseId);
        }

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
            ->with([
                'products' => function ($query) {
                    $query->withPivot('container_id', 'quantity', 'good_sheets', 'bad_sheets', 'receive_by');
                }
            ])
            ->get();

        // Obtener todos los contenedores de una vez con su warehouse
        $allContainers = Container::with('warehouse')->get()->keyBy('id');

        // Cargar relaciones de contenedores para todos los productos de una vez
        // Solo si es una colección Eloquent (no productos clonados con contenedores)
        if ($products instanceof \Illuminate\Database\Eloquent\Collection && $products->count() > 0) {
            $firstProduct = $products->first();
            // Verificar si es un modelo Eloquent sin contenedor asignado (no clonado)
            if (
                $firstProduct instanceof \Illuminate\Database\Eloquent\Model &&
                !isset($firstProduct->container_reference)
            ) {
                $products->load('containers');
            }
        }

        foreach ($products as $producto) {
            // Agrupar cantidades por contenedor
            $cantidadesPorContenedor = collect();

            // 1. Obtener contenedores relacionados directamente (tabla container_product)
            // Solo para bodegas que reciben contenedores, buscar en container_product
            // Para bodegas que NO reciben contenedores, los contenedores vienen de transferencias recibidas (se procesan en el punto 2)
            if (!$selectedWarehouseId || in_array($selectedWarehouseId, Warehouse::getBodegasQueRecibenContenedores())) {
                // Solo para bodegas que reciben contenedores o si no hay bodega seleccionada, buscar en container_product
                $containerProductQuery = DB::table('container_product')
                    ->where('product_id', $producto->id);

                // Si hay una bodega seleccionada, filtrar por contenedores de esa bodega
                if ($selectedWarehouseId) {
                    $containerIds = Container::where('warehouse_id', $selectedWarehouseId)->pluck('id')->toArray();
                    if (!empty($containerIds)) {
                        $containerProductQuery->whereIn('container_id', $containerIds);

                        $containerProductData = $containerProductQuery->get();

                        foreach ($containerProductData as $cp) {
                            $containerId = $cp->container_id;
                            $container = $allContainers->get($containerId);
                            if ($container && $container->warehouse_id == $selectedWarehouseId) {
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
                    }
                    // Si no hay contenedores en esa bodega en container_product, NO hacer continue
                    // Continuar para procesar transferencias recibidas en el punto 2
                } else {
                    // Si no hay bodega seleccionada, buscar todos los contenedores
                    $containerProductData = $containerProductQuery->get();

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
                }
            }

            // 2. Obtener cantidades de transferencias recibidas (solo para bodegas que NO reciben contenedores)
            // Para bodegas que reciben contenedores, solo mostramos las cajas que quedan en el contenedor (ya descontadas)
            // Para otras bodegas, mostramos las cantidades recibidas por transferencia
            // Solo si hay una bodega seleccionada y esa bodega NO recibe contenedores
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, Warehouse::getBodegasQueRecibenContenedores())) {
                $receivedTransfers = $allReceivedTransfers->filter(function ($transfer) use ($producto, $selectedWarehouseId) {
                    if ($transfer->warehouse_to_id != $selectedWarehouseId) {
                        return false;
                    }
                    // Buscar por ID del producto primero, luego por nombre y código (ya que el ID puede ser diferente)
                    return $transfer->products->contains(function ($p) use ($producto) {
                        return ($p->id === $producto->id) ||
                            ($p->nombre === $producto->nombre && $p->codigo === $producto->codigo);
                    });
                });

                // Agrupar transferencias recibidas POR CONTENEDOR
                // Cada contenedor debe mantener su propia fila con sus cantidades, NO unificar productos de diferentes contenedores
                foreach ($receivedTransfers as $transfer) {
                    // Buscar el producto en la transferencia por ID primero, luego por nombre y código
                    $productInTransfer = $transfer->products->first(function ($p) use ($producto) {
                        return ($p->id === $producto->id) ||
                            ($p->nombre === $producto->nombre && $p->codigo === $producto->codigo);
                    });

                    if ($productInTransfer && $productInTransfer->pivot->container_id) {
                        $containerId = $productInTransfer->pivot->container_id;

                        // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                        $goodSheets = $productInTransfer->pivot->good_sheets;
                        $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas'; // Por defecto 'laminas' para transferencias antiguas

                        $quantity = 0;
                        $laminas = 0;

                        if ($goodSheets !== null) {
                            if ($receiveBy === 'cajas') {
                                // good_sheets contiene cajas recibidas
                                $quantity = $goodSheets; // Cajas
                                // Convertir a láminas
                                if ($producto->unidades_por_caja > 0) {
                                    $laminas = $quantity * $producto->unidades_por_caja;
                                } else {
                                    $laminas = $quantity; // Si no hay unidades_por_caja, asumir 1:1
                                }
                            } else {
                                // good_sheets contiene láminas recibidas
                                $laminas = $goodSheets; // Láminas
                                // Convertir a cajas
                                if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                                    $quantity = ceil($laminas / $producto->unidades_por_caja);
                                } else {
                                    $quantity = $laminas; // Si no es tipo caja, usar directamente
                                }
                            }
                        } else {
                            // Transferencia antigua sin good_sheets
                            $quantity = $productInTransfer->pivot->quantity;
                            // Calcular láminas si es tipo caja
                            $laminas = $quantity;
                            if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                                $laminas = $quantity * $producto->unidades_por_caja;
                            }
                        }

                        // Agrupar POR CONTENEDOR - cada contenedor mantiene su propia fila
                        // Si ya existe el contenedor, sumar a sus cantidades existentes (puede haber múltiples transferencias del mismo producto en el mismo contenedor)
                        if ($cantidadesPorContenedor->has($containerId)) {
                            $cantidadesPorContenedor[$containerId]['cajas'] += $quantity;
                            $cantidadesPorContenedor[$containerId]['laminas'] += $laminas;
                        } else {
                            // Crear nueva entrada para este contenedor
                            // El contenedor puede estar en cualquier bodega, no solo en la bodega de destino
                            $container = $allContainers->get($containerId);
                            if (!$container && $containerId) {
                                // Si no se encuentra en el cache, buscarlo directamente
                                // El contenedor puede estar en otra bodega (bodega de origen), no en la bodega de destino
                                $container = Container::find($containerId);
                                // Si se encuentra, agregarlo al cache para futuras referencias
                                if ($container) {
                                    $allContainers->put($containerId, $container);
                                }
                            }
                            $cantidadesPorContenedor[$containerId] = [
                                'container_reference' => $container ? $container->reference : 'N/A (ID: ' . $containerId . ')',
                                'cajas' => $quantity,
                                'laminas' => $laminas,
                            ];
                        }
                    } else if ($productInTransfer && !$productInTransfer->pivot->container_id) {
                        // Si no hay contenedor asignado, crear una entrada unificada solo para transferencias sin contenedor
                        $unifiedContainerId = -($producto->id * 1000 + ($selectedWarehouseId ?? 0));

                        // Calcular cantidades
                        $goodSheets = $productInTransfer->pivot->good_sheets;
                        $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas';

                        $quantity = 0;
                        $laminas = 0;

                        if ($goodSheets !== null) {
                            if ($receiveBy === 'cajas') {
                                $quantity = $goodSheets;
                                if ($producto->unidades_por_caja > 0) {
                                    $laminas = $quantity * $producto->unidades_por_caja;
                                } else {
                                    $laminas = $quantity;
                                }
                            } else {
                                $laminas = $goodSheets;
                                if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                                    $quantity = ceil($laminas / $producto->unidades_por_caja);
                                } else {
                                    $quantity = $laminas;
                                }
                            }
                        } else {
                            $quantity = $productInTransfer->pivot->quantity;
                            $laminas = $quantity;
                            if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                                $laminas = $quantity * $producto->unidades_por_caja;
                            }
                        }

                        if ($cantidadesPorContenedor->has($unifiedContainerId)) {
                            // Si ya existe una entrada unificada para transferencias sin contenedor, sumar
                            $cantidadesPorContenedor[$unifiedContainerId]['cajas'] += $quantity;
                            $cantidadesPorContenedor[$unifiedContainerId]['laminas'] += $laminas;
                        } else {
                            // Crear nueva entrada unificada solo para transferencias sin contenedor
                            $cantidadesPorContenedor[$unifiedContainerId] = [
                                'container_reference' => '-',
                                'cajas' => $quantity,
                                'laminas' => $laminas,
                            ];
                        }
                    }
                }

            }

            // FIX: Descontar transferencias salientes (en tránsito o recibidas) para bodegas que NO reciben contenedores
            // Esto es crucial para bodegas como Girardot (18) que reciben stock por traslados y luego lo envían
            if ($selectedWarehouseId && !in_array($selectedWarehouseId, Warehouse::getBodegasQueRecibenContenedores())) {
                $outgoingTransfers = TransferOrder::whereIn('status', ['en_transito', 'recibido'])
                    ->where('warehouse_from_id', $selectedWarehouseId)
                    ->whereHas('products', function ($query) use ($producto) {
                        $query->where('products.id', $producto->id);
                    })
                    ->with([
                        'products' => function ($query) use ($producto) {
                            $query->where('products.id', $producto->id)->withPivot('quantity', 'container_id', 'sheets_per_box');
                        }
                    ])
                    ->get();

                foreach ($outgoingTransfers as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        $containerId = $productInTransfer->pivot->container_id;
                        $qty = $productInTransfer->pivot->quantity;

                        // Calcular láminas exactas basado en la unidad de medida
                        $laminas = $qty;
                        if ($producto->tipo_medida === 'caja' && $producto->unidades_por_caja > 0) {
                            $laminas = $qty * $producto->unidades_por_caja;
                        }

                        // Descontar del contenedor específico si existe en el cálculo actual
                        // Descontar del contenedor específico si existe en el cálculo actual
                        if ($containerId && $cantidadesPorContenedor->has($containerId)) {
                            $data = $cantidadesPorContenedor->get($containerId);
                            $data['cajas'] -= $qty;
                            $data['laminas'] -= $laminas;

                            // Evitar negativos visuales
                            if ($data['laminas'] < 0) {
                                $data['laminas'] = 0;
                                $data['cajas'] = 0;
                            }
                            $cantidadesPorContenedor->put($containerId, $data);
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
            ->with([
                'products' => function ($query) {
                    $query->withPivot('container_id');
                }
            ])
            ->get();

        foreach ($products as $producto) {
            // Si hay bodega seleccionada, filtrar transferencias recibidas para esa bodega
            // Si no hay bodega seleccionada, obtener todas las transferencias que contengan este producto
            $receivedTransfers = $allReceivedTransfers->filter(function ($transfer) use ($producto, $selectedWarehouseId) {
                // Si hay bodega seleccionada, solo transferencias recibidas en esa bodega
                if ($selectedWarehouseId && $transfer->warehouse_to_id != $selectedWarehouseId) {
                    return false;
                }
                // Buscar por nombre del producto (ya que el ID puede ser diferente)
                return $transfer->products->contains(function ($p) use ($producto) {
                    return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                });
            });

            // Obtener contenedores de origen desde transferencias recibidas
            $containerIds = collect();
            foreach ($receivedTransfers as $transfer) {
                // Buscar el producto en la transferencia por nombre y código
                $productInTransfer = $transfer->products->first(function ($p) use ($producto) {
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
        $pdf = Pdf::loadView('stock.pdf', compact('warehouses', 'products', 'containers', 'transferOrders', 'selectedWarehouseId', 'bodegasQueRecibenContenedores', 'isExport', 'productosStockPorBodega', 'productosCantidadesPorContenedor'));

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
                    <th>Bodega</th>
                    <th>Medidas</th>
                    <th>Contenedor</th>
                    <th>Cajas</th>
                    <th>Láminas</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($products as $product) {
            // Determinar la bodega a mostrar
            $warehouseIdToShow = null;
            $warehouseName = '-';
            if (isset($product->container_warehouse_id)) {
                $warehouseIdToShow = $product->container_warehouse_id;
            } elseif ($selectedWarehouseId) {
                $warehouseIdToShow = $selectedWarehouseId;
            }

            if ($warehouseIdToShow) {
                $warehouse = $warehouses->where('id', $warehouseIdToShow)->first();
                if ($warehouse) {
                    $warehouseName = $warehouse->nombre . ($warehouse->ciudad ? ' - ' . $warehouse->ciudad : '');
                }
            } elseif (!$selectedWarehouseId) {
                $warehouseName = 'Todas';
            }

            // Obtener referencia de contenedor
            $containerRef = '-';
            if (isset($product->container_reference)) {
                $containerRef = $product->container_reference;
            } elseif (isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($product->id)) {
                $cantidadesPorContenedor = $productosCantidadesPorContenedor->get($product->id);
                $containerRefs = $cantidadesPorContenedor->pluck('container_reference')->unique()->filter()->implode(', ');
                $containerRef = $containerRefs ?: '-';
            }

            // Calcular cajas
            $cajas = '-';
            if (isset($product->cajas_en_contenedor)) {
                $cajas = number_format($product->cajas_en_contenedor, 0);
            } elseif ($product->tipo_medida === 'caja') {
                // Calcular cajas desde stock si es tipo caja
                $laminas = isset($product->laminas_en_contenedor) ? $product->laminas_en_contenedor : 0;
                if ($laminas > 0 && $product->unidades_por_caja > 0) {
                    $cajas = number_format(ceil($laminas / $product->unidades_por_caja), 0);
                } elseif ($productosStockPorBodega->has($product->id)) {
                    $stockPorBodega = $productosStockPorBodega->get($product->id);
                    $stock = $warehouseIdToShow ? $stockPorBodega->get($warehouseIdToShow, 0) : $stockPorBodega->sum();
                    if ($stock > 0 && $product->unidades_por_caja > 0) {
                        $cajas = number_format(ceil($stock / $product->unidades_por_caja), 0);
                    }
                }
            }

            // Calcular láminas
            $laminas = 0;
            if (isset($product->laminas_en_contenedor)) {
                $laminas = $product->laminas_en_contenedor;
            } elseif ($productosStockPorBodega->has($product->id)) {
                $stockPorBodega = $productosStockPorBodega->get($product->id);
                if ($warehouseIdToShow) {
                    $laminas = $stockPorBodega->get($warehouseIdToShow, 0);
                } else {
                    $laminas = $stockPorBodega->sum();
                }
            }

            $html .= '<tr>
                <td>' . htmlspecialchars($product->codigo) . '</td>
                <td>' . htmlspecialchars($product->nombre) . '</td>
                <td>' . htmlspecialchars($warehouseName) . '</td>
                <td>' . htmlspecialchars($product->medidas ?? '-') . '</td>
                <td>' . htmlspecialchars($containerRef) . '</td>
                <td>' . $cajas . '</td>
                <td>' . number_format($laminas, 0) . '</td>
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
                        <th>Bodega</th>
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
                foreach ($container->products as $product) {
                    $totalBoxes += $product->pivot->boxes;
                    $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                    $productNames[] = $product->nombre . ' (' . $product->pivot->boxes . ' cajas × ' . $product->pivot->sheets_per_box . ' láminas)';
                }
                $warehouseName = '-';
                if ($container->warehouse) {
                    $warehouseName = $container->warehouse->nombre . ($container->warehouse->ciudad ? ' - ' . $container->warehouse->ciudad : '');
                }
                $html .= '<tr>
                    <td>' . htmlspecialchars($container->reference) . '</td>
                    <td>' . htmlspecialchars($warehouseName) . '</td>
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
            foreach ($transfer->products as $prod) {
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

    public function exportExcelProducts(Request $request)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }

        $data = $this->getStockData($request);
        extract($data);

        $warehouseName = $selectedWarehouseId
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos'
            : 'Todos';
        $filename = 'Productos-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.xls';

        $html = $this->generateExcelHeader('PRODUCTOS', $warehouseName, $selectedWarehouseId, $warehouses);
        $html .= $this->generateProductsTable($products, $warehouses, $selectedWarehouseId, $productosStockPorBodega, $productosCantidadesPorContenedor);
        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcelContainers(Request $request)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }

        $data = $this->getStockData($request);
        extract($data);

        $warehouseName = $selectedWarehouseId
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos'
            : 'Todos';
        $filename = 'Contenedores-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.xls';

        $html = $this->generateExcelHeader('CONTENEDORES', $warehouseName, $selectedWarehouseId, $warehouses);
        $html .= $this->generateContainersTable($containers, $bodegasQueRecibenContenedores, $selectedWarehouseId);
        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcelTransfers(Request $request)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }

        $data = $this->getStockData($request);
        extract($data);

        $warehouseName = $selectedWarehouseId
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos'
            : 'Todos';
        $filename = 'Transferencias-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.xls';

        $html = $this->generateExcelHeader('TRANSFERENCIAS', $warehouseName, $selectedWarehouseId, $warehouses);
        $html .= $this->generateTransfersTable($transferOrders);
        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcelSalidas(Request $request)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin') {
            return redirect()->route('stock.index')->with('error', 'No tienes permiso para descargar este archivo.');
        }

        $data = $this->getStockData($request);
        extract($data);

        $warehouseName = $selectedWarehouseId
            ? $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? 'Todos'
            : 'Todos';
        $filename = 'Salidas-Stock-' . str_replace(' ', '-', $warehouseName) . '-' . date('Y-m-d') . '.xls';

        $html = $this->generateExcelHeader('SALIDAS', $warehouseName, $selectedWarehouseId, $warehouses);
        $html .= $this->generateSalidasTable($salidas);
        $html .= '</body></html>';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response($html, 200, $headers);
    }

    private function generateExcelHeader($section, $warehouseName, $selectedWarehouseId, $warehouses)
    {
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
    <div class="header">INVENTARIO DE STOCK - ' . $section . '</div>
    <div class="info">Fecha: ' . date('d/m/Y H:i') . '</div>';

        if ($selectedWarehouseId) {
            $warehouseName = $warehouses->where('id', $selectedWarehouseId)->first()->nombre ?? '';
            $html .= '<div class="info">Almacén: ' . htmlspecialchars($warehouseName) . '</div>';
        } else {
            $html .= '<div class="info">Almacén: Todos los almacenes</div>';
        }

        return $html;
    }

    private function generateProductsTable($products, $warehouses, $selectedWarehouseId, $productosStockPorBodega, $productosCantidadesPorContenedor)
    {
        $html = '<div class="section-title">SECCIÓN: PRODUCTOS</div>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Bodega</th>
                    <th>Medidas</th>
                    <th>Contenedor</th>
                    <th>Cajas</th>
                    <th>Láminas</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($products as $product) {
            // Determinar la bodega a mostrar
            $warehouseIdToShow = null;
            $warehouseName = '-';
            if (isset($product->container_warehouse_id)) {
                $warehouseIdToShow = $product->container_warehouse_id;
            } elseif ($selectedWarehouseId) {
                $warehouseIdToShow = $selectedWarehouseId;
            }

            if ($warehouseIdToShow) {
                $warehouse = $warehouses->where('id', $warehouseIdToShow)->first();
                if ($warehouse) {
                    $warehouseName = $warehouse->nombre . ($warehouse->ciudad ? ' - ' . $warehouse->ciudad : '');
                }
            } elseif (!$selectedWarehouseId) {
                $warehouseName = 'Todas';
            }

            // Obtener referencia de contenedor
            $containerRef = '-';
            if (isset($product->container_reference)) {
                $containerRef = $product->container_reference;
            } elseif (isset($productosCantidadesPorContenedor) && $productosCantidadesPorContenedor->has($product->id)) {
                $cantidadesPorContenedor = $productosCantidadesPorContenedor->get($product->id);
                $containerRefs = $cantidadesPorContenedor->pluck('container_reference')->unique()->filter()->implode(', ');
                $containerRef = $containerRefs ?: '-';
            }

            // Calcular cajas
            $cajas = '-';
            if (isset($product->cajas_en_contenedor)) {
                $cajas = number_format($product->cajas_en_contenedor, 0);
            } elseif ($product->tipo_medida === 'caja') {
                // Calcular cajas desde stock si es tipo caja
                $laminas = isset($product->laminas_en_contenedor) ? $product->laminas_en_contenedor : 0;
                if ($laminas > 0 && $product->unidades_por_caja > 0) {
                    $cajas = number_format(ceil($laminas / $product->unidades_por_caja), 0);
                } elseif ($productosStockPorBodega->has($product->id)) {
                    $stockPorBodega = $productosStockPorBodega->get($product->id);
                    $stock = $warehouseIdToShow ? $stockPorBodega->get($warehouseIdToShow, 0) : $stockPorBodega->sum();
                    if ($stock > 0 && $product->unidades_por_caja > 0) {
                        $cajas = number_format(ceil($stock / $product->unidades_por_caja), 0);
                    }
                }
            }

            // Calcular láminas
            $laminas = 0;
            if (isset($product->laminas_en_contenedor)) {
                $laminas = $product->laminas_en_contenedor;
            } elseif ($productosStockPorBodega->has($product->id)) {
                $stockPorBodega = $productosStockPorBodega->get($product->id);
                if ($warehouseIdToShow) {
                    $laminas = $stockPorBodega->get($warehouseIdToShow, 0);
                } else {
                    $laminas = $stockPorBodega->sum();
                }
            }

            $html .= '<tr>
                <td>' . htmlspecialchars($product->codigo) . '</td>
                <td>' . htmlspecialchars($product->nombre) . '</td>
                <td>' . htmlspecialchars($warehouseName) . '</td>
                <td>' . htmlspecialchars($product->medidas ?? '-') . '</td>
                <td>' . htmlspecialchars($containerRef) . '</td>
                <td>' . $cajas . '</td>
                <td>' . number_format($laminas, 0) . '</td>
                <td>' . ($product->estado ? 'Activo' : 'Inactivo') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function generateContainersTable($containers, $bodegasQueRecibenContenedores, $selectedWarehouseId)
    {
        $html = '<div class="section-title">SECCIÓN: CONTENEDORES</div>
        <table>
            <thead>
                <tr>
                    <th>Referencia</th>
                    <th>Bodega</th>
                    <th>Productos</th>
                    <th>Total Cajas</th>
                    <th>Total Láminas</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>';

        if ($containers && $containers->count() > 0) {
            foreach ($containers as $container) {
                $totalBoxes = 0;
                $totalSheets = 0;
                $productNames = [];
                foreach ($container->products as $product) {
                    $totalBoxes += $product->pivot->boxes;
                    $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                    $productNames[] = $product->nombre . ' (' . $product->pivot->boxes . ' cajas × ' . $product->pivot->sheets_per_box . ' láminas)';
                }
                $warehouseName = '-';
                if ($container->warehouse) {
                    $warehouseName = $container->warehouse->nombre . ($container->warehouse->ciudad ? ' - ' . $container->warehouse->ciudad : '');
                }
                $html .= '<tr>
                    <td>' . htmlspecialchars($container->reference) . '</td>
                    <td>' . htmlspecialchars($warehouseName) . '</td>
                    <td>' . htmlspecialchars(implode(' | ', $productNames)) . '</td>
                    <td>' . number_format($totalBoxes, 0) . '</td>
                    <td>' . number_format($totalSheets, 0) . '</td>
                    <td>' . htmlspecialchars($container->note ?? '-') . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="6" style="text-align: center;">No hay contenedores registrados</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function generateTransfersTable($transferOrders)
    {
        $html = '<div class="section-title">SECCIÓN: TRANSFERENCIAS</div>
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

        if ($transferOrders && $transferOrders->count() > 0) {
            foreach ($transferOrders as $transfer) {
                $productNames = [];
                foreach ($transfer->products as $prod) {
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
        } else {
            $html .= '<tr><td colspan="7" style="text-align: center;">No hay transferencias registradas</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function generateSalidasTable($salidas)
    {
        $html = '<div class="section-title">SECCIÓN: SALIDAS</div>
        <table>
            <thead>
                <tr>
                    <th>No. Salida</th>
                    <th>Bodega</th>
                    <th>Fecha</th>
                    <th>Productos</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>';

        if ($salidas && $salidas->count() > 0) {
            foreach ($salidas as $salida) {
                $productNames = [];
                foreach ($salida->products as $prod) {
                    $productNames[] = $prod->nombre . ' (' . number_format($prod->pivot->quantity, 0) . ' láminas)';
                }
                $html .= '<tr>
                    <td>' . htmlspecialchars($salida->salida_number ?? 'N/A') . '</td>
                    <td>' . htmlspecialchars($salida->warehouse->nombre ?? '-') . '</td>
                    <td>' . htmlspecialchars($salida->fecha ? \Carbon\Carbon::parse($salida->fecha)->format('d/m/Y') : '-') . '</td>
                    <td>' . htmlspecialchars(implode(' | ', $productNames)) . '</td>
                    <td>' . htmlspecialchars($salida->note ?? '-') . '</td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="5" style="text-align: center;">No hay salidas registradas</td></tr>';
        }

        $html .= '</tbody></table>';
        return $html;
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
            ->with([
                'products' => function ($query) {
                    $query->withPivot('quantity', 'container_id', 'good_sheets', 'bad_sheets', 'receive_by');
                }
            ])
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
                    $transfersToWarehouse = $receivedTransfers->filter(function ($transfer) use ($warehouse, $product) {
                        if ($transfer->warehouse_to_id != $warehouse->id) {
                            return false;
                        }
                        return $transfer->products->contains('id', $product->id);
                    });

                    foreach ($transfersToWarehouse as $transfer) {
                        $productInTransfer = $transfer->products->first(function ($p) use ($product) {
                            return $p->id === $product->id;
                        });

                        if ($productInTransfer) {
                            // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                            $goodSheets = $productInTransfer->pivot->good_sheets;
                            $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas'; // Por defecto 'laminas' para transferencias antiguas

                            if ($goodSheets !== null) {
                                if ($receiveBy === 'cajas') {
                                    // good_sheets contiene cajas recibidas, convertir a láminas para el stock
                                    if ($product->unidades_por_caja > 0) {
                                        $stock += $goodSheets * $product->unidades_por_caja;
                                    } else {
                                        $stock += $goodSheets; // Si no hay unidades_por_caja, asumir 1:1
                                    }
                                } else {
                                    // good_sheets contiene láminas recibidas
                                    $stock += $goodSheets;
                                }
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
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity');
                        }
                    ])
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

                // Descontar transferencias salientes (en tránsito o recibidas)
                // FIX: Incluir 'recibido' porque no generan salida automática
                $transfersInTransit = TransferOrder::whereIn('status', ['en_transito', 'recibido'])
                    ->where('warehouse_from_id', $warehouse->id)
                    ->whereHas('products', function ($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with([
                        'products' => function ($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity', 'sheets_per_box');
                        }
                    ])
                    ->get();

                $transitoQty = 0;
                foreach ($transfersInTransit as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        $quantity = $productInTransfer->pivot->quantity;
                        // Si es tipo caja, convertir a láminas
                        if ($product->tipo_medida === 'caja') {
                            $sheetsPerBox = $productInTransfer->pivot->sheets_per_box ?? $product->unidades_por_caja;
                            if ($sheetsPerBox > 0) {
                                $quantity = $quantity * $sheetsPerBox;
                            }
                        }
                        $stock -= $quantity;
                        $transitoQty += $quantity;
                    }
                }

                // Log removed

                $stockPorBodega->put($warehouse->id, max(0, $stock));
            }

            $resultado->put($product->id, $stockPorBodega);
        }

        return $resultado;
    }
}
