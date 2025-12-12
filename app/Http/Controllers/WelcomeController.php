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
        $isAdmin = $user && $user->rol === 'admin';
        // Productos
        $productos = $isAdmin
            ? Product::all()
            : Product::where('almacen_id', $user->almacen_id)->get();
        $totalProductos = $productos->count();
        $bajoStock = $productos->where('stock', '<', 5)->count();
        // Almacenes
        $totalAlmacenes = $isAdmin ? Warehouse::count() : 1;
        // Transferencias
        if ($isAdmin) {
            $transito = TransferOrder::where('status','en_transito')->count();
        } else {
            $transito = TransferOrder::where('status', 'en_transito')
                ->where(function($q) use($user) {
                    $q->where('warehouse_from_id', $user->almacen_id)
                      ->orWhere('warehouse_to_id', $user->almacen_id);
                })
                ->count();
        }
        return view('welcome', compact('totalProductos', 'totalAlmacenes', 'bajoStock', 'transito'));
    }
}
