<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Si el usuario es importer, redirigir al módulo de importaciones
        if (auth()->check() && auth()->user()->rol === 'importer') {
            return redirect()->route('imports.provider-index');
        }
        
        // Si el usuario es import_viewer, redirigir a la vista de importaciones (solo lectura)
        if (auth()->check() && auth()->user()->rol === 'import_viewer') {
            return redirect()->route('imports.viewer-index');
        }

        // Si el usuario es proveedor_itr, redirigir al módulo ITR
        if (auth()->check() && auth()->user()->rol === 'proveedor_itr') {
            return redirect()->route('itrs.index');
        }
        
        // Invoca directamente la lógica de WelcomeController
        return app(\App\Http\Controllers\WelcomeController::class)->index();
    }
}
