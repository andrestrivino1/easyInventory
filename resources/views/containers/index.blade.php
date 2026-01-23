@extends('layouts.app')

@section('content')
    <style>
        .container-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            font-size: 14px;
            table-layout: auto;
        }

        .container-table th,
        .container-table td {
            padding: 12px;
            word-break: break-word;
            text-align: center;
        }

        .container-table tbody tr:not(:last-child) td {
            border-bottom: 1px solid #e6e6e6;
        }

        .container-table th {
            background: #0066cc;
            color: white;
            font-size: 14px;
            letter-spacing: 0.1px;
            white-space: nowrap;
        }

        .container-table td {
            font-size: 13.2px;
        }

        .container-table tr:hover {
            background: #f1f7ff;
        }

        .actions {
            gap: 6px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }

        .actions button,
        .actions a {
            padding: 5px 9px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            display: inline-block;
            text-decoration: none;
            white-space: nowrap;
        }

        .actions form {
            display: inline-block;
            margin: 0;
        }

        .btn-edit {
            background: #1d7ff0;
            color: white;
        }

        .btn-delete {
            background: #ffb3b3;
            color: #b30000;
        }

        .actions button:hover,
        .actions a:hover {
            opacity: 0.85;
        }

        .table-responsive-custom {
            overflow-x: auto;
            max-width: 100vw;
            padding-bottom: 6px;
        }
    </style>
    <div class="container-fluid" style="padding-top:32px; min-height:88vh;">
        <div class="mx-auto" style="max-width:1200px;">
            <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
                <a href="{{ route('containers.create') }}" class="btn btn-primary rounded-pill px-4"
                    style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nuevo contenedor</a>
            </div>
            <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Contenedores registrados</h2>

            <!-- Campo de búsqueda -->
            <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
                <div style="position: relative; width: 100%;">
                    <input type="text" id="search-containers-main" class="form-control" placeholder="Buscar contenedores..."
                        style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
                    <i class="bi bi-search"
                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
                </div>
            </div>

            @if(session('success') || session('error') || session('warning'))
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        @if(session('success'))
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: '{{ session('success') }}',
                                timer: 3300,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        @endif
                        @if(session('error'))
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'error',
                                title: '{{ session('error') }}',
                                timer: 3800,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        @endif
                        @if(session('warning'))
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'warning',
                                title: '{{ session('warning') }}',
                                timer: 3500,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        @endif
                        });
                </script>
            @endif
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Búsqueda en tiempo real
                    const searchInput = document.getElementById('search-containers-main');
                    const table = document.getElementById('containers-main-table');
                    if (searchInput && table) {
                        searchInput.addEventListener('input', function () {
                            const searchTerm = this.value.toLowerCase().trim();
                            const rows = table.querySelectorAll('tbody tr');
                            rows.forEach(function (row) {
                                const text = row.textContent.toLowerCase();
                                row.style.display = text.includes(searchTerm) ? '' : 'none';
                            });
                        });
                    }
                });
            </script>
            <div class="table-responsive-custom">
                <table class="container-table" id="containers-main-table">
                    <thead>
                        <tr>
                            <th>Contenedor</th>
                            <th>Bodega</th>
                            <th style="min-width:280px;">Productos</th>
                            <th>Medidas</th>
                            <th>Cajas</th>
                            <th>Láminas</th>
                            <th>Observación</th>
                            <th style="min-width:130px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($containers as $container)
                            @php
                                $container->load('products');
                                $totalBoxes = 0;
                                $totalSheets = 0;
                                foreach ($container->products as $product) {
                                    $totalBoxes += $product->pivot->boxes;
                                    $totalSheets += ($product->pivot->boxes * $product->pivot->sheets_per_box);
                                }
                            @endphp
                            <tr>
                                <td><strong>{{ $container->reference }}</strong></td>
                                <td>
                                    @if($container->warehouse)
                                        <span
                                            style="font-weight: 500;">{{ $container->warehouse->nombre }}{{ $container->warehouse->ciudad ? ' - ' . $container->warehouse->ciudad : '' }}</span>
                                    @else
                                        <span style="color: #999; font-style: italic;">Sin asignar</span>
                                    @endif
                                </td>
                                <td>
                                    @if($container->products->count() > 0)
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            @foreach($container->products as $product)
                                                <div style="font-size: 13px; line-height: 1.5;">
                                                    <strong>{{ $product->nombre }}</strong>
                                                    <span style="color: #666;">({{ $product->pivot->boxes }} cajas ×
                                                        {{ $product->pivot->sheets_per_box }} láminas)</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #999; font-style: italic;">Sin productos</span>
                                    @endif
                                </td>
                                <td>
                                    @if($container->products->count() > 0)
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            @foreach($container->products as $product)
                                                <div style="font-size: 13px; line-height: 1.5;">
                                                    {{ $product->medidas ?? '-' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #999; font-style: italic;">-</span>
                                    @endif
                                </td>
                                <td><strong>{{ $totalBoxes }}</strong></td>
                                <td><strong>{{ $totalSheets }}</strong></td>
                                <td>{{ $container->note ?? '-' }}</td>
                                <td class="actions">
                                    @php $user = Auth::user(); @endphp
                                    <div
                                        style="display: flex; gap: 6px; align-items: center; justify-content: center; flex-wrap: nowrap;">
                                        @if($user->rol !== 'funcionario')
                                            <a href="{{ route('containers.edit', $container) }}" class="btn-edit">Editar</a>
                                            <form action="{{ route('containers.destroy', $container) }}" method="POST"
                                                style="display:inline; margin:0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-delete"
                                                    onclick="return confirm('¿Estás seguro de eliminar este contenedor?')">Eliminar</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('containers.print', $container) }}" class="btn btn-outline-secondary"
                                            title="Imprimir" style="padding: 5px 9px; vertical-align:middle;" target="_blank"><i
                                                class="bi bi-printer"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4"><i class="bi bi-box text-secondary"
                                        style="font-size:2.2em;"></i><br>
                                    <div class="mt-2">No hay contenedores registrados.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Paginación -->
                <div id="containers-pagination" class="mt-4">
                    @if(method_exists($containers, 'total') && $containers->total() > $containers->perPage())
                        <div
                            style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                <div style="color: #666; font-size: 14px;">
                                    Mostrando {{ $containers->firstItem() ?? 0 }} - {{ $containers->lastItem() ?? 0 }} de
                                    {{ $containers->total() }} contenedores
                                </div>
                                <div>
                                    {!! $containers->appends(request()->query())->links() !!}
                                </div>
                            </div>
                        </div>
                    @elseif(method_exists($containers, 'total') && $containers->total() > 0)
                        <div
                            style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); text-align: center; color: #666; font-size: 14px;">
                            Mostrando {{ $containers->total() }} contenedor(es)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection