<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use App\Models\Container;

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
        $ID_BUENAVENTURA = 1;
        
        if (in_array($user->rol, ['admin', 'secretaria'])) {
            $productos = \App\Models\Product::with(['almacen', 'containers'])->orderBy('nombre')->get();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve productos de Buenaventura
            $productos = \App\Models\Product::with(['almacen', 'containers'])
                ->where('almacen_id', $ID_BUENAVENTURA)
                ->orderBy('nombre')->get();
        } else {
            $productos = \App\Models\Product::with(['almacen', 'containers'])
                ->where('almacen_id', $user->almacen_id)
                ->orderBy('nombre')->get();
        }
        
        // Verificar para cada producto si tiene transferencias recibidas y obtener contenedores de origen
        $productosConTransferencias = collect();
        $productosContenedoresOrigen = collect(); // Contenedores de origen desde transferencias recibidas
        
        // Obtener todas las transferencias recibidas de una vez para optimizar
        $allReceivedTransfers = \App\Models\TransferOrder::where('status', 'recibido')
            ->with(['products' => function($query) {
                $query->withPivot('container_id');
            }])
            ->get();
        
        foreach ($productos as $producto) {
            // Filtrar transferencias recibidas para este producto en este almacén
            // Buscar por nombre y almacén destino, ya que el producto puede tener un ID diferente
            $receivedTransfers = $allReceivedTransfers->filter(function($transfer) use ($producto) {
                if ($transfer->warehouse_to_id != $producto->almacen_id) {
                    return false;
                }
                // Buscar por nombre del producto (ya que el ID puede ser diferente)
                return $transfer->products->contains(function($p) use ($producto) {
                    return $p->nombre === $producto->nombre && $p->codigo === $producto->codigo;
                });
            });
            
            $hasReceivedTransfers = $receivedTransfers->isNotEmpty();
            $productosConTransferencias->put($producto->id, $hasReceivedTransfers);
            
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
                $containers = \App\Models\Container::whereIn('id', $containerIds->unique())->get();
                $containersFromTransfers = $containers;
            }
            
            $productosContenedoresOrigen->put($producto->id, $containersFromTransfers);
        }
        
        $ID_BUENAVENTURA = 1;
        // Funcionario no puede crear productos (solo lectura)
        $canCreateProducts = ($user->rol !== 'funcionario') && (in_array($user->rol, ['admin', 'secretaria']) || $user->almacen_id == $ID_BUENAVENTURA);
        
        return view('products.index', compact('productos', 'productosConTransferencias', 'productosContenedoresOrigen', 'ID_BUENAVENTURA', 'canCreateProducts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        $ID_BUENAVENTURA = 1;
        
        // Solo permitir crear productos en almacenes que reciben contenedores (Buenaventura)
        if ($user->rol !== 'admin' && $user->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'Los productos solo se pueden crear en almacenes que reciben contenedores. Los productos llegan a este almacén a través de transferencias.');
        }
        
        $warehouses = Warehouse::orderBy('nombre')->get();
        return view('products.create', compact('warehouses'));
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
        $ID_BUENAVENTURA = 1; // AJUSTA este valor si el id de Buenaventura es diferente
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Solo permitir crear productos en almacenes que reciben contenedores (Buenaventura)
        if ($user->rol !== 'admin' && $user->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'Los productos solo se pueden crear en almacenes que reciben contenedores. Los productos llegan a este almacén a través de transferencias.');
        }
        
        // Validar que solo se creen productos en Buenaventura
        if ($request->input('almacen_id') != $ID_BUENAVENTURA) {
            return back()->withInput()->with('error', 'Los productos solo se pueden crear en almacenes que reciben contenedores. Los productos llegan a otros almacenes a través de transferencias.');
        }
        
        if ($request->input('almacen_id') == $ID_BUENAVENTURA && $request->input('tipo_medida') !== 'caja') {
            return back()->withInput()->with('error', 'Solo se permiten Cajas en Buenaventura');
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'medidas' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
        ]);
        
        // Valores por defecto
        $data['stock'] = 0;
        $data['unidades_por_caja'] = null;
        $data['estado'] = $data['estado'] ?? true;
        $data['precio'] = 0;
        
        // Crear producto
        \App\Models\Product::create($data);
        return redirect()->route('products.index')->with('success', 'Producto creado correctamente. Ahora puedes agregarlo a un contenedor para asignar stock.');
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
        $ID_BUENAVENTURA = 1;
        $product = \App\Models\Product::findOrFail($id);
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Solo permitir editar productos de almacenes que reciben contenedores (Buenaventura)
        if ($product->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'Los productos de este almacén no se pueden editar. Solo se pueden editar productos de almacenes que reciben contenedores.');
        }
        
        // Verificar permisos del usuario
        if ($user->rol !== 'admin' && $user->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para editar productos de este almacén.');
        }
        
        $warehouses = Warehouse::orderBy('nombre')->get();
        return view('products.edit', compact('product', 'warehouses'));
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
        $ID_BUENAVENTURA = 1; // AJUSTA este valor si el id de Buenaventura es diferente
        $product = \App\Models\Product::findOrFail($id);
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Solo permitir editar productos de almacenes que reciben contenedores (Buenaventura)
        if ($product->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'Los productos de este almacén no se pueden editar. Solo se pueden editar productos de almacenes que reciben contenedores.');
        }
        
        // Verificar permisos del usuario
        if ($user->rol !== 'admin' && $user->almacen_id != $ID_BUENAVENTURA) {
            return redirect()->route('products.index')->with('error', 'No tienes permiso para editar productos de este almacén.');
        }
        
        // Validar que solo se actualicen productos en Buenaventura
        if ($request->input('almacen_id') != $ID_BUENAVENTURA) {
            return back()->withInput()->with('error', 'Los productos solo pueden estar en almacenes que reciben contenedores.');
        }
        
        if ($request->input('almacen_id') == $ID_BUENAVENTURA && $request->input('tipo_medida') !== 'caja') {
            return back()->withInput()->with('error', 'Solo se permiten Cajas en Buenaventura');
        }
        $data = $request->validate([
            'nombre'   => 'required|string|max:255',
            'medidas'  => 'nullable|string|max:255',
            'estado'   => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
        ]);
        $data['estado'] = $data['estado'] ?? true;
        
        // Actualizar producto
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
        // y el producto está en el almacén destino de esa transferencia
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
}
