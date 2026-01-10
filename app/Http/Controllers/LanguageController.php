<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switchLanguage($locale)
    {
        // Validar que el idioma esté soportado
        $supportedLocales = ['es', 'en', 'zh'];
        
        if (!in_array($locale, $supportedLocales)) {
            return redirect()->back()->with('error', 'Idioma no soportado');
        }
        
        // Establecer el idioma en la sesión
        Session::put('locale', $locale);
        
        // Establecer el idioma en una cookie que persista por 1 año
        $cookie = cookie('app_locale', $locale, 525600); // 1 año en minutos
        
        // Establecer el idioma en la aplicación
        App::setLocale($locale);
        
        return redirect()->back()->cookie($cookie);
    }
}
