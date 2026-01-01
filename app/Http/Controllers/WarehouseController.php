<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $warehouses = \App\Models\Warehouse::orderBy('nombre')->paginate(10);
        return view('warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('warehouses.create');
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
            'direccion' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
        ]);
        \App\Models\Warehouse::create($data);
        return redirect()->route('warehouses.index')
            ->with('success', 'Bodega creada correctamente.');
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
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        return view('warehouses.edit', compact('warehouse'));
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
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
        ]);
        $warehouse->update($data);
        return redirect()->route('warehouses.index')->with('success', 'Bodega editada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $warehouse = \App\Models\Warehouse::findOrFail($id);
        // Prevenir eliminación si hay productos asociados
        if ($warehouse->products()->count() > 0) {
            return redirect()->route('warehouses.index')->with('error', 'No puedes eliminar la bodega porque tiene productos asociados.');
        }
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Almacén eliminado correctamente.');
    }
}
