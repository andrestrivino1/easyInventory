<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Salida;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class SalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Filtrar por bodega del usuario (excepto admin y funcionario que ven todas)
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $salidas = Salida::with(['warehouse', 'products'])->orderByDesc('fecha')->get();
        } elseif ($user->rol === 'funcionario') {
        } elseif ($user->rol === 'clientes') {
            // Clientes ven salidas solo de sus bodegas asignadas
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (empty($bodegasAsignadasIds)) {
                $bodegasAsignadasIds = [];
            }
            $salidas = Salida::with(['warehouse', 'products'])
                ->whereIn('warehouse_id', $bodegasAsignadasIds)
                ->orderByDesc('fecha')
                ->get();
        } else {
            $salidas = Salida::with(['warehouse', 'products'])
                ->where('warehouse_id', $user->almacen_id)
                ->orderByDesc('fecha')
                ->get();
        }
        
        return view('salidas.index', compact('salidas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        
        // Obtener productos de la bodega del usuario
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario pueden crear salidas en cualquier bodega
            $products = Product::with('containers')
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get();
            $warehouses = Warehouse::orderBy('nombre')->get();
        } elseif ($user->rol === 'funcionario') {
            // Este bloque ya no se ejecutará, pero lo dejamos por si acaso
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            $products = Product::with('containers')
                ->whereIn('almacen_id', $bodegasBuenaventuraIds)
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get();
            $warehouses = Warehouse::getBodegasBuenaventura();
        } elseif ($user->rol === 'clientes') {
            // Clientes pueden crear salidas solo de sus bodegas asignadas (excluyendo Buenaventura)
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            
            // Filtrar bodegas asignadas excluyendo las de Buenaventura
            $bodegasParaSalidas = $bodegasAsignadas->reject(function($bodega) use ($bodegasBuenaventuraIds) {
                return in_array($bodega->id, $bodegasBuenaventuraIds);
            });
            
            $bodegasParaSalidasIds = $bodegasParaSalidas->pluck('id')->toArray();
            
            if (!empty($bodegasParaSalidasIds)) {
                $products = Product::with('containers')
                    ->whereIn('almacen_id', $bodegasParaSalidasIds)
                    ->where('stock', '>', 0)
                    ->orderBy('nombre')
                    ->get();
            } else {
                $products = collect();
            }
            
            $warehouses = $bodegasParaSalidas->sortBy('nombre')->values();
        } else {
            $products = Product::with('containers')
                ->where('almacen_id', $user->almacen_id)
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get();
            $warehouses = collect([$user->almacen]);
        }
        
        $drivers = \App\Models\Driver::activeWithValidSocialSecurity()->orderBy('name')->get();
        return view('salidas.create', compact('products', 'warehouses', 'drivers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'fecha' => 'required|date',
            'a_nombre_de' => 'required|string|max:255',
            'nit_cedula' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
            'aprobo' => 'nullable|string|max:255',
            'ciudad_destino' => 'nullable|string|max:255',
            'driver_id' => 'nullable|exists:drivers,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
        
        // Validar que el usuario tenga permiso para esta bodega
        if ($user->rol === 'admin') {
            // Admin puede crear salidas en cualquier bodega
        } elseif ($user->rol === 'funcionario') {
            // Funcionario puede crear salidas en cualquier bodega (como secretaria)
            // No hay restricciones
        } elseif ($user->rol === 'clientes') {
            // Clientes solo pueden crear salidas en sus bodegas asignadas (excluyendo Buenaventura)
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            
            // Filtrar bodegas asignadas excluyendo las de Buenaventura
            $bodegasParaSalidas = $bodegasAsignadas->reject(function($bodega) use ($bodegasBuenaventuraIds) {
                return in_array($bodega->id, $bodegasBuenaventuraIds);
            });
            
            $bodegasParaSalidasIds = $bodegasParaSalidas->pluck('id')->toArray();
            
            if (!in_array($data['warehouse_id'], $bodegasParaSalidasIds)) {
                return back()->with('error', 'Solo puedes crear salidas en tus bodegas asignadas (no incluye bodegas de Buenaventura).')->withInput();
            }
        } else {
            // Otros roles solo pueden crear salidas en su bodega
            if ($data['warehouse_id'] != $user->almacen_id) {
                return back()->with('error', 'No tienes permiso para crear salidas en esta bodega.')->withInput();
            }
        }
        
        DB::beginTransaction();
        try {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);
            $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
            $productsToAttach = [];
            
            // Validar cada producto y calcular stock real
            foreach ($data['products'] as $index => $productData) {
                // Los productos son globales (almacen_id es null)
                $product = Product::where('id', $productData['product_id'])
                    ->whereNull('almacen_id')
                    ->first();
                    
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto seleccionado no existe.")->withInput();
                }
                
                // Validar que para bodegas de Buenaventura solo se permitan productos tipo "caja"
                $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
                if (in_array($data['warehouse_id'], $bodegasBuenaventuraIds) && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Las bodegas de Buenaventura solo pueden dar salida a productos medidos en cajas.")->withInput();
                }
                
                // Calcular stock real de la bodega
                $stock = 0;
                $containerId = null;
                
                if (in_array($data['warehouse_id'], $bodegasQueRecibenContenedores)) {
                    // Bodega que recibe contenedores: stock desde container_product
                    // Calcular stock total de TODOS los contenedores (unificado)
                    $containerProducts = DB::table('container_product')
                        ->join('containers', 'container_product.container_id', '=', 'containers.id')
                        ->where('container_product.product_id', $product->id)
                        ->where('containers.warehouse_id', $data['warehouse_id'])
                        ->where('container_product.boxes', '>', 0)
                        ->select('container_product.container_id', 'container_product.boxes', 'container_product.sheets_per_box')
                        ->get();
                    
                    // Sumar stock de todos los contenedores
                    foreach ($containerProducts as $cp) {
                        $stock += ($cp->boxes ?? 0) * ($cp->sheets_per_box ?? 0);
                        // Usar el primer container_id como referencia (para trazabilidad)
                        if ($containerId === null) {
                            $containerId = $cp->container_id;
                        }
                    }
                    
                    // Descontar salidas existentes (las salidas se descuentan del total unificado)
                    $salidas = Salida::where('warehouse_id', $data['warehouse_id'])
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
                            // Las salidas ya se guardan en láminas (unidades)
                            $stock -= $productInSalida->pivot->quantity;
                        }
                    }
                } else {
                    // Otra bodega: stock desde transferencias recibidas menos salidas
                    // Usar consulta directa a la tabla pivot para obtener container_id
                    $receivedQuantities = DB::table('transfer_order_products')
                        ->join('transfer_orders', 'transfer_order_products.transfer_order_id', '=', 'transfer_orders.id')
                        ->where('transfer_orders.status', 'recibido')
                        ->where('transfer_orders.warehouse_to_id', $data['warehouse_id'])
                        ->where('transfer_order_products.product_id', $product->id)
                        ->select('transfer_order_products.quantity', 'transfer_order_products.good_sheets', 'transfer_order_products.bad_sheets', 'transfer_order_products.container_id', 'transfer_orders.id as transfer_id')
                        ->get();
                    
                    foreach ($receivedQuantities as $received) {
                        // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                        if ($received->good_sheets !== null) {
                            // Ya está en láminas buenas
                            $stock += $received->good_sheets;
                        } else {
                            // Transferencia antigua sin good_sheets
                            $quantity = $received->quantity;
                            // Si es tipo caja, convertir a unidades
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $quantity = $quantity * $product->unidades_por_caja;
                            }
                            $stock += $quantity;
                        }
                        // Obtener el container_id de la primera transferencia que tenga stock y container_id
                        if ($containerId === null && $received->container_id) {
                            $containerId = $received->container_id;
                        }
                    }
                    
                    // Descontar salidas existentes
                    $salidas = Salida::where('warehouse_id', $data['warehouse_id'])
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
                            $quantity = $productInSalida->pivot->quantity;
                            $stock -= $quantity;
                        }
                    }
                }
                
                // Determinar si la bodega es de Buenaventura
                $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
                $isBuenaventura = in_array($data['warehouse_id'], $bodegasBuenaventuraIds);
                
                // Convertir cantidad según el tipo de bodega
                $quantity = $productData['quantity'];
                if ($isBuenaventura) {
                    // Para Buenaventura: la cantidad viene en cajas, convertir a láminas
                    if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                        $quantity = $quantity * $product->unidades_por_caja;
                    }
                }
                // Para otras bodegas: la cantidad ya viene en láminas (unidades)
                
                // Validar stock suficiente
                if ($stock < $quantity) {
                    DB::rollBack();
                    // Mostrar stock disponible según el tipo de bodega
                    $laminasDisponibles = $stock;
                    if ($isBuenaventura && $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                        $cajasDisponibles = floor($stock / $product->unidades_por_caja);
                        $cajasSolicitadas = $productData['quantity'];
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$laminasDisponibles} láminas). Solicitado: {$cajasSolicitadas} cajas.")->withInput();
                    } else {
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $cajasDisponibles = floor($stock / $product->unidades_por_caja);
                            return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas ({$cajasDisponibles} cajas). Solicitado: {$quantity} láminas.")->withInput();
                        } else {
                            return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas. Solicitado: {$quantity} láminas.")->withInput();
                        }
                    }
                }
                
                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => $containerId, // Obtener el contenedor del producto
                    'quantity' => $quantity, // Cantidad en láminas (unidades) - siempre se guarda en láminas
                ];
            }
            
            // Crear la salida
            $salida = Salida::create([
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $user->id, // Guardar el usuario que crea la salida
                'driver_id' => $data['driver_id'] ?? null,
                'fecha' => $data['fecha'],
                'a_nombre_de' => $data['a_nombre_de'],
                'nit_cedula' => $data['nit_cedula'],
                'note' => $data['note'] ?? null,
                'aprobo' => $data['aprobo'] ?? null,
                'ciudad_destino' => $data['ciudad_destino'] ?? null,
            ]);
            
            // Asociar productos a la salida
            // El stock se calcula dinámicamente, así que solo necesitamos registrar la salida
            foreach ($productsToAttach as $index => $item) {
                try {
                    $product = Product::findOrFail($item['product_id']);
                    
                    // Asociar el producto a la salida
                    // La cantidad ya está en láminas (unidades) para clientes y funcionarios
                    $pivotData = [
                        'quantity' => $item['quantity'], // Cantidad en láminas (unidades)
                        'container_id' => $item['container_id']
                    ];
                    
                    $salida->products()->attach($item['product_id'], $pivotData);
                    
                    \Log::info('SALIDA store - producto asociado', [
                        'product_id' => $product->id,
                        'product_nombre' => $product->nombre,
                        'warehouse_id' => $data['warehouse_id'],
                        'quantity' => $item['quantity'],
                        'container_id' => $item['container_id']
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('SALIDA store - error al procesar producto', [
                        'msg'=>$e->getMessage(), 
                        'product_id'=>$item['product_id'],
                        'warehouse_id'=>$data['warehouse_id'],
                        'index'=>$index,
                        'trace'=>$e->getTraceAsString()
                    ]);
                    return back()->with('error', $e->getMessage())->withInput();
                }
            }
            
            DB::commit();
            return redirect()->route('salidas.index')->with('success', 'Salida creada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('SALIDA store - validación fallida', ['errors'=>$e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SALIDA store - exception', [
                'msg'=>$e->getMessage(), 
                'file'=>$e->getFile(), 
                'line'=>$e->getLine(),
                'trace'=>$e->getTraceAsString()
            ]);
            $errorMessage = "Error al crear la salida: " . $e->getMessage();
            if (config('app.debug')) {
                $errorMessage .= " (Archivo: " . basename($e->getFile()) . ", Línea: " . $e->getLine() . ")";
            }
            return back()->with('error', $errorMessage)->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Salida  $salida
     * @return \Illuminate\Http\Response
     */
    public function show(Salida $salida)
    {
        $user = Auth::user();
        
        // Validar que el usuario tenga acceso a esta salida
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario pueden ver todas las salidas
        } elseif ($user->rol === 'funcionario') {
        } elseif ($user->rol === 'clientes') {
            // Clientes solo pueden ver salidas de sus bodegas asignadas
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (!in_array($salida->warehouse_id, $bodegasAsignadasIds)) {
                return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
            }
        } else {
            // Otros roles solo pueden ver salidas de su bodega
            if ($salida->warehouse_id != $user->almacen_id) {
                return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
            }
        }
        
        $salida->load(['warehouse', 'products']);
        
        return view('salidas.show', compact('salida'));
    }

    /**
     * Export PDF
     */
    public function export(Salida $salida)
    {
        $user = Auth::user();
        
        // Validar que el usuario tenga acceso a esta salida
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario pueden descargar todas las salidas
        } elseif ($user->rol === 'clientes') {
            // Clientes solo pueden descargar salidas de sus bodegas asignadas
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (!in_array($salida->warehouse_id, $bodegasAsignadasIds)) {
                return redirect()->route('salidas.index')->with('error', 'No tienes permiso para descargar esta salida.');
            }
        } else {
            // Otros roles solo pueden descargar salidas de su bodega
            if ($salida->warehouse_id != $user->almacen_id) {
                return redirect()->route('salidas.index')->with('error', 'No tienes permiso para descargar esta salida.');
            }
        }
        
        $salida->load(['warehouse', 'products' => function($query) {
            $query->withPivot('quantity', 'container_id');
        }, 'user', 'driver']);
        
        $isExport = true;
        $currentUser = $user;
        $pdf = Pdf::loadView('salidas.pdf', compact('salida', 'isExport', 'currentUser'));
        $filename = 'Salida-' . $salida->salida_number . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Print PDF
     */
    public function print(Salida $salida)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login');
            }
            
            // Validar que el usuario tenga acceso a esta salida
            if (in_array($user->rol, ['admin', 'funcionario'])) {
                // Admin y funcionario pueden imprimir todas las salidas
            } elseif ($user->rol === 'clientes') {
                // Clientes solo pueden imprimir salidas de sus bodegas asignadas
                $bodegasAsignadas = $user->almacenes()->get();
                $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
                if (!in_array($salida->warehouse_id, $bodegasAsignadasIds)) {
                    return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
                }
            } else {
                // Otros roles solo pueden imprimir salidas de su bodega
                if ($salida->warehouse_id != $user->almacen_id) {
                    return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
                }
            }
            
            $salida->load(['warehouse', 'products' => function($query) {
                $query->withPivot('quantity', 'container_id');
            }, 'user', 'driver']);
            
            $isExport = false;
            $currentUser = $user;
            return view('salidas.pdf', compact('salida', 'isExport', 'currentUser'));
        } catch (\Exception $e) {
            \Log::error('SALIDA print - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'salida_id' => $salida->id ?? null
            ]);
            return redirect()->route('salidas.index')->with('error', 'Error al cargar la salida: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Salida  $salida
     * @return \Illuminate\Http\Response
     */
    public function destroy(Salida $salida)
    {
        $user = Auth::user();
        
        // Solo admin puede eliminar salidas
        if ($user->rol !== 'admin') {
            return redirect()->route('salidas.index')->with('error', 'No tienes permiso para eliminar salidas.');
        }
        
        DB::beginTransaction();
        try {
            // El stock se calcula dinámicamente, así que solo necesitamos eliminar la salida
            // No necesitamos restaurar stock porque se calcula desde transferencias recibidas menos salidas
            $salida->delete();
            
            DB::commit();
            return redirect()->route('salidas.index')->with('success', 'Salida eliminada correctamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SALIDA destroy - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'salida_id' => $salida->id ?? null
            ]);
            return back()->with('error', "Error al eliminar la salida: " . $e->getMessage());
        }
    }

    /**
     * Obtener productos disponibles en una bodega específica para salidas
     */
    public function getProductsForWarehouse($warehouseId)
    {
        $user = Auth::user();
        $warehouse = Warehouse::findOrFail($warehouseId);
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        
        \Log::info('getProductsForWarehouse - Inicio', [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouse->nombre,
            'user_id' => $user->id,
            'user_rol' => $user->rol
        ]);
        
        // Validar permisos según el rol
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            // Admin y funcionario pueden ver productos de cualquier bodega
        } elseif ($user->rol === 'funcionario') {
        } elseif ($user->rol === 'clientes') {
            // Clientes solo pueden ver productos de sus bodegas asignadas (excluyendo Buenaventura)
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
            
            // Filtrar bodegas asignadas excluyendo las de Buenaventura
            $bodegasParaSalidas = $bodegasAsignadas->reject(function($bodega) use ($bodegasBuenaventuraIds) {
                return in_array($bodega->id, $bodegasBuenaventuraIds);
            });
            
            $bodegasParaSalidasIds = $bodegasParaSalidas->pluck('id')->toArray();
            
            \Log::info('getProductsForWarehouse - Cliente', [
                'bodegas_asignadas' => $bodegasAsignadas->pluck('id')->toArray(),
                'bodegas_para_salidas' => $bodegasParaSalidasIds,
                'warehouse_id' => $warehouseId
            ]);
            
            if (!in_array($warehouseId, $bodegasParaSalidasIds)) {
                \Log::warning('getProductsForWarehouse - Acceso denegado para cliente', ['warehouse_id' => $warehouseId]);
                return response()->json([], 403);
            }
        } elseif ($user->rol !== 'admin') {
            // Otros roles solo pueden ver productos de su bodega
            if ($warehouseId != $user->almacen_id) {
                \Log::warning('getProductsForWarehouse - Acceso denegado para rol', ['rol' => $user->rol, 'warehouse_id' => $warehouseId]);
                return response()->json([], 403);
            }
        }
        
        // Obtener todos los productos globales (sin almacen_id específico)
        $allProductsQuery = Product::whereNull('almacen_id');
        
        // Si la bodega es de Buenaventura, solo mostrar productos tipo "caja"
        $bodegasBuenaventuraIds = Warehouse::getBodegasBuenaventuraIds();
        if (in_array($warehouseId, $bodegasBuenaventuraIds)) {
            $allProductsQuery->where('tipo_medida', 'caja');
        }
        
        $allProducts = $allProductsQuery->orderBy('nombre')->get();
        
        \Log::info('getProductsForWarehouse - Productos encontrados', [
            'total_productos' => $allProducts->count(),
            'bodega_recibe_contenedores' => in_array($warehouseId, $bodegasQueRecibenContenedores),
            'bodega_buenaventura' => in_array($warehouseId, $bodegasBuenaventuraIds),
            'filtro_tipo_caja' => in_array($warehouseId, $bodegasBuenaventuraIds)
        ]);
        
        $productsWithStock = [];
        
        foreach ($allProducts as $product) {
            $stock = 0;
            
            if (in_array($warehouseId, $bodegasQueRecibenContenedores)) {
                // Bodega que recibe contenedores: stock desde container_product
                $containerProducts = DB::table('container_product')
                    ->join('containers', 'container_product.container_id', '=', 'containers.id')
                    ->where('container_product.product_id', $product->id)
                    ->where('containers.warehouse_id', $warehouseId)
                    ->select('container_product.boxes', 'container_product.sheets_per_box')
                    ->get();
                
                foreach ($containerProducts as $cp) {
                    $stock += ($cp->boxes ?? 0) * ($cp->sheets_per_box ?? 0);
                }
            } else {
                // Otra bodega: stock desde transferencias recibidas menos salidas
                // Usar consulta directa a la tabla pivot para mayor confiabilidad
                $receivedQuantities = DB::table('transfer_order_products')
                    ->join('transfer_orders', 'transfer_order_products.transfer_order_id', '=', 'transfer_orders.id')
                    ->where('transfer_orders.status', 'recibido')
                    ->where('transfer_orders.warehouse_to_id', $warehouseId)
                    ->where('transfer_order_products.product_id', $product->id)
                    ->select('transfer_order_products.quantity', 'transfer_order_products.good_sheets', 'transfer_order_products.bad_sheets', 'transfer_orders.id as transfer_id', 'transfer_orders.order_number')
                    ->get();
                
                \Log::info('getProductsForWarehouse - Transferencias recibidas (consulta directa)', [
                    'product_id' => $product->id,
                    'product_nombre' => $product->nombre,
                    'warehouse_id' => $warehouseId,
                    'transferencias_count' => $receivedQuantities->count(),
                    'transferencias' => $receivedQuantities->map(function($t) {
                        return [
                            'transfer_id' => $t->transfer_id,
                            'order_number' => $t->order_number,
                            'quantity' => $t->quantity,
                            'good_sheets' => $t->good_sheets
                        ];
                    })->toArray()
                ]);
                
                // Sumar cantidades recibidas (solo las láminas buenas)
                foreach ($receivedQuantities as $received) {
                    // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                    $quantity = null;
                    if ($received->good_sheets !== null) {
                        // Ya está en láminas buenas
                        $stock += $received->good_sheets;
                        $quantity = $received->good_sheets; // Para el log
                    } else {
                        // Transferencia antigua sin good_sheets
                        $quantity = $received->quantity;
                        // Si es tipo caja, convertir a unidades
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $quantity = $quantity * $product->unidades_por_caja;
                        }
                        $stock += $quantity;
                    }
                    
                    \Log::info('getProductsForWarehouse - Transferencia procesada', [
                        'transfer_id' => $received->transfer_id,
                        'transfer_order_number' => $received->order_number,
                        'product_id' => $product->id,
                        'quantity_original' => $received->quantity,
                        'good_sheets' => $received->good_sheets,
                        'quantity_en_unidades' => $quantity,
                        'stock_acumulado' => $stock
                    ]);
                }
                
                // Descontar salidas usando consulta directa
                $salidasQuantities = DB::table('salida_products')
                    ->join('salidas', 'salida_products.salida_id', '=', 'salidas.id')
                    ->where('salidas.warehouse_id', $warehouseId)
                    ->where('salida_products.product_id', $product->id)
                    ->select('salida_products.quantity', 'salidas.id as salida_id')
                    ->get();
                
                \Log::info('getProductsForWarehouse - Salidas encontradas (consulta directa)', [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouseId,
                    'salidas_count' => $salidasQuantities->count(),
                    'salidas' => $salidasQuantities->map(function($s) {
                        return [
                            'salida_id' => $s->salida_id,
                            'quantity' => $s->quantity
                        ];
                    })->toArray()
                ]);
                
                foreach ($salidasQuantities as $salida) {
                    // Las salidas ya se guardan en láminas (unidades), no en cajas
                    $quantity = $salida->quantity;
                    $stock -= $quantity;
                    
                    \Log::info('getProductsForWarehouse - Salida descontada', [
                        'salida_id' => $salida->salida_id,
                        'product_id' => $product->id,
                        'quantity_descontada' => $quantity,
                        'stock_restante' => $stock
                    ]);
                }
            }
            
            // Asegurarse de que el stock sea al menos 0 (no negativo)
            $finalStock = max(0, $stock);
            
            // Verificar si hay transferencias recibidas para este producto
            $hasReceivedTransfers = false;
            if (!in_array($warehouseId, $bodegasQueRecibenContenedores)) {
                $hasReceivedTransfers = DB::table('transfer_order_products')
                    ->join('transfer_orders', 'transfer_order_products.transfer_order_id', '=', 'transfer_orders.id')
                    ->where('transfer_orders.status', 'recibido')
                    ->where('transfer_orders.warehouse_to_id', $warehouseId)
                    ->where('transfer_order_products.product_id', $product->id)
                    ->exists();
            }
            
            \Log::info('getProductsForWarehouse - Resumen producto', [
                'product_id' => $product->id,
                'product_nombre' => $product->nombre,
                'product_codigo' => $product->codigo,
                'stock_calculado' => $stock,
                'stock_final' => $finalStock,
                'warehouse_id' => $warehouseId,
                'tipo_medida' => $product->tipo_medida,
                'unidades_por_caja' => $product->unidades_por_caja ?? 1,
                'has_received_transfers' => $hasReceivedTransfers
            ]);
            
            // Incluir productos con stock > 0
            // Si el stock es 0 pero hay transferencias recibidas, también incluirlo para diagnóstico
            // (esto ayuda a identificar problemas donde las salidas descontaron más de lo recibido)
            if ($finalStock > 0 || ($hasReceivedTransfers && $finalStock == 0)) {
                $productsWithStock[] = [
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'codigo' => $product->codigo,
                    'medidas' => $product->medidas ?? '',
                    'stock' => $finalStock,
                    'tipo_medida' => $product->tipo_medida,
                    'unidades_por_caja' => $product->unidades_por_caja ?? 1,
                ];
                
                if ($finalStock == 0 && $hasReceivedTransfers) {
                    \Log::warning('getProductsForWarehouse - Producto incluido con stock 0 pero tiene transferencias recibidas', [
                        'product_id' => $product->id,
                        'product_nombre' => $product->nombre,
                        'warehouse_id' => $warehouseId,
                        'stock_calculado' => $stock
                    ]);
                }
            } else {
                \Log::warning('getProductsForWarehouse - Producto excluido', [
                    'product_id' => $product->id,
                    'product_nombre' => $product->nombre,
                    'stock_calculado' => $stock,
                    'warehouse_id' => $warehouseId,
                    'has_received_transfers' => $hasReceivedTransfers
                ]);
            }
        }
        
        // Verificar transferencias recibidas para esta bodega (para debugging)
        $allReceivedTransfers = DB::table('transfer_orders')
            ->where('status', 'recibido')
            ->where('warehouse_to_id', $warehouseId)
            ->select('id', 'order_number', 'warehouse_to_id', 'status', 'date')
            ->get();
        
        $transferProducts = DB::table('transfer_order_products')
            ->join('transfer_orders', 'transfer_order_products.transfer_order_id', '=', 'transfer_orders.id')
            ->where('transfer_orders.status', 'recibido')
            ->where('transfer_orders.warehouse_to_id', $warehouseId)
            ->select('transfer_order_products.product_id', 'transfer_order_products.quantity', 'transfer_orders.id as transfer_id', 'transfer_orders.order_number')
            ->get();
        
        \Log::info('getProductsForWarehouse - Resultado final', [
            'productos_con_stock' => count($productsWithStock),
            'total_productos_globales' => $allProducts->count(),
            'warehouse_id' => $warehouseId,
            'warehouse_nombre' => $warehouse->nombre,
            'bodega_recibe_contenedores' => in_array($warehouseId, $bodegasQueRecibenContenedores),
            'transferencias_recibidas_count' => $allReceivedTransfers->count(),
            'transferencias_recibidas' => $allReceivedTransfers->map(function($t) {
                return [
                    'id' => $t->id,
                    'order_number' => $t->order_number,
                    'date' => $t->date
                ];
            })->toArray(),
            'productos_en_transferencias' => $transferProducts->groupBy('product_id')->map(function($group) {
                return [
                    'product_id' => $group->first()->product_id,
                    'total_quantity' => $group->sum('quantity'),
                    'transfers' => $group->map(function($t) {
                        return [
                            'transfer_id' => $t->transfer_id,
                            'order_number' => $t->order_number,
                            'quantity' => $t->quantity
                        ];
                    })->toArray()
                ];
            })->values()->toArray(),
            'productos' => array_map(function($p) {
                return [
                    'id' => $p['id'],
                    'nombre' => $p['nombre'],
                    'stock' => $p['stock']
                ];
            }, $productsWithStock)
        ]);
        
        return response()->json($productsWithStock);
    }
}
