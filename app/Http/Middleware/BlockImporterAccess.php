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
                // Allow access to viewer route, view files, download files, and report generation
                $allowedRoutes = [
                    'imports.viewer-index',
                    'imports.view',
                    'imports.download',
                    'imports.report',
                    'language.switch',
                    'home',
                    'logout'
                ];
                
                $route = $request->route();
                $routeName = $route ? $route->getName() : null;
                
                // Also check the URI path
                $path = $request->path();
                $allowedPaths = ['imports-viewer', 'imports/view', 'imports/download', 'imports/report', 'home', 'logout', 'language'];
                
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
            } elseif ($userRole === 'proveedor_itr') {
                $allowedRoutes = [
                    'itrs.index',
                    'itrs.update-date',
                    'itrs.upload-evidence',
                    'itrs.download-evidence',
                    'itrs.date-history',
                    'language.switch',
                    'home',
                    'logout'
                ];
                $route = $request->route();
                $routeName = $route ? $route->getName() : null;
                $path = $request->path();
                $allowedPaths = ['itrs', 'home', 'logout', 'language'];
                $isAllowedPath = false;
                foreach ($allowedPaths as $allowedPath) {
                    if (strpos($path, $allowedPath) === 0 || $path === '' || $path === '/') {
                        $isAllowedPath = true;
                        break;
                    }
                }
                if (!in_array($routeName, $allowedRoutes) && !$isAllowedPath && $routeName !== null) {
                    return redirect()->route('itrs.index')->with('error', 'Acceso no autorizado. Solo puedes acceder al módulo ITR.');
                }
            }
        }
        
        return $next($request);
    }
}

