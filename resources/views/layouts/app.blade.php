<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Inventory | Dashboard</title>
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
            height: 49px;
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
        @media (max-width: 991px) {
            .main-sidebar, .main-footer, .content-area, .main-header { margin-left: 0!important; left:0!important; }
            .main-sidebar { width: 60vw; min-width:160px; max-width:240px; }
            .main-header, .main-footer { padding-left: 8vw; }
            .content-area {padding: 18px 3vw 16px 3vw;}
        }
        @media (max-width: 600px) {
            .main-header, .main-footer {padding-left: 6px; padding-right:6px;}
            .main-sidebar {display:none;}
            .content-area {margin:0; padding:10px 2px 12px 2px;}
        }
    </style>
</head>
<body>
    <aside class="main-sidebar">
        <div class="sidebar-logo"><i class="bi bi-box"></i> EasyInventory</div>
        <ul class="sidebar-menu">
            <li><a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="/"><i class="bi bi-bar-chart"></i> Dashboard</a></li>
            <li><a class="nav-link" href="/products"><i class="bi bi-bag"></i> Productos</a></li>
            @if(Auth::user() && Auth::user()->rol === 'admin')
              <li><a class="nav-link" href="/warehouses"><i class="bi bi-building"></i> Almacenes</a></li>
            @endif
            <li><a class="nav-link" href="/transfer-orders"><i class="bi bi-arrow-left-right"></i> Transferencias</a></li>
            <li><a class="nav-link" href="/stock-movements"><i class="bi bi-recycle"></i> Movimientos</a></li>
            @if(Auth::user() && Auth::user()->rol === 'admin')
              <li><a class="nav-link" href="/users"><i class="bi bi-person"></i> Usuarios</a></li>
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
        <div><span class="text-muted">&copy; 2025 EasyInventory</span> <span class="ms-3 text-muted">v1.0.0</span></div>
    </footer>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @yield('scripts')
</body>
</html>
