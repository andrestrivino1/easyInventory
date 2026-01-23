<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSessionExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Si el usuario no está autenticado y la ruta requiere autenticación
        if (!Auth::check() && !$request->is('login', 'logout', 'register', 'password/*', 'language/*')) {
            // Si es una petición AJAX o espera JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.',
                    'session_expired' => true,
                    'redirect' => route('login')
                ], 401);
            }

            // Para peticiones normales, redirigir al login con mensaje
            return redirect()->route('login')->with('session_expired', 'Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
        }

        return $next($request);
    }
}
