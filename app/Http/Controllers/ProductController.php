<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use App\Models\Container;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Productos globales: mostrar todos los productos (sin almacen_id específico)
        $productos = \App\Models\Product::whereNull('almacen_id')
            ->orderBy('nombre')
            ->paginate(10);
        
        // Funcionario no puede crear productos (solo lectura)
        $canCreateProducts = ($user->rol !== 'funcionario') && 
            ($user->rol === 'admin' || 
             ($user->almacen_id && Warehouse::bodegaRecibeContenedores($user->almacen_id)));
        
        return view('products.index', compact('productos', 'canCreateProducts'));
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
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Los productos ahora son globales - todos los usuarios pueden crearlos
        return view('products.create');
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
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Los productos ahora son globales - no requieren almacen_id
        // Si se especifica almacen_id, debe ser una bodega que recibe contenedores y solo puede ser tipo caja
        $almacenId = $request->input('almacen_id');
        if ($almacenId) {
            if (!Warehouse::bodegaRecibeContenedores($almacenId)) {
                return back()->withInput()->with('error', 'Solo puedes crear productos en bodegas que reciben contenedores. Los productos globales no requieren bodega específica.');
            }
            if ($request->input('tipo_medida') !== 'caja') {
                $warehouse = Warehouse::find($almacenId);
                return back()->withInput()->with('error', 'Solo se permiten Cajas en bodegas que reciben contenedores (' . ($warehouse ? $warehouse->nombre : '') . ')');
            }
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'medidas' => 'nullable|string|max:255',
            'calibre' => 'nullable|numeric|min:0',
            'alto' => 'nullable|numeric|min:0',
            'ancho' => 'nullable|numeric|min:0',
            'peso_empaque' => 'nullable|numeric|min:0',
            'tipo_medida' => 'nullable|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean',
        ]);
        
        $data['stock'] = 0;
        $data['estado'] = $data['estado'] ?? true;
        $data['precio'] = 0;
        $data['almacen_id'] = null;
        $data['peso_empaque'] = $data['peso_empaque'] ?? 2.5;
        if (isset($data['tipo_medida']) && $data['tipo_medida'] === '') {
            $data['tipo_medida'] = null;
        }
        
        \App\Models\Product::create($data);
        return redirect()->route('products.index')->with('success', 'Producto global creado correctamente. El stock se asignará automáticamente cuando se agregue a contenedores o se reciban transferencias.');
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
    public function edit($id)
    {
        $user = Auth::user();
        $product = \App\Models\Product::findOrFail($id);
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Los productos son globales - todos los usuarios pueden editarlos
        return view('products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $product = \App\Models\Product::findOrFail($id);
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'medidas' => 'nullable|string|max:255',
            'calibre' => 'nullable|numeric|min:0',
            'alto' => 'nullable|numeric|min:0',
            'ancho' => 'nullable|numeric|min:0',
            'peso_empaque' => 'nullable|numeric|min:0',
            'tipo_medida' => 'nullable|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean',
        ]);
        $data['estado'] = $data['estado'] ?? true;
        $data['almacen_id'] = null;
        $data['peso_empaque'] = $data['peso_empaque'] ?? 2.5;
        if (isset($data['tipo_medida']) && $data['tipo_medida'] === '') {
            $data['tipo_medida'] = null;
        }
        
        $product->update($data);
        return redirect()->route('products.index')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $product = \App\Models\Product::findOrFail($id);
        
        // Verificar si el producto está asociado a algún contenedor
        if($product->containers()->count() > 0) {
            return redirect()->route('products.index')->with('error', 'No puedes eliminar el producto porque está asociado a uno o más contenedores. Primero debes eliminarlo de los contenedores.');
        }
        
        // Verificar si el producto tiene historial de transferencias recibidas
        // Un producto tiene transferencias recibidas si existe en transferencias con status 'recibido'
        // y el producto está en la bodega destino de esa transferencia
        $hasReceivedTransfers = \App\Models\TransferOrder::where('status', 'recibido')
            ->whereHas('products', function($query) use ($product) {
                $query->where('products.id', $product->id)
                      ->where('transfer_orders.warehouse_to_id', $product->almacen_id);
            })
            ->exists();
        
        if($hasReceivedTransfers) {
            return redirect()->route('products.index')->with('error', 'No puedes eliminar este producto porque tiene historial de transferencias recibidas. En su lugar, puedes desactivarlo desde la opción de editar.');
        }
        
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente.');
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
        $allReceivedTransfers = \App\Models\TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id', 'quantity', 'good_sheets', 'bad_sheets', 'receive_by');
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
            
            // 2. Obtener cantidades de transferencias recibidas (solo para bodegas que NO reciben contenedores)
            // Para bodegas que reciben contenedores, solo mostramos las cajas que quedan en el contenedor (ya descontadas)
            // Para otras bodegas, mostramos las cantidades recibidas por transferencia
            if ($producto->almacen_id && !Warehouse::bodegaRecibeContenedores($producto->almacen_id)) {
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
                        
                        // Usar good_sheets si está disponible, sino usar quantity (para compatibilidad)
                        $goodSheets = $productInTransfer->pivot->good_sheets;
                        $receiveBy = $productInTransfer->pivot->receive_by ?? 'laminas'; // Por defecto 'laminas' para transferencias antiguas
                        
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
}
