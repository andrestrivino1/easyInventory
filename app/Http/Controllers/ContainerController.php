<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ContainerController extends Controller
{
    public function index() {
        $containers = Container::with(['products', 'warehouse'])->orderByDesc('id')->get();
        return view('containers.index', compact('containers'));
    }
    public function create() {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        // Mostrar todos los productos globales
        $products = \App\Models\Product::whereNull('almacen_id')
            ->orderBy('nombre')
            ->get();
        // Mostrar solo bodegas que reciben contenedores
        $warehouses = Warehouse::whereIn('id', Warehouse::getBodegasQueRecibenContenedores())
            ->orderBy('nombre')
            ->get();
        return view('containers.create', compact('products', 'warehouses'));
    }
    public function store(Request $request) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:containers,reference',
            'note' => 'nullable|string|max:255',
            'warehouse_id' => 'required|exists:warehouses,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.boxes' => 'required|integer|min:0',
            'products.*.sheets_per_box' => 'required|integer|min:1',
        ]);
        
        // Validar que la bodega seleccionada recibe contenedores
        if (!Warehouse::bodegaRecibeContenedores($data['warehouse_id'])) {
            return back()->withInput()->with('error', 'Solo se pueden asignar contenedores a bodegas que reciben contenedores (Buenaventura/Pablo Rojas).');
        }
        
        // Crear el contenedor
        $container = Container::create([
            'reference' => $data['reference'],
            'note' => $data['note'] ?? null,
            'warehouse_id' => $data['warehouse_id'],
        ]);
        
        // Asociar productos con sus cajas y láminas por caja
        $syncData = [];
        foreach ($data['products'] as $productData) {
            $syncData[$productData['product_id']] = [
                'boxes' => $productData['boxes'],
                'sheets_per_box' => $productData['sheets_per_box'],
            ];
        }
        
        $container->products()->sync($syncData);
        
        // Actualizar tipo_medida y unidades_por_caja de productos
        // Los productos en contenedores siempre son tipo "caja"
        foreach ($data['products'] as $productData) {
            $product = \App\Models\Product::find($productData['product_id']);
            if ($product) {
                // Establecer tipo_medida como "caja" y actualizar unidades_por_caja
                $product->tipo_medida = 'caja';
                if ($productData['sheets_per_box'] > 0) {
                    $product->unidades_por_caja = $productData['sheets_per_box'];
                }
                $product->save();
            }
        }
        
        return redirect()->route('containers.index')->with('success','Contenedor creado correctamente. El stock se calculará automáticamente desde los contenedores.');
    }
    public function edit(Container $container) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $container->load('products');
        // Mostrar todos los productos globales
        $products = \App\Models\Product::whereNull('almacen_id')
            ->orderBy('nombre')
            ->get();
        // Mostrar solo bodegas que reciben contenedores
        $warehouses = Warehouse::whereIn('id', Warehouse::getBodegasQueRecibenContenedores())
            ->orderBy('nombre')
            ->get();
        return view('containers.edit', compact('container', 'products', 'warehouses'));
    }
    public function update(Request $request, Container $container) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $data = $request->validate([
            'reference' => 'required|string|max:100|unique:containers,reference,'.$container->id,
            'note' => 'nullable|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.boxes' => 'required|integer|min:0',
            'products.*.sheets_per_box' => 'required|integer|min:1',
        ]);
        
        // Actualizar contenedor
        $container->update([
            'reference' => $data['reference'],
            'note' => $data['note'] ?? null,
        ]);
        
        // Asociar nuevos productos
        $syncData = [];
        foreach ($data['products'] as $productData) {
            $syncData[$productData['product_id']] = [
                'boxes' => $productData['boxes'],
                'sheets_per_box' => $productData['sheets_per_box'],
            ];
        }
        $container->products()->sync($syncData);
        
        // Actualizar unidades_por_caja de productos (el stock se calcula dinámicamente desde contenedores)
        foreach ($data['products'] as $productData) {
            $product = \App\Models\Product::find($productData['product_id']);
            if ($product) {
                if ($product->tipo_medida === 'caja' && $productData['sheets_per_box'] > 0) {
                    $product->unidades_por_caja = $productData['sheets_per_box'];
                    $product->save();
                }
            }
        }
        
        return redirect()->route('containers.index')->with('success','Contenedor actualizado correctamente. El stock se calculará automáticamente desde los contenedores.');
    }
    public function destroy(Container $container) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // El stock se calcula dinámicamente desde contenedores, no necesitamos restaurarlo
        $container->delete();
        return redirect()->route('containers.index')->with('success','Contenedor eliminado correctamente. El stock se actualizará automáticamente.');
    }

    public function export(Container $container)
    {
        $container->load('products');
        $isExport = true;
        $pdf = Pdf::loadView('containers.pdf', compact('container', 'isExport'));
        $filename = 'Orden-Entrada-Contenedor-' . $container->reference . '.pdf';
        return $pdf->download($filename);
    }

    public function print(Container $container)
    {
        $container->load('products');
        $isExport = false; // Indicar que es para visualizar en navegador
        return view('containers.pdf', compact('container', 'isExport'));
    }
}
