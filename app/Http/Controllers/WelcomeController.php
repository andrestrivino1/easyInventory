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
        $isAdmin = $user && in_array($user->rol, ['admin', 'secretaria']);
        $ID_PABLO_ROJAS = 1;
        
        // Productos
        if ($isAdmin) {
            $productos = Product::all();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve productos de Pablo Rojas
            $productos = Product::where('almacen_id', $ID_PABLO_ROJAS)->get();
        } else {
            $productos = Product::where('almacen_id', $user->almacen_id)->get();
        }
        $totalProductos = $productos->count();
        $bajoStock = $productos->where('stock', '<', 5)->count();
        // Bodegas
        $totalBodegas = $isAdmin ? Warehouse::count() : 1;
        // Transferencias
        if ($isAdmin) {
            $transito = TransferOrder::where('status','en_transito')->count();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve transferencias relacionadas con Pablo Rojas
            $transito = TransferOrder::where('status', 'en_transito')
                ->where(function($q) use($ID_PABLO_ROJAS) {
                    $q->where('warehouse_from_id', $ID_PABLO_ROJAS)
                      ->orWhere('warehouse_to_id', $ID_PABLO_ROJAS);
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
