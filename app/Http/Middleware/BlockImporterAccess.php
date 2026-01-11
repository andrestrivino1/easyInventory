<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockImporterAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $userRole = Auth::user()->rol;
            
            if ($userRole === 'importer') {
                // Allow access only to import routes and home
                $allowedRoutes = [
                    'imports.index',
                    'imports.provider-index',
                    'imports.create',
                    'imports.store',
                    'imports.edit',
                    'imports.update',
                    'imports.download',
                    'imports.view',
                    'language.switch',
                    'home',
                    'logout'
                ];
                
                $route = $request->route();
                $routeName = $route ? $route->getName() : null;
                
                // Also check the URI path
                $path = $request->path();
                $allowedPaths = ['imports', 'my-imports', 'home', 'logout', 'language'];
                
                // Check if path starts with any allowed path
                $isAllowedPath = false;
                foreach ($allowedPaths as $allowedPath) {
                    if (strpos($path, $allowedPath) === 0 || $path === '' || $path === '/') {
                        $isAllowedPath = true;
                        break;
                    }
                }
                
                // If route is not in allowed list and path is not allowed, redirect to imports
                if (!in_array($routeName, $allowedRoutes) && !$isAllowedPath && $routeName !== null) {
                    return redirect()->route('imports.provider-index')->with('error', 'Acceso no autorizado. Solo puedes acceder al módulo de importaciones.');
                }
            } elseif ($userRole === 'import_viewer') {
                // Allow access only to viewer route (read-only) and home
                $allowedRoutes = [
                    'imports.viewer-index',
                    'imports.view',
                    'language.switch',
                    'home',
                    'logout'
                ];
                
                $route = $request->route();
                $routeName = $route ? $route->getName() : null;
                
                // Also check the URI path
                $path = $request->path();
                $allowedPaths = ['imports-viewer', 'imports/view', 'home', 'logout', 'language'];
                
                // Check if path starts with any allowed path
                $isAllowedPath = false;
                foreach ($allowedPaths as $allowedPath) {
                    if (strpos($path, $allowedPath) === 0 || $path === '' || $path === '/') {
                        $isAllowedPath = true;
                        break;
                    }
                }
                
                // If route is not in allowed list and path is not allowed, redirect to viewer-index
                if (!in_array($routeName, $allowedRoutes) && !$isAllowedPath && $routeName !== null) {
                    return redirect()->route('imports.viewer-index')->with('error', 'Acceso no autorizado. Solo puedes ver las importaciones (modo lectura).');
                }
            }
        }
        
        return $next($request);
    }
}

