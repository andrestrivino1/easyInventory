<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;

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
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'precio' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'estado' => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:1',
        ]);
        $data['estado'] = $data['estado'] ?? 1;
        $data['precio'] = $data['precio'] ?? 0;
        if ($data['tipo_medida'] === 'caja') {
            $cajas = (int)($request->input('stock_cajas') ?? 1);
            $data['stock'] = $cajas * (int) $data['unidades_por_caja'];
            $data['unidades_por_caja'] = (int) $data['unidades_por_caja'] ?: 1;
        } else {
            $data['unidades_por_caja'] = null;
            $data['stock'] = $data['stock'] ?? 0;
        }
        \App\Models\Product::create($data);
        return redirect()->route('products.index')
            ->with('success', 'Producto creado correctamente.');
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
        $product = \App\Models\Product::findOrFail($id);
        $data = $request->validate([
            'nombre'   => 'required|string|max:255',
            'precio'   => 'nullable|numeric|min:0',
            'stock'    => 'nullable|integer|min:0',
            'estado'   => 'nullable|boolean',
            'almacen_id' => 'required|exists:warehouses,id',
            'tipo_medida' => 'required|in:unidad,caja',
            'unidades_por_caja' => 'nullable|integer|min:1',
        ]);
        $data['estado'] = $data['estado'] ?? 1;
        $data['precio'] = $data['precio'] ?? 0;
        if ($data['tipo_medida'] === 'caja') {
            $cajas = (int)($request->input('stock_cajas') ?? 1);
            $data['stock'] = $cajas * (int) $data['unidades_por_caja'];
            $data['unidades_por_caja'] = (int) $data['unidades_por_caja'] ?: 1;
        } else {
            $data['unidades_por_caja'] = null;
            $data['stock'] = $data['stock'] ?? 0;
        }
        $product->update($data);
        return redirect()->route('products.index')->with('success', 'Producto editado correctamente.');
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
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente.');
    }
}
