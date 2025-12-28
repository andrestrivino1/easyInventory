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
        $ID_BUENAVENTURA = 1;
        
        // Productos
        if ($isAdmin) {
            $productos = Product::all();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve productos de Buenaventura
            $productos = Product::where('almacen_id', $ID_BUENAVENTURA)->get();
        } else {
            $productos = Product::where('almacen_id', $user->almacen_id)->get();
        }
        $totalProductos = $productos->count();
        $bajoStock = $productos->where('stock', '<', 5)->count();
        // Almacenes
        $totalAlmacenes = $isAdmin ? Warehouse::count() : 1;
        // Transferencias
        if ($isAdmin) {
            $transito = TransferOrder::where('status','en_transito')->count();
        } elseif ($user->rol === 'funcionario') {
            // Funcionario solo ve transferencias relacionadas con Buenaventura
            $transito = TransferOrder::where('status', 'en_transito')
                ->where(function($q) use($ID_BUENAVENTURA) {
                    $q->where('warehouse_from_id', $ID_BUENAVENTURA)
                      ->orWhere('warehouse_to_id', $ID_BUENAVENTURA);
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
        return view('welcome', compact('totalProductos', 'totalAlmacenes', 'bajoStock', 'transito'));
    }
}
