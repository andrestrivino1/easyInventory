<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\TransferOrder;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Cargar relación almacenes si es funcionario o cliente
        if (in_array($user->rol, ['funcionario', 'clientes']) && !$user->relationLoaded('almacenes')) {
            $user->load('almacenes');
        }
        
        // Admin y funcionario ven todas las transferencias
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->orderByDesc('date')
                ->get();
        } elseif ($user->rol === 'clientes') {
            // Clientes ven transferencias hacia sus bodegas asignadas
            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
            if (empty($bodegasAsignadasIds)) {
                $bodegasAsignadasIds = [];
            }
            
            if (!empty($bodegasAsignadasIds)) {
                $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                    ->whereIn('warehouse_to_id', $bodegasAsignadasIds)
                    ->orderByDesc('date')
                    ->get();
            } else {
                // Si no tiene bodegas asignadas, no mostrar transferencias
                $transferOrders = collect();
            }
        } else {
            // Otros roles filtran por su bodega
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->where(function($query) use ($user) {
                    $query->where('warehouse_from_id', $user->almacen_id)
                          ->orWhere('warehouse_to_id', $user->almacen_id);
                })
                ->orderByDesc('date')
                ->get();
        }
        
        $canCreateTransfer = in_array($user->rol, ['admin', 'funcionario']) || 
            ($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id));
        
        // Pasar el usuario con las relaciones cargadas a la vista
        return view('transfer-orders.index', compact('transferOrders', 'canCreateTransfer', 'user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        
        // Admin, funcionario o usuarios de bodegas que reciben contenedores pueden crear transferencias
        if (!in_array($user->rol, ['admin', 'funcionario']) && 
            !($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id))) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias. Solo las bodegas que reciben contenedores pueden crear transferencias.');
        }
        
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::with('containers')->orderBy('nombre')->get();
        $drivers = \App\Models\Driver::where('active', true)->orderBy('name')->get();
        return view('transfer-orders.create', compact('warehouses', 'products', 'drivers'));
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
        
        // Admin, funcionario o usuarios de bodegas que reciben contenedores pueden crear transferencias
        if (!in_array($user->rol, ['admin', 'funcionario']) && 
            !($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id))) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias. Solo las bodegas que reciben contenedores pueden crear transferencias.');
        }
        
        $data = $request->validate([
            'warehouse_from_id' => 'required|different:warehouse_to_id|exists:warehouses,id',
            'warehouse_to_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'driver_id' => 'required|exists:drivers,id',
            'aprobo' => 'nullable|string|max:255',
            'ciudad_destino' => 'nullable|string|max:255',
        ]);
        
        DB::beginTransaction();
        try {
            // Validar y preparar productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                // Obtener producto de la bodega de origen
                // Los productos son globales, buscar solo por ID
                $product = Product::where('id', $productData['product_id'])
                    ->lockForUpdate()
                    ->first();
                
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe.")->withInput();
                }
                
                // Validar contenedor
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El contenedor no existe.")->withInput();
                }
                
                // Validar que el producto esté en el contenedor
                $productInContainer = $container->products()->where('products.id', $productData['product_id'])->first();
                if (!$productInContainer) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor seleccionado.")->withInput();
                }
                
                // Validar que desde bodegas que reciben contenedores solo se despachen Cajas
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id']) && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    $warehouse = Warehouse::find($data['warehouse_from_id']);
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde " . ($warehouse ? $warehouse->nombre : 'bodegas que reciben contenedores') . " solo se pueden despachar productos medidos en Cajas.")->withInput();
                }
                
                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock según el tipo de bodega
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    // Para bodegas que reciben contenedores, validar cajas en contenedor
                    $pivot = DB::table('container_product')
                        ->join('containers', 'container_product.container_id', '=', 'containers.id')
                        ->where('container_product.container_id', $productData['container_id'])
                        ->where('container_product.product_id', $productData['product_id'])
                        ->where('containers.warehouse_id', $data['warehouse_from_id'])
                        ->select('container_product.boxes')
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$pivot) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor o el contenedor no pertenece a esta bodega.")->withInput();
                    }
                    
                    if ($pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$pivot->boxes} cajas.")->withInput();
                    }
                } else {
                    // Para otras bodegas, calcular stock desde transferencias recibidas menos salidas
                    $stock = 0;
                    
                    // Sumar transferencias recibidas
                    $receivedTransfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $data['warehouse_from_id'])
                        ->whereHas('products', function($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with(['products' => function($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity');
                        }])
                        ->get();
                    
                    foreach ($receivedTransfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            $quantity = $productInTransfer->pivot->quantity;
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $quantity = $quantity * $product->unidades_por_caja;
                            }
                            $stock += $quantity;
                        }
                    }
                    
                    // Descontar salidas
                    $salidas = \App\Models\Salida::where('warehouse_id', $data['warehouse_from_id'])
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
                    
                    // Validar stock disponible
                    if ($stock < $unidadesADescontar) {
                        DB::rollBack();
                        $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0 
                            ? floor($stock / $product->unidades_por_caja) 
                            : 0;
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$stock} unidades).")->withInput();
                    }
                }
                
                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                ];
            }
            
            // Validar conductor
            $driver = \App\Models\Driver::find($data['driver_id']);
            if (!$driver) {
                DB::rollBack();
                return back()->with('error', "El conductor seleccionado no existe.")->withInput();
            }
            
            // Obtener las ciudades de las bodegas
            $warehouseFrom = Warehouse::find($data['warehouse_from_id']);
            $warehouseTo = Warehouse::find($data['warehouse_to_id']);
            
            // Crear la transferencia
            $transfer = TransferOrder::create([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $warehouseFrom->ciudad ?? $warehouseFrom->nombre,
                'destino' => $warehouseTo->ciudad ?? $warehouseTo->nombre,
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
                'aprobo' => $data['aprobo'] ?? null,
                'ciudad_destino' => $data['ciudad_destino'] ?? null,
            ]);
            
            // Descontar stock y asociar productos
            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde bodegas que reciben contenedores)
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    $rowsAffected = DB::table('container_product')
                        ->where('container_id', $item['container_id'])
                        ->where('product_id', $item['product_id'])
                        ->decrement('boxes', $item['quantity']);
                    
                    if ($rowsAffected === 0) {
                        DB::rollBack();
                        return back()->with('error', "Error al descontar del contenedor para el producto #" . ($index + 1))->withInput();
                    }
                    
                    \Log::info('TRANSFER store - Descontado del contenedor', [
                        'container_id' => $item['container_id'],
                        'product_id' => $item['product_id'],
                        'cajas_descontadas' => $item['quantity'],
                        'rows_affected' => $rowsAffected
                    ]);
                }
                
                // PASO 2: Los productos son globales - el stock se calcula dinámicamente
                // No necesitamos descontar del stock del producto aquí
                // Solo descontamos del contenedor si es bodega que recibe contenedores (ya hecho arriba)
                
                \Log::info('TRANSFER store - Descontado del producto', [
                    'product_id' => $item['product_id'],
                    'unidades_descontadas' => $item['unidades_a_descontar'],
                    'rows_affected' => $rowsAffected
                ]);
                
                // Asociar producto a la transferencia
                $transfer->products()->attach($item['product_id'], [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id']
                ]);
            }
            
            DB::commit();
            \Log::info('TRANSFER store - Transferencia creada exitosamente', ['transfer_id' => $transfer->id]);
            
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia creada correctamente.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER store - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', "Error al crear la transferencia: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para editar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden editar transferencias en tránsito.');
        }
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::with('containers')->orderBy('nombre')->get();
        $drivers = \App\Models\Driver::where('active', true)->orderBy('name')->get();
        return view('transfer-orders.edit', compact('transferOrder', 'warehouses', 'products', 'drivers'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TransferOrder $transferOrder)
    {
        $user = Auth::user();
        if ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para editar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden editar transferencias en tránsito.');
        }
        
        $data = $request->validate([
            'warehouse_from_id' => 'required|different:warehouse_to_id|exists:warehouses,id',
            'warehouse_to_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'driver_id' => 'required|exists:drivers,id',
        ]);
        
        DB::beginTransaction();
        try {
            $almacenOrigenAnterior = $transferOrder->warehouse_from_id;
            
            // Los productos son globales - restaurar solo las cajas del contenedor si es necesario
            foreach ($transferOrder->products as $oldProduct) {
                $prodAnterior = Product::where('id', $oldProduct->id)
                    ->lockForUpdate()
                    ->first();
                
                // Si es desde bodegas que reciben contenedores, restaurar las cajas del contenedor
                if ($prodAnterior && Warehouse::bodegaRecibeContenedores($almacenOrigenAnterior) && $oldProduct->pivot->container_id) {
                        DB::table('container_product')
                            ->where('container_id', $oldProduct->pivot->container_id)
                            ->where('product_id', $oldProduct->id)
                            ->increment('boxes', $oldProduct->pivot->quantity);
                }
            }
            
            // Validar y preparar nuevos productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                // Los productos son globales, buscar solo por ID
                $product = Product::where('id', $productData['product_id'])
                    ->lockForUpdate()
                    ->first();
                
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe.")->withInput();
                }
                
                // Validar contenedor
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El contenedor no existe.")->withInput();
                }
                
                // Validar que el producto esté en el contenedor
                $productInContainer = $container->products()->where('products.id', $productData['product_id'])->first();
                if (!$productInContainer) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor seleccionado.")->withInput();
                }
                
                // Validar que desde bodegas que reciben contenedores solo se despachen Cajas
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id']) && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    $warehouse = Warehouse::find($data['warehouse_from_id']);
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde " . ($warehouse ? $warehouse->nombre : 'bodegas que reciben contenedores') . " solo se pueden despachar productos medidos en Cajas.")->withInput();
                }
                
                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock según el tipo de bodega
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    // Para bodegas que reciben contenedores, validar cajas en contenedor
                    $pivot = DB::table('container_product')
                        ->join('containers', 'container_product.container_id', '=', 'containers.id')
                        ->where('container_product.container_id', $productData['container_id'])
                        ->where('container_product.product_id', $productData['product_id'])
                        ->where('containers.warehouse_id', $data['warehouse_from_id'])
                        ->select('container_product.boxes')
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$pivot) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor o el contenedor no pertenece a esta bodega.")->withInput();
                    }
                    
                    if ($pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$pivot->boxes} cajas.")->withInput();
                    }
                } else {
                    // Para otras bodegas, calcular stock desde transferencias recibidas menos salidas
                    $stock = 0;
                    
                    // Sumar transferencias recibidas
                    $receivedTransfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $data['warehouse_from_id'])
                        ->whereHas('products', function($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with(['products' => function($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('quantity');
                        }])
                        ->get();
                    
                    foreach ($receivedTransfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer) {
                            $quantity = $productInTransfer->pivot->quantity;
                            if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                                $quantity = $quantity * $product->unidades_por_caja;
                            }
                            $stock += $quantity;
                        }
                    }
                    
                    // Descontar salidas
                    $salidas = \App\Models\Salida::where('warehouse_id', $data['warehouse_from_id'])
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
                    
                    // Validar stock disponible
                    if ($stock < $unidadesADescontar) {
                        DB::rollBack();
                        $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0 
                            ? floor($stock / $product->unidades_por_caja) 
                            : 0;
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$stock} unidades).")->withInput();
                    }
                }
                
                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                ];
            }
            
            // Validar conductor
            $driver = \App\Models\Driver::find($data['driver_id']);
            if (!$driver) {
                DB::rollBack();
                return back()->with('error', "El conductor seleccionado no existe.")->withInput();
            }
            
            // Obtener las ciudades de las bodegas
            $warehouseFrom = Warehouse::find($data['warehouse_from_id']);
            $warehouseTo = Warehouse::find($data['warehouse_to_id']);
            
            // Actualizar la transferencia
            $transferOrder->update([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $warehouseFrom->ciudad ?? $warehouseFrom->nombre,
                'destino' => $warehouseTo->ciudad ?? $warehouseTo->nombre,
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
            ]);
            
            // Descontar stock y asociar productos
            $syncData = [];
            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde bodegas que reciben contenedores)
                if (Warehouse::bodegaRecibeContenedores($data['warehouse_from_id'])) {
                    DB::table('container_product')
                        ->where('container_id', $item['container_id'])
                        ->where('product_id', $item['product_id'])
                        ->decrement('boxes', $item['quantity']);
                }
                
                // PASO 2: Los productos son globales - el stock se calcula dinámicamente
                // No necesitamos descontar del stock del producto aquí
                // Solo descontamos del contenedor si es bodega que recibe contenedores (ya hecho arriba)
                
                $syncData[$item['product_id']] = [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id']
                ];
            }
            
            $transferOrder->products()->sync($syncData);
            
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia actualizada correctamente.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER update - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', "Error al actualizar la transferencia: " . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        // Admin puede eliminar cualquier transferencia, otros solo las de su bodega
        if ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para eliminar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden eliminar transferencias en tránsito.');
        }
        
        DB::beginTransaction();
        try {
            // Los productos son globales - restaurar solo las cajas del contenedor si es necesario
            foreach ($transferOrder->products as $product) {
                $prod = Product::where('id', $product->id)
                    ->lockForUpdate()
                    ->first();
                
                // Si es desde bodegas que reciben contenedores, restaurar las cajas del contenedor
                if ($prod && Warehouse::bodegaRecibeContenedores($transferOrder->warehouse_from_id) && $product->pivot->container_id) {
                        DB::table('container_product')
                            ->where('container_id', $product->pivot->container_id)
                            ->where('product_id', $product->id)
                            ->increment('boxes', $product->pivot->quantity);
                }
            }
            
            $transferOrder->delete();
            
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia eliminada correctamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER destroy - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', "Error al eliminar la transferencia: " . $e->getMessage());
        }
    }

    /**
     * Confirmar recepción de transferencia
     */
    public function confirmReceived(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        // Solo se puede confirmar si la transferencia está en tránsito
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Esta transferencia ya fue procesada.');
        }
        
        // Admin y funcionario pueden confirmar desde cualquier lugar
        $canConfirm = false;
        $errorMessage = 'Solo se puede confirmar la recepción en la bodega destino.';
        
        if (in_array($user->rol, ['admin', 'funcionario'])) {
            $canConfirm = true;
        } elseif ($user->rol === 'clientes') {
            // Clientes pueden confirmar si la bodega destino está en sus bodegas asignadas
            if (!$user->relationLoaded('almacenes')) {
                $user->load('almacenes');
            }
            $bodegasAsignadasIds = $user->almacenes->pluck('id')->toArray();
            $canConfirm = in_array($transferOrder->warehouse_to_id, $bodegasAsignadasIds);
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tus bodegas asignadas.';
            }
        } else {
            // Otros roles solo pueden confirmar si es su bodega
            $canConfirm = $user->almacen_id == $transferOrder->warehouse_to_id;
            if (!$canConfirm) {
                $errorMessage = 'Solo puedes confirmar transferencias hacia tu bodega asignada.';
            }
        }
        
        if (!$canConfirm) {
            return redirect()->route('transfer-orders.index')->with('error', $errorMessage);
        }
        
        DB::beginTransaction();
        try {
            // Los productos ahora son globales, no necesitamos crear productos en la bodega destino
            // El stock se calculará dinámicamente desde las transferencias recibidas
            // Solo marcamos la transferencia como recibida
            
            $transferOrder->update(['status' => 'recibido']);
            
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia confirmada correctamente. El stock se calculará automáticamente desde las transferencias recibidas.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('TRANSFER confirmReceived - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return back()->with('error', "Error al confirmar la transferencia: " . $e->getMessage());
        }
    }

    /**
     * Exportar transferencia a PDF
     */
    public function export(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        $transferOrder->load(['from', 'to', 'products' => function($query) {
            $query->withPivot('quantity', 'container_id');
        }, 'driver']);
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $currentUser = $user;
        $isExport = true;
        $pdf = \PDF::loadView('transfer-orders.pdf', compact('transferOrder', 'showSignatures', 'currentUser', 'isExport'));
        return $pdf->download("transferencia_{$transferOrder->id}.pdf");
    }

    /**
     * Imprimir transferencia
     */
    public function print(TransferOrder $transferOrder)
    {
        $user = Auth::user();
        $transferOrder->load(['from', 'to', 'products' => function($query) {
            $query->withPivot('quantity', 'container_id');
        }, 'driver']);
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $currentUser = $user;
        $isExport = false;
        return view('transfer-orders.pdf', compact('transferOrder', 'showSignatures', 'currentUser', 'isExport'));
    }

    /**
     * Obtener productos disponibles en una bodega específica
     */
    public function getProductsForWarehouse(Request $request, $warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $bodegasQueRecibenContenedores = Warehouse::getBodegasQueRecibenContenedores();
        
        // Obtener todos los productos globales
        $allProducts = Product::whereNull('almacen_id')
            ->with(['containers' => function($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }])
            ->orderBy('nombre')
            ->get();
        
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
                
                // Descontar salidas para bodegas que reciben contenedores
                $salidas = \App\Models\Salida::where('warehouse_id', $warehouseId)
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
            } else {
                // Otra bodega: stock desde transferencias recibidas menos salidas
                $receivedTransfers = TransferOrder::where('status', 'recibido')
                    ->where('warehouse_to_id', $warehouseId)
                    ->whereHas('products', function($query) use ($product) {
                        $query->where('products.id', $product->id);
                    })
                    ->with(['products' => function($query) use ($product) {
                        $query->where('products.id', $product->id)->withPivot('quantity');
                    }])
                    ->get();
                
                foreach ($receivedTransfers as $transfer) {
                    $productInTransfer = $transfer->products->first();
                    if ($productInTransfer) {
                        $quantity = $productInTransfer->pivot->quantity;
                        // Si es tipo caja, convertir a unidades
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $quantity = $quantity * $product->unidades_por_caja;
                        }
                        $stock += $quantity;
                    }
                }
                
                // Descontar salidas
                $salidas = \App\Models\Salida::where('warehouse_id', $warehouseId)
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
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $quantity = $quantity * $product->unidades_por_caja;
                        }
                        $stock -= $quantity;
                    }
                }
            }
            
            // Para bodegas que reciben contenedores, crear una entrada por cada combinación producto-contenedor
            if (in_array($warehouseId, $bodegasQueRecibenContenedores)) {
                $containerProducts = DB::table('container_product')
                    ->join('containers', 'container_product.container_id', '=', 'containers.id')
                    ->where('container_product.product_id', $product->id)
                    ->where('containers.warehouse_id', $warehouseId)
                    ->where('container_product.boxes', '>', 0)
                    ->select('container_product.container_id', 'container_product.boxes', 'container_product.sheets_per_box', 'containers.reference')
                    ->get();
                
                // Crear una entrada por cada contenedor con stock
                // Cada entrada debe mostrar el stock específico de su contenedor
                // Calcular stock total sin descontar para distribución proporcional de salidas
                $stockTotalSinDescontar = 0;
                foreach ($containerProducts as $cp2) {
                    $stockTotalSinDescontar += ($cp2->boxes ?? 0) * ($cp2->sheets_per_box ?? 0);
                }
                
                foreach ($containerProducts as $cp) {
                    $stockContenedor = ($cp->boxes ?? 0) * ($cp->sheets_per_box ?? 0);
                    if ($stockContenedor > 0) {
                        // Calcular el stock del contenedor descontando salidas proporcionalmente
                        $stockContenedorFinal = $stockContenedor;
                        if ($stockTotalSinDescontar > 0 && $stock < $stockTotalSinDescontar) {
                            // Hay salidas, calcular proporción
                            $salidasTotales = $stockTotalSinDescontar - $stock;
                            $proporcion = $stockContenedor / $stockTotalSinDescontar;
                            $salidasDelContenedor = $salidasTotales * $proporcion;
                            $stockContenedorFinal = max(0, $stockContenedor - $salidasDelContenedor);
                        }
                        
                        $productsWithStock[] = [
                            'id' => $product->id,
                            'nombre' => $product->nombre,
                            'codigo' => $product->codigo,
                            'medidas' => $product->medidas,
                            'tipo_medida' => $product->tipo_medida,
                            'unidades_por_caja' => $product->unidades_por_caja,
                            'stock' => $stockContenedorFinal, // Stock específico del contenedor (descontando salidas proporcionalmente)
                            'cajas_en_contenedor' => $cp->boxes,
                            'sheets_per_box' => $cp->sheets_per_box,
                            'containers' => [[
                                'id' => $cp->container_id,
                                'reference' => $cp->reference,
                                'stock' => $stockContenedorFinal,
                                'boxes' => $cp->boxes
                            ]]
                        ];
                    }
                }
            } else {
                // Para otras bodegas, incluir productos con stock > 0
                if ($stock > 0) {
                    // Obtener contenedores relacionados con este producto en esta bodega
                    $containers = [];
                    // Para otras bodegas, obtener contenedores de transferencias recibidas
                    $transfers = TransferOrder::where('status', 'recibido')
                        ->where('warehouse_to_id', $warehouseId)
                        ->whereHas('products', function($query) use ($product) {
                            $query->where('products.id', $product->id);
                        })
                        ->with(['products' => function($query) use ($product) {
                            $query->where('products.id', $product->id)->withPivot('container_id');
                        }])
                        ->get();
                    
                    $containerIds = [];
                    foreach ($transfers as $transfer) {
                        $productInTransfer = $transfer->products->first();
                        if ($productInTransfer && $productInTransfer->pivot->container_id) {
                            $containerIds[] = $productInTransfer->pivot->container_id;
                        }
                    }
                    
                    if (!empty($containerIds)) {
                        $containersData = \App\Models\Container::whereIn('id', array_unique($containerIds))
                            ->select('id', 'reference')
                            ->get();
                        
                        foreach ($containersData as $container) {
                            $containers[] = [
                                'id' => $container->id,
                                'reference' => $container->reference
                            ];
                        }
                    }
                    
                    $productsWithStock[] = [
                        'id' => $product->id,
                        'nombre' => $product->nombre,
                        'codigo' => $product->codigo,
                        'medidas' => $product->medidas,
                        'tipo_medida' => $product->tipo_medida,
                        'unidades_por_caja' => $product->unidades_por_caja,
                        'stock' => max(0, $stock),
                        'containers' => $containers
                    ];
                }
            }
        }
        
        return response()->json($productsWithStock);
    }
}
