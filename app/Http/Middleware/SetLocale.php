<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class SetLocale
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
        // Obtener el idioma en este orden: cookie > sesión > predeterminado
        $locale = $request->cookie('app_locale');
        
        if (!$locale) {
            $locale = Session::get('locale');
        }
        
        if (!$locale) {
            $locale = config('app.locale');
        }
        
        // Validar que el idioma esté soportado
        $supportedLocales = ['es', 'en', 'zh'];
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.locale');
        }
        
        // Establecer el idioma en la sesión si no está
        if (!Session::has('locale')) {
            Session::put('locale', $locale);
        }
        
        // Establecer el idioma
        App::setLocale($locale);
        
        return $next($request);
    }
}
