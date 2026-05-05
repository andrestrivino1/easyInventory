<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\TransferOrder;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }
        $isAdmin = $user && in_array($user->rol, ['admin', 'funcionario']);
        $ID_PABLO_ROJAS = 1;
        
        // Productos
        if ($isAdmin) {
            $productos = Product::all();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario ve todos los productos (como secretaria)
            $productos = Product::all();
        } elseif ($user->rol === 'clientes') {
            // Clientes ven productos de sus bodegas asignadas
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (empty($bodegasAsignadasIds)) {
                $bodegasAsignadasIds = [];
            }
            $productos = Product::whereIn('almacen_id', $bodegasAsignadasIds)->get();
        } else {
            $productos = Product::where('almacen_id', $user->almacen_id)->get();
        }
        $totalProductos = $productos->count();
        $bajoStock = $productos->where('stock', '<', 5)->count();
        // Bodegas
        if ($isAdmin) {
            $totalBodegas = Warehouse::count();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario ve todas las bodegas (como secretaria)
            $totalBodegas = Warehouse::count();
        } elseif ($user->rol === 'clientes') {
            // Clientes ven sus bodegas asignadas
            $totalBodegas = $user->almacenes()->count();
        } else {
            $totalBodegas = 1;
        }
        // Transferencias
        if ($isAdmin) {
            $transito = TransferOrder::where('status','en_transito')->count();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario ve todas las transferencias (como secretaria)
            $transito = TransferOrder::where('status','en_transito')->count();
        } elseif ($user->rol === 'clientes') {
            // Clientes ven transferencias relacionadas con sus bodegas asignadas
            $bodegasAsignadas = $user->almacenes()->get();
            $bodegasAsignadasIds = $bodegasAsignadas->pluck('id')->toArray();
            if (empty($bodegasAsignadasIds)) {
                $bodegasAsignadasIds = [];
            }
            $transito = TransferOrder::where('status', 'en_transito')
                ->where(function($q) use($bodegasAsignadasIds) {
                    $q->whereIn('warehouse_from_id', $bodegasAsignadasIds)
                      ->orWhereIn('warehouse_to_id', $bodegasAsignadasIds);
                })
                ->count();
        } else {
            $transito = TransferOrder::where('status', 'en_transito')
                ->where(function($q) use($user) {
                    $q->where('warehouse_from_id', $user->almacen_id)
                      ->orWhere('warehouse_to_id', $user->almacen_id);
                })
                ->count();
        }
        return view('welcome', compact('totalProductos', 'totalBodegas', 'bajoStock', 'transito'));
    }
}
