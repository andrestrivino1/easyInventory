<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\TransferOrder;
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
        if ($user->rol === 'admin') {
            $transferOrders = \App\Models\TransferOrder::with(['from', 'to'])->orderByDesc('date')->get();
        } else {
            $transferOrders = \App\Models\TransferOrder::with(['from', 'to'])
                ->where('warehouse_from_id', $user->almacen_id)
                ->orWhere('warehouse_to_id', $user->almacen_id)
                ->orderByDesc('date')->get();
        }
        return view('transfer-orders.index', compact('transferOrders'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $warehouses = Warehouse::orderBy('nombre')->get();
        $products = Product::orderBy('nombre')->get();
        return view('transfer-orders.create', compact('warehouses', 'products'));
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
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
            'driver_name' => 'required|string|max:100', // nuevo
            'driver_id' => 'required|string|max:20',    // nuevo
            'vehicle_plate' => 'required|string|max:20' // nuevo
        ]);
        DB::beginTransaction();
        try {
            $product = Product::where('id', $data['product_id'])->where('almacen_id', $data['warehouse_from_id'])->first();
            if (!$product) {
                return back()->with('error', 'El producto no existe en ese almacén de origen.')->withInput();
            }
            if ($product->stock < $data['quantity']) {
                return back()->with('error', 'Stock insuficiente para realizar la transferencia.')->withInput();
            }
            $product->stock -= $data['quantity'];
            $product->save();
            $transfer = TransferOrder::create([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
                'driver_name' => $data['driver_name'], // nuevo
                'driver_id' => $data['driver_id'],     // nuevo
                'vehicle_plate' => $data['vehicle_plate'] // nuevo
            ]);
            // Relacionar producto y cantidad
            $transfer->products()->attach($data['product_id'], ['quantity' => $data['quantity']]);
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
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
        $products = Product::orderBy('nombre')->get();
        return view('transfer-orders.edit', compact('transferOrder', 'warehouses', 'products'));
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
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:255',
        ]);
        DB::beginTransaction();
        try {
            $product = Product::where('id', $data['product_id'])->where('almacen_id', $data['warehouse_from_id'])->first();
            if (!$product) {
                return back()->with('error', 'El producto no existe en ese almacén de origen.')->withInput();
            }
            if ($product->stock < $data['quantity']) {
                return back()->with('error', 'Stock insuficiente para realizar la transferencia.')->withInput();
            }
            $product->stock -= $data['quantity'];
            $product->save();
            $transferOrder->update([
                'warehouse_from_id' => $data['warehouse_from_id'],
                'warehouse_to_id' => $data['warehouse_to_id'],
                'status' => 'en_transito',
                'date' => now(),
                'note' => $data['note'] ?? null,
            ]);
            // Relacionar producto y cantidad
            $transferOrder->products()->sync([$data['product_id'] => ['quantity' => $data['quantity']]]);
            DB::commit();
            return redirect()->route('transfer-orders.index')->with('success', 'Transferencia actualizada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
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
        $transferOrder->delete();
        return redirect()->route('transfer-orders.index')->with('success','Transferencia eliminada correctamente.');
    }

    public function export(TransferOrder $transferOrder)
    {
        $transferOrder->load(['from', 'to', 'products']);
        $pdf = Pdf::loadView('transfer-orders.pdf', compact('transferOrder'));
        $filename = 'Orden-Transferencia-' . $transferOrder->order_number . '.pdf';
        return $pdf->download($filename);
    }

    public function print(TransferOrder $transferOrder)
    {
        $transferOrder->load(['from', 'to', 'products']);
        return view('transfer-orders.pdf', compact('transferOrder'));
    }

    public function confirmReceived(TransferOrder $transferOrder)
    {
        // Verificar usuario correcto
        $user = Auth::user();
        if (!$user || $user->almacen_id != $transferOrder->warehouse_to_id) {
            return back()->with('error', 'No puedes confirmar esta transferencia.');
        }
        if ($transferOrder->status !== 'en_transito') {
            return back()->with('error', 'La transferencia ya fue recibida o no está activa.');
        }
        // Buscar producto en el almacén destino
        $productPivot = $transferOrder->products->first();
        if (!$productPivot) {
            return back()->with('error', 'No se encontró producto asociado a la transferencia.');
        }
        $product = \App\Models\Product::where('id', $productPivot->id)->where('almacen_id', $transferOrder->warehouse_to_id)->first();
        if ($product) {
            $product->stock += $productPivot->pivot->quantity;
            $product->save();
        } else {
            // Si no existe, se puede clonar al destino (opcional, si deseas):
            \App\Models\Product::create([
                'nombre' => $productPivot->nombre,
                'codigo' => $productPivot->codigo,
                'precio' => $productPivot->precio,
                'stock' => $productPivot->pivot->quantity,
                'estado' => $productPivot->estado,
                'almacen_id' => $transferOrder->warehouse_to_id,
            ]);
        }
        $transferOrder->status = 'recibido';
        $transferOrder->save();
        return back()->with('success', 'Transferencia recibida y stock actualizado correctamente.');
    }
}
