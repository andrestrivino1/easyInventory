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
        if ($user->rol === 'admin') {
            $productos = \App\Models\Product::with('almacen')->orderBy('nombre')->get();
        } else {
            $productos = \App\Models\Product::with('almacen')
                ->where('almacen_id', $user->almacen_id)
                ->orderBy('nombre')->get();
        }
        return view('products.index', compact('productos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $warehouses = Warehouse::orderBy('nombre')->get();
        $containers = \App\Models\Container::orderBy('reference')->get();
        return view('products.create', compact('warehouses', 'containers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $ID_BUENAVENTURA = 1; // AJUSTA este valor si el id de Buenaventura es diferente
        if ($request->input('almacen_id') == $ID_BUENAVENTURA && $request->input('tipo_medida') !== 'caja') {
            return back()->withInput()->with('error', 'Solo se permiten Cajas en Buenaventura');
        }
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:1',
            'container_id' => 'required|exists:containers,id',
            'stock_cajas' => 'required|integer|min:1',
        ]);
        $cajas = (int)$request->input('stock_cajas');
        $unidadesXCaja = (int)($data['unidades_por_caja'] ?? 1);
        $laminasSolicitadas = $cajas * $unidadesXCaja;
        if ($data['tipo_medida'] === 'caja') {
            $data['stock'] = $laminasSolicitadas;
            $data['unidades_por_caja'] = $unidadesXCaja;
        } else {
            $cajas = 0;
            $laminasSolicitadas = $data['stock'] ?? 0;
            $data['unidades_por_caja'] = null;
            $data['stock'] = $data['stock'] ?? 0;
        }
        $container = Container::findOrFail($data['container_id']);
        $totalLaminasContenedor = $container->boxes * $container->sheets_per_box;
        if($container->boxes < $cajas) {
            return back()->withInput()->with('error', 'El contenedor no tiene cajas suficientes.');
        }
        if($totalLaminasContenedor < $laminasSolicitadas) {
            return back()->withInput()->with('error', 'El contenedor no tiene láminas suficientes.');
        }
        $container->boxes -= $cajas;
        $container->save();
        // Crear producto
        \App\Models\Product::create($data);
        return redirect()->route('products.index')->with('success', 'Producto creado correctamente y descontado del contenedor.');
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
        $product = \App\Models\Product::findOrFail($id);
        $warehouses = Warehouse::orderBy('nombre')->get();
        $containers = \App\Models\Container::orderBy('reference')->get();
        return view('products.edit', compact('product', 'warehouses', 'containers'));
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
        $ID_BUENAVENTURA = 1; // AJUSTA este valor si el id de Buenaventura es diferente
        \Log::info('DEBUG editar producto', [
            'almacen_id'  => $request->input('almacen_id'),
            'tipo_medida' => $request->input('tipo_medida')
        ]);
        if ($request->input('almacen_id') == $ID_BUENAVENTURA && $request->input('tipo_medida') !== 'caja') {
            return back()->withInput()->with('error', 'Solo se permiten Cajas en Buenaventura');
        }
        $product = \App\Models\Product::findOrFail($id);
        $oldContainerId = $product->container_id;
        $oldCajas = (int)($product->tipo_medida==='caja' ? ($product->stock && $product->unidades_por_caja ? $product->stock / $product->unidades_por_caja : 0) : 0);
        $oldLaminas = (int)$product->stock;
        $data = $request->validate([
            'nombre'   => 'required|string|max:255',
            'precio'   => 'nullable|numeric|min:0',
            'stock'    => 'nullable|integer|min:0',
            'estado'   => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:1',
            'container_id' => 'required|exists:containers,id',
            'stock_cajas' => 'required|integer|min:1',
        ]);
        $data['estado'] = $data['estado'] ?? 1;
        $data['precio'] = $data['precio'] ?? 0;
        $cajas = (int)$request->input('stock_cajas');
        $unidadesXCaja = (int)($data['unidades_por_caja'] ?? 1);
        $laminasSolicitadas = $cajas * $unidadesXCaja;
        if ($data['tipo_medida'] === 'caja') {
            $data['stock'] = $laminasSolicitadas;
            $data['unidades_por_caja'] = $unidadesXCaja;
        } else {
            $cajas = 0;
            $laminasSolicitadas = $data['stock'] ?? 0;
            $data['unidades_por_caja'] = null;
            $data['stock'] = $data['stock'] ?? 0;
        }
        // Reintegrar stock viejo a contenedor anterior (cajas y láminas)
        if($oldContainerId){
            $prevContainer = Container::find($oldContainerId);
            if($prevContainer && $oldCajas > 0) {
                $prevContainer->boxes += $oldCajas;
                $prevContainer->save();
            }
            if($prevContainer && $oldLaminas > 0) {
                $prevContainerTotalLaminas = $prevContainer->boxes * $prevContainer->sheets_per_box;
                // Solo sumamos o recalculamos cajas (ya hecho arriba); las láminas sufren recalculo automático
                // No almacenar conteo manual de láminas por consistencia
            }
        }
        $container = Container::findOrFail($data['container_id']);
        $totalLaminasContenedor = $container->boxes * $container->sheets_per_box;
        if($container->boxes < $cajas) {
            return back()->withInput()->with('error', 'El contenedor seleccionado no tiene cajas suficientes.');
        }
        if($totalLaminasContenedor < $laminasSolicitadas) {
            return back()->withInput()->with('error', 'El contenedor no tiene láminas suficientes.');
        }
        $container->boxes -= $cajas;
        $container->save();
        // Actualizar sólo los campos válidos
        $product->update($data);
        return redirect()->route('products.index')->with('success', 'Producto actualizado y stock del contenedor actualizado.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        if($product->container_id && $product->tipo_medida === 'caja') {
            $cajas = $product->unidades_por_caja ? $product->stock / $product->unidades_por_caja : 0;
            $laminas = $product->stock;
            $container = Container::find($product->container_id);
            if($container && $cajas > 0) {
                $container->boxes += (int)$cajas;
                $container->save();
            }
            // Las láminas realmente llegan por el cálculo de cajas * sheets_per_box, así que sólo con reintegrar cajas, ajusta.
        }
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado y stock del contenedor restaurado.');
    }
}
