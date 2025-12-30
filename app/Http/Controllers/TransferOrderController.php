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
        $ID_PABLO_ROJAS = 1;
        
        // Si el usuario no es admin ni secretaria, filtrar por su bodega
        if (!in_array($user->rol, ['admin', 'secretaria'])) {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->where(function($query) use ($user, $ID_PABLO_ROJAS) {
                    // Ver transferencias desde su bodega o hacia su bodega
                    // Si es funcionario, solo ver Pablo Rojas
                    if ($user->rol === 'funcionario') {
                        $query->where('warehouse_from_id', $ID_PABLO_ROJAS)
                              ->orWhere('warehouse_to_id', $ID_PABLO_ROJAS);
                    } else {
                        $query->where('warehouse_from_id', $user->almacen_id)
                              ->orWhere('warehouse_to_id', $user->almacen_id);
                    }
                })
                ->orderByDesc('date')
                ->get();
        } else {
            $transferOrders = TransferOrder::with(['from', 'to', 'products', 'driver'])
                ->orderByDesc('date')
                ->get();
        }
        
        $canCreateTransfer = in_array($user->rol, ['admin', 'secretaria']) || $user->almacen_id == $ID_PABLO_ROJAS;
        
        return view('transfer-orders.index', compact('transferOrders', 'canCreateTransfer'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        $ID_PABLO_ROJAS = 1;
        
        // Solo admin, secretaria o usuarios de Pablo Rojas pueden crear transferencias
        if (!in_array($user->rol, ['admin', 'secretaria']) && $user->almacen_id != $ID_PABLO_ROJAS) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias. Solo la bodega principal puede crear transferencias.');
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
        $ID_PABLO_ROJAS = 1;
        
        // Solo admin, secretaria o usuarios de Pablo Rojas pueden crear transferencias
        if (!in_array($user->rol, ['admin', 'secretaria']) && $user->almacen_id != $ID_PABLO_ROJAS) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para crear transferencias. Solo la bodega principal puede crear transferencias.');
        }
        
        $data = $request->validate([
            'warehouse_from_id' => 'required|different:warehouse_to_id|exists:warehouses,id',
            'warehouse_to_id' => 'required|exists:warehouses,id',
            'salida' => 'required|string|max:255',
            'destino' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'driver_id' => 'required|exists:drivers,id',
        ]);
        
        DB::beginTransaction();
        try {
            // Validar y preparar productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                // Obtener producto de la bodega de origen
                $product = Product::where('id', $productData['product_id'])
                    ->where('almacen_id', $data['warehouse_from_id'])
                    ->lockForUpdate()
                    ->first();
                
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe en la bodega de origen.")->withInput();
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
                
                // Validar que desde Pablo Rojas solo se despachen Cajas
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde Pablo Rojas solo se pueden despachar productos medidos en Cajas.")->withInput();
                }
                
                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock del producto
                if ($product->stock < $unidadesADescontar) {
                    DB::rollBack();
                    $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0 
                        ? floor($product->stock / $product->unidades_por_caja) 
                        : 0;
                    return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas ({$product->stock} unidades).")->withInput();
                }
                
                // Si es desde Pablo Rojas, validar cajas en contenedor
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS) {
                    $pivot = DB::table('container_product')
                        ->where('container_id', $productData['container_id'])
                        ->where('product_id', $productData['product_id'])
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$pivot) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . ": El producto no está asociado al contenedor.")->withInput();
                    }
                    
                    if ($pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$pivot->boxes} cajas.")->withInput();
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
            
            // Crear la transferencia
            $transfer = TransferOrder::create([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $data['salida'],
                'destino' => $data['destino'],
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
            ]);
            
            // Descontar stock y asociar productos
            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde Pablo Rojas)
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS) {
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
                
                // PASO 2: Descontar del stock del producto
                $rowsAffected = DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['unidades_a_descontar']);
                
                if ($rowsAffected === 0) {
                    DB::rollBack();
                    return back()->with('error', "Error al descontar del stock para el producto #" . ($index + 1))->withInput();
                }
                
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
            'salida' => 'required|string|max:255',
            'destino' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.container_id' => 'required|exists:containers,id',
            'products.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'driver_id' => 'required|exists:drivers,id',
        ]);
        
        DB::beginTransaction();
        try {
            $ID_PABLO_ROJAS = 1;
            $almacenOrigenAnterior = $transferOrder->warehouse_from_id;
            
            // Restaurar stock de productos anteriores
            foreach ($transferOrder->products as $oldProduct) {
                $prodAnterior = Product::where('id', $oldProduct->id)
                    ->where('almacen_id', $almacenOrigenAnterior)
                    ->lockForUpdate()
                    ->first();
                
                if ($prodAnterior) {
                    $unidadesARestaurar = $oldProduct->pivot->quantity;
                    if ($prodAnterior->tipo_medida === 'caja' && $prodAnterior->unidades_por_caja > 0) {
                        $unidadesARestaurar = $oldProduct->pivot->quantity * $prodAnterior->unidades_por_caja;
                    }
                    
                    DB::table('products')
                        ->where('id', $prodAnterior->id)
                        ->increment('stock', $unidadesARestaurar);
                    
                    // Si es desde Pablo Rojas, restaurar también las cajas del contenedor
                    if ($almacenOrigenAnterior == $ID_PABLO_ROJAS && $oldProduct->pivot->container_id) {
                        DB::table('container_product')
                            ->where('container_id', $oldProduct->pivot->container_id)
                            ->where('product_id', $oldProduct->id)
                            ->increment('boxes', $oldProduct->pivot->quantity);
                    }
                }
            }
            
            // Validar y preparar nuevos productos
            $productsToAttach = [];
            foreach ($data['products'] as $index => $productData) {
                $product = Product::where('id', $productData['product_id'])
                    ->where('almacen_id', $data['warehouse_from_id'])
                    ->lockForUpdate()
                    ->first();
                
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto no existe en la bodega de origen.")->withInput();
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
                
                // Validar que desde Pablo Rojas solo se despachen Cajas
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS && $product->tipo_medida !== 'caja') {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": Desde Pablo Rojas solo se pueden despachar productos medidos en Cajas.")->withInput();
                }
                
                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock
                if ($product->stock < $unidadesADescontar) {
                    DB::rollBack();
                    $cajasDisponibles = $product->tipo_medida === 'caja' && $product->unidades_por_caja > 0 
                        ? floor($product->stock / $product->unidades_por_caja) 
                        : 0;
                    return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$cajasDisponibles} cajas.")->withInput();
                }
                
                // Si es desde Pablo Rojas, validar cajas en contenedor
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS) {
                    $pivot = DB::table('container_product')
                        ->where('container_id', $productData['container_id'])
                        ->where('product_id', $productData['product_id'])
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$pivot || $pivot->boxes < $productData['quantity']) {
                        DB::rollBack();
                        $cajasDisponibles = $pivot ? $pivot->boxes : 0;
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): No hay suficientes cajas en el contenedor. Disponible: {$cajasDisponibles} cajas.")->withInput();
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
            
            // Actualizar la transferencia
            $transferOrder->update([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'salida' => $data['salida'],
                'destino' => $data['destino'],
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
            ]);
            
            // Descontar stock y asociar productos
            $syncData = [];
            foreach ($productsToAttach as $index => $item) {
                // PASO 1: Descontar del contenedor (solo si es desde Pablo Rojas)
                if ($data['warehouse_from_id'] == $ID_PABLO_ROJAS) {
                    DB::table('container_product')
                        ->where('container_id', $item['container_id'])
                        ->where('product_id', $item['product_id'])
                        ->decrement('boxes', $item['quantity']);
                }
                
                // PASO 2: Descontar del stock del producto
                DB::table('products')
                    ->where('id', $item['product_id'])
                    ->decrement('stock', $item['unidades_a_descontar']);
                
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
        if ($user->rol !== 'admin' && $transferOrder->warehouse_from_id !== $user->almacen_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para eliminar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se pueden eliminar transferencias en tránsito.');
        }
        
        DB::beginTransaction();
        try {
            $ID_PABLO_ROJAS = 1;
            
            // Restaurar stock de productos
            foreach ($transferOrder->products as $product) {
                $prod = Product::where('id', $product->id)
                    ->where('almacen_id', $transferOrder->warehouse_from_id)
                    ->lockForUpdate()
                    ->first();
                
                if ($prod) {
                    $unidadesARestaurar = $product->pivot->quantity;
                    if ($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                        $unidadesARestaurar = $product->pivot->quantity * $prod->unidades_por_caja;
                    }
                    
                    DB::table('products')
                        ->where('id', $prod->id)
                        ->increment('stock', $unidadesARestaurar);
                    
                    // Si es desde Pablo Rojas, restaurar también las cajas del contenedor
                    if ($transferOrder->warehouse_from_id == $ID_PABLO_ROJAS && $product->pivot->container_id) {
                        DB::table('container_product')
                            ->where('container_id', $product->pivot->container_id)
                            ->where('product_id', $product->id)
                            ->increment('boxes', $product->pivot->quantity);
                    }
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
        $ID_PABLO_ROJAS = 1;
        
        // Solo se puede confirmar si la transferencia está en tránsito
        if ($transferOrder->status !== 'en_transito') {
            return redirect()->route('transfer-orders.index')->with('error', 'Esta transferencia ya fue procesada.');
        }
        
        // Solo se puede confirmar en la bodega destino
        if ($user->rol !== 'admin' && $user->almacen_id != $transferOrder->warehouse_to_id) {
            return redirect()->route('transfer-orders.index')->with('error', 'Solo se puede confirmar la recepción en la bodega destino.');
        }
        
        DB::beginTransaction();
        try {
            foreach ($transferOrder->products as $product) {
                // Buscar si ya existe un producto con el mismo código en la bodega destino
                $existingProduct = Product::where('codigo', $product->codigo)
                    ->where('almacen_id', $transferOrder->warehouse_to_id)
                    ->first();
                
                if ($existingProduct) {
                    // Si existe, actualizar el stock
                    $quantity = $product->pivot->quantity;
                    if ($existingProduct->tipo_medida === 'caja' && $existingProduct->unidades_por_caja > 0) {
                        $quantity = $product->pivot->quantity * $existingProduct->unidades_por_caja;
                    }
                    
                    DB::table('products')
                        ->where('id', $existingProduct->id)
                        ->increment('stock', $quantity);
                } else {
                    // Si no existe, crear un nuevo producto
                    $newProduct = Product::create([
                        'codigo' => $product->codigo,
                        'nombre' => $product->nombre,
                        'medidas' => $product->medidas,
                        'tipo_medida' => $product->tipo_medida,
                        'unidades_por_caja' => $product->unidades_por_caja,
                        'stock' => $product->pivot->quantity * ($product->unidades_por_caja ?? 1),
                        'almacen_id' => $transferOrder->warehouse_to_id,
                        'estado' => true,
                        'precio' => $product->precio ?? 0,
                    ]);
                }
            }
            
            $transferOrder->update(['status' => 'recibido']);
            
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia confirmada correctamente.');
            
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
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $pdf = \PDF::loadView('transfer-orders.pdf', compact('transferOrder', 'showSignatures'));
        return $pdf->download("transferencia_{$transferOrder->id}.pdf");
    }

    /**
     * Imprimir transferencia
     */
    public function print(TransferOrder $transferOrder)
    {
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        return view('transfer-orders.pdf', compact('transferOrder', 'showSignatures'));
    }
}
