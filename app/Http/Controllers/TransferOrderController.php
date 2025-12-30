<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\TransferOrder;
use App\Models\Container;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

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
        $ID_BUENAVENTURA = 1;
        
        if (in_array($user->rol, ['admin', 'secretaria'])) {
            $transferOrders = \App\Models\TransferOrder::with([
                'from', 
                'to', 
                'products' => function($query) {
                    $query->withPivot('quantity', 'container_id');
                }, 
                'driver'
            ])->orderByDesc('date')->get();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve transferencias relacionadas con Buenaventura
            $transferOrders = \App\Models\TransferOrder::with([
                'from', 
                'to', 
                'products' => function($query) {
                    $query->withPivot('quantity', 'container_id');
                }, 
                'driver'
            ])
                ->where('warehouse_from_id', $ID_BUENAVENTURA)
                ->orWhere('warehouse_to_id', $ID_BUENAVENTURA)
                ->orderByDesc('date')->get();
        } else {
            $transferOrders = \App\Models\TransferOrder::with([
                'from', 
                'to', 
                'products' => function($query) {
                    $query->withPivot('quantity', 'container_id');
                }, 
                'driver'
            ])
                ->where('warehouse_from_id', $user->almacen_id)
                ->orWhere('warehouse_to_id', $user->almacen_id)
                ->orderByDesc('date')->get();
        }
        
        // Cargar contenedores de forma eficiente
        $containerIds = collect();
        foreach ($transferOrders as $transfer) {
            foreach ($transfer->products as $product) {
                if ($product->pivot->container_id) {
                    $containerIds->push($product->pivot->container_id);
                }
            }
        }
        $containers = $containerIds->isNotEmpty() 
            ? Container::whereIn('id', $containerIds->unique())->get()->keyBy('id')
            : collect();
        
        return view('transfer-orders.index', compact('transferOrders', 'containers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
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
        
        \Log::info('TRANSFER store - datos validados', $data);
        DB::beginTransaction();
        try {
            $ID_BUENAVENTURA = 1;
            $productsToAttach = [];
            
            // Validar cada producto
            foreach ($data['products'] as $productData) {
                $product = Product::where('id', $productData['product_id'])->where('almacen_id', $data['warehouse_from_id'])->first();
                if (!$product) {
                    return back()->with('error', "El producto seleccionado no existe en el almacén de origen.")->withInput();
                }
                
                // Validar que el producto esté en el contenedor seleccionado
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    return back()->with('error', "El contenedor seleccionado no existe.")->withInput();
                }
                
                $productInContainer = $container->products()->where('products.id', $productData['product_id'])->first();
                if (!$productInContainer) {
                    return back()->with('error', "El producto '{$product->nombre}' no está asociado al contenedor '{$container->reference}'.")->withInput();
                }
                
                // Validar que desde Buenaventura solo se despachen Cajas
                if ($data['warehouse_from_id'] == $ID_BUENAVENTURA && $product->tipo_medida !== 'caja') {
                    return back()->with('error', "Desde el almacén de Buenaventura solo se pueden despachar productos medidos en Cajas. El producto '{$product->nombre}' es tipo Unidad.")->withInput();
                }
                
                // Calcular unidades a descontar según el tipo de medida
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock
                if ($product->stock < $unidadesADescontar) {
                    if ($product->tipo_medida === 'caja') {
                        $cajasDisponibles = floor($product->stock / $product->unidades_por_caja);
                        return back()->with('error', "Stock insuficiente para '{$product->nombre}'. Solo hay {$cajasDisponibles} cajas disponibles (stock: {$product->stock} unidades).")->withInput();
                    } else {
                        return back()->with('error', "Stock insuficiente para '{$product->nombre}'. Stock disponible: {$product->stock} unidades.")->withInput();
                    }
                }
                
                // Preparar para descontar stock y asociar
                $productsToAttach[] = [
                    'product' => $product,
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                ];
            }
            
            // Crear la transferencia
            $transfer = TransferOrder::create([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
            ]);
            
            // Descontar stock y asociar productos
            foreach ($productsToAttach as $item) {
                $item['product']->stock -= $item['unidades_a_descontar'];
                $item['product']->save();
                
                // Asegurar que container_id se guarde correctamente
                $pivotData = [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id']
                ];
                
                \Log::info('Attaching product to transfer', [
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'container_id' => $item['container_id'],
                    'quantity' => $item['quantity']
                ]);
                
                $transfer->products()->attach($item['product_id'], $pivotData);
            }
            
            // Guardar preferencia de mostrar firmas en sesión
            if ($request->has('show_signatures')) {
                session(["transfer_signatures_{$transfer->id}" => true]);
            } else {
                session()->forget("transfer_signatures_{$transfer->id}");
            }
            
            DB::commit();
            \Log::info('TRANSFER store - exito', ['transfer_id'=>$transfer->id]);
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('TRANSFER store - exception', ['msg'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            return back()->with('error', 'Ocurrió un error al crear la transferencia.')->withInput();
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
            $ID_BUENAVENTURA = 1;
            $almacenOrigenAnterior = $transferOrder->warehouse_from_id;
            
            // Restaurar stock de productos anteriores
            foreach ($transferOrder->products as $oldProduct) {
                $prodAnterior = Product::where('id', $oldProduct->id)
                    ->where('almacen_id', $almacenOrigenAnterior)
                    ->first();
                
                if ($prodAnterior) {
                    $unidadesARestaurar = $oldProduct->pivot->quantity;
                    if ($prodAnterior->tipo_medida === 'caja' && $prodAnterior->unidades_por_caja > 0) {
                        $unidadesARestaurar = $oldProduct->pivot->quantity * $prodAnterior->unidades_por_caja;
                    }
                    $prodAnterior->stock += $unidadesARestaurar;
                    $prodAnterior->save();
                }
            }
            
            // Validar y preparar nuevos productos
            $productsToAttach = [];
            foreach ($data['products'] as $productData) {
                $product = Product::where('id', $productData['product_id'])->where('almacen_id', $data['warehouse_from_id'])->first();
                if (!$product) {
                    return back()->with('error', "El producto seleccionado no existe en el almacén de origen.")->withInput();
                }
                
                // Validar que el producto esté en el contenedor seleccionado
                $container = \App\Models\Container::find($productData['container_id']);
                if (!$container) {
                    return back()->with('error', "El contenedor seleccionado no existe.")->withInput();
                }
                
                $productInContainer = $container->products()->where('products.id', $productData['product_id'])->first();
                if (!$productInContainer) {
                    return back()->with('error', "El producto '{$product->nombre}' no está asociado al contenedor '{$container->reference}'.")->withInput();
                }
                
                // Validar que desde Buenaventura solo se despachen Cajas
                if ($data['warehouse_from_id'] == $ID_BUENAVENTURA && $product->tipo_medida !== 'caja') {
                    return back()->with('error', "Desde el almacén de Buenaventura solo se pueden despachar productos medidos en Cajas. El producto '{$product->nombre}' es tipo Unidad.")->withInput();
                }
                
                // Calcular unidades a descontar
                $unidadesADescontar = $productData['quantity'];
                if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                    $unidadesADescontar = $productData['quantity'] * $product->unidades_por_caja;
                }
                
                // Validar stock
                if ($product->stock < $unidadesADescontar) {
                    if ($product->tipo_medida === 'caja') {
                        $cajasDisponibles = floor($product->stock / $product->unidades_por_caja);
                        return back()->with('error', "Stock insuficiente para '{$product->nombre}'. Solo hay {$cajasDisponibles} cajas disponibles (stock: {$product->stock} unidades).")->withInput();
                    } else {
                        return back()->with('error', "Stock insuficiente para '{$product->nombre}'. Stock disponible: {$product->stock} unidades.")->withInput();
                    }
                }
                
                $productsToAttach[] = [
                    'product' => $product,
                    'product_id' => $productData['product_id'],
                    'container_id' => $productData['container_id'],
                    'quantity' => $productData['quantity'],
                    'unidades_a_descontar' => $unidadesADescontar,
                ];
            }
            
            // Actualizar la transferencia
            $transferOrder->update([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_id' => $data['driver_id'],
            ]);
            
            // Descontar stock y asociar productos
            $syncData = [];
            foreach ($productsToAttach as $item) {
                $item['product']->stock -= $item['unidades_a_descontar'];
                $item['product']->save();
                $syncData[$item['product_id']] = [
                    'quantity' => $item['quantity'],
                    'container_id' => $item['container_id']
                ];
            }
            $transferOrder->products()->sync($syncData);
            
            // Guardar preferencia de mostrar firmas en sesión
            if ($request->has('show_signatures')) {
                session(["transfer_signatures_{$transferOrder->id}" => true]);
            } else {
                session()->forget("transfer_signatures_{$transferOrder->id}");
            }
            
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('TRANSFER update - exception', ['msg'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            return back()->with('error', 'Ocurrió un error al actualizar la transferencia.')->withInput();
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
            // Regresar el stock al almacén de origen
            foreach ($transferOrder->products as $product) {
                $prod = \App\Models\Product::where('id', $product->id)
                        ->where('almacen_id', $transferOrder->warehouse_from_id)->first();
                if ($prod) {
                    $unidadesARestaurar = $product->pivot->quantity;
                    if ($prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0) {
                        $unidadesARestaurar = $product->pivot->quantity * $prod->unidades_por_caja;
                    }
                    $prod->stock += $unidadesARestaurar;
                    $prod->save();
                }
            }
            $transferOrder->delete();
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success','Transferencia eliminada y stock restaurado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('transfer-orders.index')->with('error','Ocurrió un error al eliminar la transferencia.');
        }
    }

    public function export(TransferOrder $transferOrder, Request $request)
    {
        $transferOrder->load(['from', 'to', 'products']);
        // Cargar contenedores para optimizar consultas
        $containerIds = $transferOrder->products->pluck('pivot.container_id')->filter()->unique();
        $containers = \App\Models\Container::whereIn('id', $containerIds)->get()->keyBy('id');
        $isExport = true;
        // Verificar sesión para mostrar firmas
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        $pdf = Pdf::loadView('transfer-orders.pdf', compact('transferOrder', 'isExport', 'containers', 'showSignatures'));
        $filename = 'Orden-Transferencia-' . $transferOrder->order_number . '.pdf';
        return $pdf->download($filename);
    }

    public function print(TransferOrder $transferOrder, Request $request)
    {
        $transferOrder->load(['from', 'to', 'products']);
        // Cargar contenedores para optimizar consultas
        $containerIds = $transferOrder->products->pluck('pivot.container_id')->filter()->unique();
        $containers = \App\Models\Container::whereIn('id', $containerIds)->get()->keyBy('id');
        $isExport = false; // Indicar que es para visualizar en navegador
        // Verificar sesión para mostrar firmas
        $showSignatures = session("transfer_signatures_{$transferOrder->id}", false);
        return view('transfer-orders.pdf', compact('transferOrder', 'isExport', 'containers', 'showSignatures'));
    }

    public function confirmReceived(TransferOrder $transferOrder)
    {
        // Verificar usuario correcto
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('transfer-orders.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        if (!$user || $user->almacen_id != $transferOrder->warehouse_to_id) {
            return back()->with('error', 'No puedes confirmar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return back()->with('error', 'La transferencia ya fue recibida o no está activa.');
        }
        
        if ($transferOrder->products->isEmpty()) {
            return back()->with('error', 'No se encontraron productos asociados a la transferencia.');
        }
        
        DB::beginTransaction();
        try {
            // Procesar todos los productos de la transferencia
            foreach ($transferOrder->products as $productPivot) {
                // Buscar producto en el almacén destino
                $product = \App\Models\Product::where('id', $productPivot->id)
                    ->where('almacen_id', $transferOrder->warehouse_to_id)
                    ->first();
                
                // Calcular unidades a agregar según el tipo de medida
                $unidadesAAgregar = $productPivot->pivot->quantity;
                if ($productPivot->tipo_medida === 'caja' && $productPivot->unidades_por_caja > 0) {
                    // Si es caja, multiplicar cantidad de cajas por unidades por caja
                    $unidadesAAgregar = $productPivot->pivot->quantity * $productPivot->unidades_por_caja;
                }
                
                if ($product) {
                    // Si el producto existe en el almacén destino, actualizar stock
                    $product->stock += $unidadesAAgregar;
                    $product->save();
                } else {
                    // Si no existe, crear el producto en el almacén destino
                    $product = \App\Models\Product::create([
                        'nombre' => $productPivot->nombre,
                        'codigo' => $productPivot->codigo,
                        'precio' => $productPivot->precio ?? 0,
                        'stock' => $unidadesAAgregar,
                        'estado' => $productPivot->estado,
                        'almacen_id' => $transferOrder->warehouse_to_id,
                        'tipo_medida' => $productPivot->tipo_medida,
                        'unidades_por_caja' => $productPivot->unidades_por_caja,
                        'medidas' => $productPivot->medidas,
                    ]);
                }
            }
            
            $transferOrder->status = 'recibido';
            $transferOrder->save();
            
            DB::commit();
            return back()->with('success', 'Transferencia recibida y stock actualizado correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Ocurrió un error al confirmar la transferencia: ' . $e->getMessage());
        }
    }
}
