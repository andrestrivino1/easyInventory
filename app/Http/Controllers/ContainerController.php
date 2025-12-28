<?php

namespace App\Http\Controllers;

use App\Models\Container;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class ContainerController extends Controller
{
    public function index() {
        $containers = Container::with('products')->orderByDesc('id')->get();
        return view('containers.index', compact('containers'));
    }
    public function create() {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        // Solo mostrar productos de Buenaventura (almacén ID 1) ya que los contenedores solo existen allí
        $ID_BUENAVENTURA = 1;
        $products = \App\Models\Product::where('almacen_id', $ID_BUENAVENTURA)
            ->orderBy('nombre')
            ->get();
        return view('containers.create', compact('products'));
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
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.boxes' => 'required|integer|min:0',
            'products.*.sheets_per_box' => 'required|integer|min:1',
        ]);
        
        // Crear el contenedor
        $container = Container::create([
            'reference' => $data['reference'],
            'note' => $data['note'] ?? null,
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
        
        // Actualizar stock de productos
        foreach ($data['products'] as $productData) {
            $product = \App\Models\Product::find($productData['product_id']);
            if ($product) {
                $totalSheets = $productData['boxes'] * $productData['sheets_per_box'];
                $product->stock += $totalSheets;
                
                // Si el producto es tipo caja, actualizar unidades_por_caja
                if ($product->tipo_medida === 'caja' && $productData['sheets_per_box'] > 0) {
                    $product->unidades_por_caja = $productData['sheets_per_box'];
                }
                
                $product->save();
            }
        }
        
        return redirect()->route('containers.index')->with('success','Contenedor creado correctamente y productos actualizados.');
    }
    public function edit(Container $container) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        $container->load('products');
        // Solo mostrar productos de Buenaventura (almacén ID 1) ya que los contenedores solo existen allí
        $ID_BUENAVENTURA = 1;
        $products = \App\Models\Product::where('almacen_id', $ID_BUENAVENTURA)
            ->orderBy('nombre')
            ->get();
        return view('containers.edit', compact('container', 'products'));
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
        
        // Restaurar stock de productos anteriores
        foreach ($container->products as $oldProduct) {
            $pivot = $oldProduct->pivot;
            $totalSheets = $pivot->boxes * $pivot->sheets_per_box;
            $product = \App\Models\Product::find($oldProduct->id);
            if ($product && $product->stock >= $totalSheets) {
                $product->stock -= $totalSheets;
                $product->save();
            }
        }
        
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
        
        // Actualizar stock de productos nuevos
        foreach ($data['products'] as $productData) {
            $product = \App\Models\Product::find($productData['product_id']);
            if ($product) {
                $totalSheets = $productData['boxes'] * $productData['sheets_per_box'];
                $product->stock += $totalSheets;
                
                if ($product->tipo_medida === 'caja' && $productData['sheets_per_box'] > 0) {
                    $product->unidades_por_caja = $productData['sheets_per_box'];
                }
                
                $product->save();
            }
        }
        
        return redirect()->route('containers.index')->with('success','Contenedor actualizado correctamente.');
    }
    public function destroy(Container $container) {
        $user = Auth::user();
        
        // Funcionario solo lectura
        if ($user->rol === 'funcionario') {
            return redirect()->route('containers.index')->with('error', 'No tienes permiso para realizar esta acción. Solo lectura permitida.');
        }
        
        // Restaurar stock de productos antes de eliminar
        foreach ($container->products as $product) {
            $pivot = $product->pivot;
            $totalSheets = $pivot->boxes * $pivot->sheets_per_box;
            $prod = \App\Models\Product::find($product->id);
            if ($prod && $prod->stock >= $totalSheets) {
                $prod->stock -= $totalSheets;
                $prod->save();
            }
        }
        $container->delete();
        return redirect()->route('containers.index')->with('success','Contenedor eliminado correctamente y stock restaurado.');
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
