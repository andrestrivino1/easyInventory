<?php

namespace App\Http\Controllers;

use App\Models\Container;
use Illuminate\Http\Request;

class ContainerController extends Controller
{
    public function index() {
        $containers = Container::orderByDesc('id')->get();
        return view('containers.index', compact('containers'));
    }
    public function create() {
        return view('containers.create');
    }
    public function store(Request $request) {
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:containers,reference',
            'product_name' => 'required|string|max:150',
            'boxes' => 'required|integer|min:0',
            'sheets_per_box' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        Container::create($data);
        return redirect()->route('containers.index')->with('success','Contenedor creado correctamente.');
    }
    public function edit(Container $container) {
        return view('containers.edit', compact('container'));
    }
    public function update(Request $request, Container $container) {
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:containers,reference,'.$container->id,
            'product_name' => 'required|string|max:150',
            'boxes' => 'required|integer|min:0',
            'sheets_per_box' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        $container->update($data);
        return redirect()->route('containers.index')->with('success','Contenedor actualizado correctamente.');
    }
    public function destroy(Container $container) {
        $container->delete();
        return redirect()->route('containers.index')->with('success','Contenedor eliminado correctamente.');
    }
}
