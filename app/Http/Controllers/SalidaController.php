<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Salida;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class SalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        
        // Filtrar por bodega del usuario (excepto admin y secretaria que ven todas)
        if (in_array($user->rol, ['admin', 'secretaria'])) {
            $salidas = Salida::with(['warehouse', 'products'])->orderByDesc('fecha')->get();
        } else {
            $salidas = Salida::with(['warehouse', 'products'])
                ->where('warehouse_id', $user->almacen_id)
                ->orderByDesc('fecha')
                ->get();
        }
        
        return view('salidas.index', compact('salidas'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $user = Auth::user();
        
        // Obtener productos de la bodega del usuario
        if (in_array($user->rol, ['admin', 'secretaria'])) {
            $products = Product::with('containers')
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get();
            $warehouses = Warehouse::orderBy('nombre')->get();
        } else {
            $products = Product::with('containers')
                ->where('almacen_id', $user->almacen_id)
                ->where('stock', '>', 0)
                ->orderBy('nombre')
                ->get();
            $warehouses = collect([$user->almacen]);
        }
        
        return view('salidas.create', compact('products', 'warehouses'));
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
        
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'fecha' => 'required|date',
            'a_nombre_de' => 'required|string|max:255',
            'nit_cedula' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);
        
        // Validar que el usuario tenga permiso para esta bodega
        if (!in_array($user->rol, ['admin', 'secretaria']) && $data['warehouse_id'] != $user->almacen_id) {
            return back()->with('error', 'No tienes permiso para crear salidas en esta bodega.')->withInput();
        }
        
        DB::beginTransaction();
        try {
            $productsToAttach = [];
            
            // Validar cada producto
            foreach ($data['products'] as $index => $productData) {
                $product = Product::where('id', $productData['product_id'])
                    ->where('almacen_id', $data['warehouse_id'])
                    ->lockForUpdate() // Bloquear el registro para evitar condiciones de carrera
                    ->first();
                    
                if (!$product) {
                    DB::rollBack();
                    return back()->with('error', "Producto #" . ($index + 1) . ": El producto seleccionado no existe en esta bodega.")->withInput();
                }
                
                // Validar stock suficiente
                // Las salidas siempre se hacen en láminas (unidades), no en cajas
                $quantity = $productData['quantity']; // Esta cantidad es en láminas (unidades)
                $unidadesADescontar = $quantity; // Ya está en unidades
                
                if ($product->stock < $unidadesADescontar) {
                    DB::rollBack();
                    // Mostrar stock disponible en láminas
                    $laminasDisponibles = $product->stock;
                    if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                        $cajasDisponibles = floor($product->stock / $product->unidades_por_caja);
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas ({$cajasDisponibles} cajas). Solicitado: {$quantity} láminas.")->withInput();
                    } else {
                        return back()->with('error', "Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas. Solicitado: {$quantity} láminas.")->withInput();
                    }
                }
                
                $productsToAttach[] = [
                    'product_id' => $productData['product_id'],
                    'container_id' => null, // Las salidas no requieren contenedor
                    'quantity' => $quantity,
                    'unidades_a_descontar' => $unidadesADescontar,
                ];
            }
            
            // Crear la salida
            $salida = Salida::create([
                'warehouse_id' => $data['warehouse_id'],
                'fecha' => $data['fecha'],
                'a_nombre_de' => $data['a_nombre_de'],
                'nit_cedula' => $data['nit_cedula'],
                'note' => $data['note'] ?? null,
            ]);
            
            // Descontar stock y asociar productos
            foreach ($productsToAttach as $index => $item) {
                try {
                    // Recargar el producto de la bodega correcta para asegurar que tenemos la versión más actualizada
                    $product = Product::where('id', $item['product_id'])
                        ->where('almacen_id', $data['warehouse_id'])
                        ->lockForUpdate() // Bloquear el registro para evitar condiciones de carrera
                        ->first();
                    
                    if (!$product) {
                        throw new \Exception("El producto con ID {$item['product_id']} no existe en esta bodega.");
                    }
                    
                    // Verificar nuevamente el stock antes de descontar (por si cambió entre validación y descuento)
                    if ($product->stock < $item['unidades_a_descontar']) {
                        $laminasDisponibles = $product->stock;
                        if ($product->tipo_medida === 'caja' && $product->unidades_por_caja > 0) {
                            $cajasDisponibles = floor($product->stock / $product->unidades_por_caja);
                            throw new \Exception("Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas ({$cajasDisponibles} cajas). Solicitado: {$item['quantity']} láminas.");
                        } else {
                            throw new \Exception("Producto #" . ($index + 1) . " ({$product->nombre}): Stock insuficiente. Disponible: {$laminasDisponibles} láminas. Solicitado: {$item['quantity']} láminas.");
                        }
                    }
                    
                    // Descontar el stock
                    $stockAnterior = $product->stock;
                    $product->stock -= $item['unidades_a_descontar'];
                    $product->save();
                    
                    \Log::info('SALIDA store - stock descontado', [
                        'product_id' => $product->id,
                        'product_nombre' => $product->nombre,
                        'warehouse_id' => $data['warehouse_id'],
                        'stock_anterior' => $stockAnterior,
                        'unidades_descontadas' => $item['unidades_a_descontar'],
                        'stock_nuevo' => $product->stock
                    ]);
                    
                    // Asociar el producto a la salida
                    $pivotData = [
                        'quantity' => $item['quantity'],
                        'container_id' => $item['container_id']
                    ];
                    
                    $salida->products()->attach($item['product_id'], $pivotData);
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error('SALIDA store - error al procesar producto', [
                        'msg'=>$e->getMessage(), 
                        'product_id'=>$item['product_id'],
                        'warehouse_id'=>$data['warehouse_id'],
                        'index'=>$index,
                        'trace'=>$e->getTraceAsString()
                    ]);
                    return back()->with('error', $e->getMessage())->withInput();
                }
            }
            
            DB::commit();
            return redirect()->route('salidas.index')->with('success', 'Salida creada correctamente.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('SALIDA store - validación fallida', ['errors'=>$e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('SALIDA store - exception', [
                'msg'=>$e->getMessage(), 
                'file'=>$e->getFile(), 
                'line'=>$e->getLine(),
                'trace'=>$e->getTraceAsString()
            ]);
            $errorMessage = "Error al crear la salida: " . $e->getMessage();
            if (config('app.debug')) {
                $errorMessage .= " (Archivo: " . basename($e->getFile()) . ", Línea: " . $e->getLine() . ")";
            }
            return back()->with('error', $errorMessage)->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Salida  $salida
     * @return \Illuminate\Http\Response
     */
    public function show(Salida $salida)
    {
        $user = Auth::user();
        
        // Validar que el usuario tenga acceso a esta salida
        if (!in_array($user->rol, ['admin', 'secretaria']) && $salida->warehouse_id != $user->almacen_id) {
            return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
        }
        
        $salida->load(['warehouse', 'products']);
        
        return view('salidas.show', compact('salida'));
    }

    /**
     * Export PDF
     */
    public function export(Salida $salida)
    {
        $user = Auth::user();
        
        // Validar que el usuario tenga acceso a esta salida
        if (!in_array($user->rol, ['admin', 'secretaria']) && $salida->warehouse_id != $user->almacen_id) {
            return redirect()->route('salidas.index')->with('error', 'No tienes permiso para descargar esta salida.');
        }
        
        $salida->load(['warehouse', 'products']);
        
        $isExport = true;
        $pdf = Pdf::loadView('salidas.pdf', compact('salida', 'isExport'));
        $filename = 'Salida-' . $salida->salida_number . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Print PDF
     */
    public function print(Salida $salida)
    {
        $user = Auth::user();
        
        // Validar que el usuario tenga acceso a esta salida
        if (!in_array($user->rol, ['admin', 'secretaria']) && $salida->warehouse_id != $user->almacen_id) {
            return redirect()->route('salidas.index')->with('error', 'No tienes permiso para ver esta salida.');
        }
        
        $salida->load(['warehouse', 'products']);
        
        $isExport = false;
        return view('salidas.pdf', compact('salida', 'isExport'));
    }
}
