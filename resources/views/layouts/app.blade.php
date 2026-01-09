<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>VIDRIOS J&P S.A.S. | Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #ecf0f5;
            font-family: 'Source Sans Pro', Arial, sans-serif;
        }
        .main-sidebar {
            width: 210px;
            background: #222d32;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            color: #fff;
            z-index: 1040;
            box-shadow: 2px 0 15px -8px #232d3255;
        }
        .sidebar-logo {
            font-size: 1.3rem;
            font-weight: 700;
            padding: 16px 18px 8px 22px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            letter-spacing: .5px;
        }
        .sidebar-logo i {
            font-size: 1.27em;
            margin-right: 2px;
        }
        .sidebar-menu {
            list-style: none;
            padding-left: 0;
            margin: 8px 0 0 0;
        }
        .sidebar-menu li {
            margin-bottom: 2px;
        }
        .sidebar-menu .nav-link {
            color: #b8c7ce;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 8px 15px 8px 28px;
            border-left: 3px solid transparent;
            border-radius: 2px;
            font-size: 1.01em;
            transition: background 0.16s, border-color 0.15s, color 0.14s;
        }
        .sidebar-menu .nav-link.active, .sidebar-menu .nav-link:hover {
            background: #1e282c;
            color: #fff;
            border-left: 3px solid #3c8dbc;
        }
        .main-header {
            position: fixed;
            left: 210px;
            top: 0;
            right: 0;
            height: 60px;
            background: #3c8dbc;
            color: #fff;
            border-bottom: 1px solid #31708f;
            display: flex;
            align-items: center;
            z-index: 1035;
            box-shadow: 0 2px 11px -8px #222d3233;
            padding: 0 24px;
        }
        .main-header .page-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 0;
            color: #fff;
            letter-spacing: 0.03em;
        }
        .main-header .user {
            margin-left: auto;
            color: #fff;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .content-area {
            margin-left: 210px;
            margin-top: 54px;
            min-height: calc(100vh - 105px);
            padding: 30px 18px 20px 18px;
        }
        .main-footer {
            background: #fff;
            border-top: 1px solid #e4e8ed;
            color: #666;
            text-align: right;
            padding: 10px 24px;
            font-size: .99em;
            position: fixed;
            left: 210px;
            right: 0;
            bottom: 0;
            z-index: 1031;
        }
        /* === ESTILO ADMINLTE small-box === */
        .small-box {
            border-radius: 2px;
            position: relative;
            display: block;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        }
        .small-box > .inner {
            padding: 10px;
        }
        .small-box > .small-box-footer {
            position: relative;
            text-align: center;
            padding: 3px 0;
            color: #fff;
            color: rgba(255, 255, 255, 0.8);
            display: block;
            z-index: 10;
            background: rgba(0, 0, 0, 0.1);
            text-decoration: none;
        }
        .small-box > .small-box-footer:hover {
            color: #fff;
            background: rgba(0, 0, 0, 0.15);
        }
        .small-box h3 {
            font-size: 38px;
            font-weight: bold;
            margin: 0 0 10px 0;
            white-space: nowrap;
            padding: 0;
        }
        .small-box p {
            font-size: 15px;
        }
        .small-box .icon {
            -webkit-transition: all 0.3s linear;
            -o-transition: all 0.3s linear;
            transition: all 0.3s linear;
            position: absolute;
            top: -10px;
            right: 10px;
            z-index: 0;
            font-size: 90px;
            color: rgba(0, 0, 0, 0.15);
        }
        .small-box:hover {
            text-decoration: none;
            color: #f9f9f9;
        }
        .small-box:hover .icon {
            font-size: 95px;
        }
        @media (max-width: 767px) {
            .small-box {
                text-align: center;
            }
            .small-box .icon {
                display: none;
            }
            .small-box p {
                font-size: 12px;
            }
        }
        .sidebar-toggle-btn {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            background: #3c8dbc;
            border: none;
            color: #fff;
            font-size: 25px;
            padding: 6px 10px;
            border-radius: 3px;
            z-index: 3080;
            height: 39px;
            width: 43px;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 3px 6px -6px #222c;
        }
        @media (max-width: 991px) {
            .main-sidebar { left: -240px!important; transition: left 0.3s; }
            .main-sidebar.active { left:0!important; }
            .sidebar-toggle-btn { display:block !important; }
            .main-header { left: 0 !important; padding-left: 48px !important; z-index: 3075; }
            .main-header .page-title {
                font-size: 1.02em;
                max-width: 70vw;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                display: inline-block;
                vertical-align: middle;
                text-align: center!important;
                margin: 12px auto 3px auto !important;
            }
            .main-header .user { margin-left: 6px; font-size: 0.98em; }
            .content-area { padding: 16px 2vw 18px 2vw !important; margin-top: 58px !important; margin-left: 0 !important; min-width: 0; width: 100vw; }
            .container, .container-fluid { margin: 0 auto; padding: 0 !important; max-width: 100vw; }
            .small-box { width: 100%; margin-bottom: 16px; }
            h2, .page-title { text-align: center !important; margin: 12px auto 3px auto !important; }
        }
        /* Footer siempre ocupando ancho completo */
        .main-footer {
            width: 100vw !important;
            left: 0 !important;
            right: 0 !important;
            position: fixed;
            bottom: 0;
            max-width: 100vw !important;
            min-width: 0;
        }
        /* Ajustes de tabla responsiva/móvil - para todas las tablas y celdas */
        @media (max-width: 991px) {
            .table-responsive { width: 100%; overflow-x: auto; }
            .table-responsive table, table.table, .inventory-table, .transfer-table { min-width: 620px; font-size: 15px; }
            .table tbody tr, .inventory-table tbody tr, .transfer-table tbody tr { white-space: nowrap; }
            .actions { white-space: nowrap !important; display: flex !important; flex-direction: row !important; gap: 5px; align-items: center; justify-content: flex-start; }
            .actions form, .actions a, .actions button { display: inline-block !important; margin-bottom: 0 !important; margin-right: 5px !important; }
        }
        @media (max-width: 680px) {
            .table-responsive table, table.table, .inventory-table, .transfer-table { min-width: 570px; font-size: 14px; }
        }
        /* Evita que el boton hamburguesa se mueva. Utiliza z-index extra alto. */
        @media (max-width: 991px) {
            .sidebar-toggle-btn {top: 11px; left:10px; z-index: 3080; position:fixed;}
        }
        @media (max-width: 600px) {
            .main-sidebar { display: none; }
            .main-sidebar.active { display: block!important; left: 0!important; }
            .main-header, .main-footer {padding-left: 4px; padding-right:6px;}
            .content-area { margin:0; padding:5px 2px 15px 2px;}
        }
        @media (max-width: 591px) {
            .btn { font-size: 0.95em !important; padding: 3px 8px !important; min-width: 72px; }
        }
    </style>
</head>
<body>
    <button id="sidebarToggleBtn" class="sidebar-toggle-btn" type="button" style="display:none;"><i class="bi bi-list"></i></button>
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
            @endphp
            @if(!$isImporter)
            <li><a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('home') }}"><i class="bi bi-bar-chart"></i> Movimientos</a></li>
            <li><a class="nav-link" href="{{ route('products.index') }}"><i class="bi bi-bag"></i> Productos</a></li>
            @if($user && $user->rol === 'admin')
              <li><a class="nav-link" href="{{ route('warehouses.index') }}"><i class="bi bi-building"></i> Bodegas</a></li>
            @endif
            <li><a class="nav-link" href="{{ route('transfer-orders.index') }}"><i class="bi bi-arrow-left-right"></i> Transferencias</a></li>
            <li><a class="nav-link" href="{{ route('salidas.index') }}"><i class="bi bi-box-arrow-right"></i> Salidas</a></li>
            @if($isPabloRojas || $isFuncionario)
            <li><a class="nav-link" href="{{ route('drivers.index') }}"><i class="bi bi-truck"></i> Conductores</a></li>
            <li><a class="nav-link" href="{{ route('containers.index') }}"><i class="bi bi-box"></i> Contenedores</a></li>
            @endif
            <li><a class="nav-link {{ request()->routeIs('stock.*') ? 'active' : '' }}" href="{{ route('stock.index') }}"><i class="bi bi-clipboard-data"></i> Stock</a></li>
            <li><a class="nav-link {{ request()->routeIs('traceability.*') ? 'active' : '' }}" href="{{ route('traceability.index') }}"><i class="bi bi-diagram-3"></i> Trazabilidad</a></li>
            @endif
            @if($user && in_array($user->rol, ['admin', 'importer']))
            <li><a class="nav-link {{ request()->routeIs('imports.*') || request()->routeIs('my-imports') ? 'active' : '' }}" href="{{ $user->rol === 'admin' ? route('imports.index') : route('imports.provider-index') }}"><i class="bi bi-upload"></i> Importación</a></li>
            @endif  
            @if($user && $user->rol === 'admin' && !$isImporter)
              <li><a class="nav-link" href="{{ route('users.index') }}"><i class="bi bi-person"></i> Usuarios</a></li>
            @endif
        </ul>
    </aside>
    <header class="main-header">
        <span class="page-title">Panel de Inventario</span>
        <span class="user">
          {{ Auth::user()->name ?? 'Usuario' }} <i class="bi bi-person-circle"></i>
          <form method="POST" action="{{ route('logout') }}" style="display:inline">
              @csrf
              <button type="submit" title="Cerrar sesión" style="background:transparent;padding:0 0 0 8px;border:none;color:#fff;cursor:pointer;vertical-align:middle;font-size:19px;margin-left:5px;">
                <i class="bi bi-box-arrow-right"></i>
              </button>
          </form>
        </span>
    </header>
    <main class="content-area">
        @yield('content')
    </main>
    <footer class="main-footer">
        <div><span class="text-muted">&copy; 2026 VIDRIOS J&P S.A.S.</span> <span class="ms-3 text-muted">v1.0.0</span></div>
    </footer>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @yield('scripts')
    <script>
        // Sidebar mobile toggle
        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = document.querySelector('.main-sidebar');
            var toggleBtn = document.getElementById('sidebarToggleBtn');
            toggleBtn && toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                sidebar.classList.toggle('active');
            });
            document.addEventListener('click', function(e){
                if(window.innerWidth < 992 && sidebar.classList.contains('active')){
                    if(!sidebar.contains(e.target) && e.target!==toggleBtn){ sidebar.classList.remove('active'); }
                }
            });
        });
        // Acomoda tablas en scroll si no son table-responsive y corrige problemas de display
        document.addEventListener('DOMContentLoaded', function() {
            if(window.innerWidth < 992) {
                document.querySelectorAll('table:not(.table-responsive)').forEach(function(tbl) {
                    if(!tbl.closest('.table-responsive')){
                        var wrap=document.createElement('div');
                        wrap.className='table-responsive';
                        tbl.parentNode.insertBefore(wrap,tbl); wrap.appendChild(tbl);
                    }
                });
            }
        });
        
        // Función global para hacer logout
        function doLogout() {
            // Crear un formulario para hacer POST al logout
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("logout") }}';
            
            // Agregar token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (csrfToken) {
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken.getAttribute('content');
                form.appendChild(csrfInput);
            } else {
                // Si no hay meta tag, buscar el token en el formulario de logout existente
                const existingForm = document.querySelector('form[action*="logout"]');
                if (existingForm) {
                    const existingToken = existingForm.querySelector('input[name="_token"]');
                    if (existingToken) {
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = existingToken.value;
                        form.appendChild(csrfInput);
                    }
                }
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Detección de inactividad y logout automático después de 30 minutos
        (function() {
            let inactivityTimer;
            const INACTIVITY_TIME = 30 * 60 * 1000; // 30 minutos en milisegundos
            
            function resetTimer() {
                // Crear un formulario para hacer POST al logout
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("logout") }}';
                
                // Agregar token CSRF
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (csrfToken) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = '_token';
                    csrfInput.value = csrfToken.getAttribute('content');
                    form.appendChild(csrfInput);
                } else {
                    // Si no hay meta tag, buscar el token en el formulario de logout existente
                    const existingForm = document.querySelector('form[action*="logout"]');
                    if (existingForm) {
                        const existingToken = existingForm.querySelector('input[name="_token"]');
                        if (existingToken) {
                            const csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = '_token';
                            csrfInput.value = existingToken.value;
                            form.appendChild(csrfInput);
                        }
                    }
                }
                
                document.body.appendChild(form);
                form.submit();
            }
            
            function resetTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(function() {
                    // Hacer logout automático después de 30 minutos de inactividad
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesión expirada',
                        text: 'Tu sesión ha expirado por inactividad. Serás redirigido al login.',
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showCancelButton: false
                    }).then(function() {
                        doLogout();
                    });
                }, INACTIVITY_TIME);
            }
            
            // Eventos que indican actividad del usuario
            const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
            events.forEach(function(event) {
                document.addEventListener(event, resetTimer, true);
            });
            
            // Iniciar el timer cuando se carga la página
            resetTimer();
        })();
        
        // Interceptar errores 403 y redirigir al login
        document.addEventListener('DOMContentLoaded', function() {
            // Interceptar respuestas AJAX con error 403
            if (window.fetch) {
                const originalFetch = window.fetch;
                window.fetch = function(...args) {
                    return originalFetch.apply(this, args).then(function(response) {
                        if (response.status === 403) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Sesión expirada',
                                text: 'Tu sesión ha expirado. Serás redirigido al login.',
                                confirmButtonText: 'Aceptar',
                                allowOutsideClick: false,
                                allowEscapeKey: false
                            }).then(function() {
                                doLogout();
                            });
                        }
                        return response;
                    });
                };
            }
        });
    </script>
</body>
</html>
