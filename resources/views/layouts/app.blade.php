<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VIDRIOS J&P S.A.S. | Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&display=swap"
        rel="stylesheet">
    <!-- App CSS (Tailwind + estilos del shell extraídos a resources/css/layout.css) -->
    <link href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : config('app.version', '1') }}" rel="stylesheet">
    <!-- Bootstrap 5 (CSS + Icons, vía CDN — usado por módulo Liquidación de Viajes) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <button id="sidebarToggleBtn" class="sidebar-toggle-btn" type="button" style="display:none;"><i
            class="bi bi-list"></i></button>
    <aside class="main-sidebar">
        <div class="sidebar-logo">
            <img src="{{ asset('public/logo.png') }}" alt="Logo" style="width:36px;height:36px;object-fit:contain;">
            <span style="display:flex;flex-direction:column;line-height:1.13;">
                <span style="font-weight:bold;font-size:1em;">VIDRIOS J&amp;P S.A.S.</span>
                <span style="font-size:11px;font-weight:500;margin-top:1px;color:#cfd8dc;">NIT: 901.701.161-4</span>
            </span>
        </div>
        <ul class="sidebar-menu">
            @php
                $user = Auth::user();
                $ID_PABLO_ROJAS = 1;
                $isPabloRojas = $user && ($user->rol === 'admin' || $user->almacen_id == $ID_PABLO_ROJAS);
                $isFuncionario = $user && $user->rol === 'funcionario';
                $isImporter = $user && $user->rol === 'importer';
                $isImportViewer = $user && $user->rol === 'import_viewer';
                $isProveedorItr = $user && $user->rol === 'proveedor_itr';
                $isPlacas = $user && $user->rol === 'placas';
            @endphp
            @if($isProveedorItr)
                <li><a class="nav-link {{ request()->routeIs('itrs.*') ? 'active' : '' }}" href="{{ route('itrs.index') }}"><i class="bi bi-box-seam"></i> ITR (Desembalaje)</a></li>
            @elseif($isPlacas)
                <li><a class="nav-link {{ request()->routeIs('liquidaciones.*') ? 'active' : '' }}" href="{{ route('liquidaciones.index') }}"><i class="bi bi-cash-coin"></i> Liquidación de Viajes</a></li>
            @elseif(!$isImporter && !$isImportViewer)
                <li><a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('home') }}"><i
                            class="bi bi-bar-chart"></i> {{ __('common.movimientos') }}</a></li>
                <li><a class="nav-link" href="{{ route('products.index') }}"><i class="bi bi-bag"></i>
                        {{ __('common.productos') }}</a></li>
                @if($user && $user->rol === 'admin')
                    <li><a class="nav-link" href="{{ route('warehouses.index') }}"><i class="bi bi-building"></i>
                            {{ __('common.bodegas') }}</a></li>
                @endif
                <li><a class="nav-link" href="{{ route('transfer-orders.index') }}"><i class="bi bi-arrow-left-right"></i>
                        {{ __('common.transferencias') }}</a></li>
                <li><a class="nav-link" href="{{ route('salidas.index') }}"><i class="bi bi-box-arrow-right"></i>
                        {{ __('common.salidas') }}</a></li>
                @if($isPabloRojas || $isFuncionario)
                    <li><a class="nav-link" href="{{ route('drivers.index') }}"><i class="bi bi-truck"></i>
                            {{ __('common.conductores') }}</a></li>
                    <li><a class="nav-link" href="{{ route('containers.index') }}"><i class="bi bi-box"></i>
                            {{ __('common.contenedores') }}</a></li>
                @endif
                <li><a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}"
                        href="{{ route('stock.index') }}"><i class="bi bi-clipboard-data"></i> {{ __('common.stock') }}</a>
                </li>
                <li><a class="nav-link {{ request()->routeIs('traceability.*') ? 'active' : '' }}"
                        href="{{ route('traceability.index') }}"><i class="bi bi-diagram-3"></i>
                        {{ __('common.trazabilidad') }}</a></li>
            @endif
            @if($user && in_array($user->rol, ['admin', 'importer', 'funcionario', 'import_viewer']))
                <li><a class="nav-link {{ request()->routeIs('imports.*') || request()->routeIs('my-imports') || request()->routeIs('imports.funcionario-index') || request()->routeIs('imports.viewer-index') ? 'active' : '' }}"
                        href="{{ $user->rol === 'admin' ? route('imports.index') : ($user->rol === 'funcionario' ? route('imports.funcionario-index') : ($user->rol === 'import_viewer' ? route('imports.viewer-index') : route('imports.provider-index'))) }}"><i
                            class="bi bi-upload"></i> {{ __('common.importacion') }}</a></li>
            @endif
            @if($user && in_array($user->rol, ['admin', 'funcionario']))
                <li><a class="nav-link {{ request()->routeIs('itrs.*') ? 'active' : '' }}" href="{{ route('itrs.index') }}"><i class="bi bi-box-seam"></i> ITR (Desembalaje)</a></li>
            @endif
            @if($user && $user->rol === 'admin' && !$isImporter && !$isImportViewer)
                <li><a class="nav-link {{ request()->routeIs('liquidaciones.*') ? 'active' : '' }}" href="{{ route('liquidaciones.index') }}"><i class="bi bi-cash-coin"></i>
                        Liquidación de Viajes</a></li>
                <li><a class="nav-link" href="{{ route('users.index') }}"><i class="bi bi-person"></i>
                        {{ __('common.usuarios') }}</a></li>
            @endif
        </ul>
    </aside>
    <header class="main-header">
        <span class="page-title">{{ __('common.panel_inventario') }}</span>
        <div style="display: flex; align-items: center; gap: 20px; margin-left: auto;">
            <!-- Language Selector -->
            <div class="language-selector">
                <div class="language-selector-wrapper">
                    <select id="languageSelect" style="display: none;">
                        <option value="es" {{ app()->getLocale() == 'es' ? 'selected' : '' }}>{{ __('common.espanol') }}
                        </option>
                        <option value="en" {{ app()->getLocale() == 'en' ? 'selected' : '' }}>{{ __('common.ingles') }}
                        </option>
                        <option value="zh" {{ app()->getLocale() == 'zh' ? 'selected' : '' }}>{{ __('common.chino') }}
                        </option>
                    </select>
                    <div class="language-selector-trigger" id="languageTrigger">
                        <span class="flag-display" id="selectedFlag">
                            <img src="{{ asset('public/images/flags/' . (app()->getLocale() == 'es' ? 'colombia' : (app()->getLocale() == 'en' ? 'usa' : 'china')) . '.png') }}"
                                alt="Flag" />
                        </span>
                        <span
                            id="languageText">{{ app()->getLocale() == 'es' ? __('common.espanol') : (app()->getLocale() == 'en' ? __('common.ingles') : __('common.chino')) }}</span>
                        <span class="arrow">▼</span>
                    </div>
                    <div class="language-selector-dropdown" id="languageDropdown">
                        <div class="language-selector-option" data-value="es">
                            <span class="flag-option"><img src="{{ asset('public/images/flags/colombia.png') }}"
                                    alt="Colombia" /></span>
                            <span>{{ __('common.espanol') }}</span>
                        </div>
                        <div class="language-selector-option" data-value="en">
                            <span class="flag-option"><img src="{{ asset('public/images/flags/usa.png') }}"
                                    alt="USA" /></span>
                            <span>{{ __('common.ingles') }}</span>
                        </div>
                        <div class="language-selector-option" data-value="zh">
                            <span class="flag-option"><img src="{{ asset('public/images/flags/china.png') }}"
                                    alt="China" /></span>
                            <span>{{ __('common.chino') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <span class="user">
                {{ Auth::user()->name ?? __('common.usuario') }} <i class="bi bi-person-circle"></i>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" title="{{ __('common.cerrar_sesion') }}"
                        style="background:transparent;padding:0 0 0 8px;border:none;color:#fff;cursor:pointer;vertical-align:middle;font-size:19px;margin-left:5px;">
                        <i class="bi bi-box-arrow-right"></i>
                    </button>
                </form>
            </span>
        </div>
    </header>
    <main class="content-area">
        @yield('content')
    </main>
    <footer class="main-footer">
        <div><span class="text-muted">&copy; 2026 VIDRIOS J&P S.A.S.</span> <span class="ms-3 text-muted">v1.0.0</span>
        </div>
    </footer>
    <script src="{{ asset('js/app.js') }}?v={{ file_exists(public_path('js/app.js')) ? filemtime(public_path('js/app.js')) : config('app.version', '1') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @yield('scripts')
    @include('partials.scripts.session-manager')
    @include('partials.scripts.language-selector')
</body>

</html>
