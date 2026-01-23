@extends('layouts.app')

@section('content')
    <style>
        .table-responsive-custom {
            overflow-x: auto;
            max-width: 100vw;
            padding-bottom: 6px;
        }

        .salida-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            font-size: 14px;
            table-layout: auto;
        }

        .salida-table th,
        .salida-table td {
            padding: 12px;
            word-break: break-word;
            vertical-align: top;
        }

        .salida-table tbody tr:not(:last-child) td {
            border-bottom: 1px solid #e6e6e6;
        }

        .salida-table th {
            background: #0066cc;
            color: white;
            font-size: 14px;
            letter-spacing: 0.1px;
            white-space: nowrap;
        }

        .salida-table td {
            font-size: 13.2px;
        }

        .salida-table tr:hover {
            background: #f1f7ff;
        }

        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }

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

        .btn-view {
            background: #1d7ff0;
            color: white;
        }

        .btn-print {
            background: #6c757d;
            color: white;
        }

        .btn-delete {
            background: #ffb3b3;
            color: #b30000;
        }

        .actions a:hover,
        .actions button:hover {
            opacity: 0.85;
        }

        .actions form {
            display: inline-block;
            margin: 0;
        }

        .actions button {
            padding: 5px 9px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
        }
    </style>
    <div class="container-fluid" style="padding-top:32px; min-height:88vh;">
        <div class="mx-auto" style="max-width:1400px;">
            <div class="d-flex justify-content-end align-items-center mb-3" style="gap:10px;">
                <a href="{{ route('salidas.create') }}" class="btn btn-primary rounded-pill px-4"
                    style="font-weight:500;"><i class="bi bi-plus-circle me-2"></i>Nueva salida</a>
            </div>
            <h2 class="mb-4" style="text-align:center;color:#333;font-weight:bold;">Salidas</h2>

            <!-- Campo de búsqueda -->
            <div class="mb-3" style="max-width: 400px; margin: 0 auto 20px; text-align: center;">
                <div style="position: relative; width: 100%;">
                    <input type="text" id="search-salidas-main" class="form-control" placeholder="Buscar salidas..."
                        style="padding-left: 40px; border-radius: 25px; border: 2px solid #e0e0e0; width: 100%;">
                    <i class="bi bi-search"
                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; font-size: 16px; pointer-events: none;"></i>
                </div>
            </div>

            <div class="table-responsive-custom">
                <table class="salida-table" id="salidas-main-table">
                    <thead>
                        <tr>
                            <th>No. Salida</th>
                            <th>Bodega</th>
                            <th>Fecha</th>
                            <th>A nombre de</th>
                            <th>NIT/Cédula</th>
                            <th>Productos</th>
                            <th style="min-width:140px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salidas as $salida)
                            <tr>
                                <td>{{ $salida->salida_number }}</td>
                                <td>{{ $salida->warehouse->nombre ?? '-' }}</td>
                                <td>{{ $salida->fecha->format('d/m/Y') }}</td>
                                <td>{{ $salida->a_nombre_de }}</td>
                                <td>{{ $salida->nit_cedula }}</td>
                                <td>
                                    @php
                                        $bodegasBuenaventuraIds = \App\Models\Warehouse::getBodegasBuenaventuraIds();
                                        $isBuenaventura = in_array($salida->warehouse_id, $bodegasBuenaventuraIds);
                                      @endphp
                                    @foreach($salida->products as $prod)
                                        <div style="font-size: 13px; margin-bottom: 4px;">
                                            <strong>{{ $prod->nombre }}</strong>
                                            @if($isBuenaventura && $prod->tipo_medida === 'caja' && $prod->unidades_por_caja > 0)
                                                @php
                                                    $cajas = floor($prod->pivot->quantity / $prod->unidades_por_caja);
                                                @endphp
                                                <span style="color: #666;">({{ $cajas }} cajas)</span>
                                            @else
                                                <span style="color: #666;">({{ $prod->pivot->quantity }} láminas)</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>
                                <td class="actions" style="white-space:nowrap;">
                                    <div
                                        style="display: flex; gap: 6px; align-items: center; justify-content: center; flex-wrap: wrap;">
                                        <a href="{{ route('salidas.show', $salida) }}" class="btn-view">Ver</a>
                                        <a href="{{ route('salidas.print', $salida) }}" class="btn-print" target="_blank"><i
                                                class="bi bi-printer"></i></a>
                                        @if(Auth::user()->rol === 'admin')
                                            <form action="{{ route('salidas.destroy', $salida) }}" method="POST"
                                                style="display:inline; margin:0;">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-delete">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-box-arrow-right text-secondary" style="font-size:2.2em;"></i><br>
                                    <div class="mt-2">No existen salidas registradas.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Paginación -->
                <div id="salidas-pagination" class="mt-4">
                    @if(method_exists($salidas, 'total') && $salidas->total() > $salidas->perPage())
                        <div
                            style="padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);">
                            <div
                                style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                                <div style="color: #666; font-size: 14px;">
                                    Mostrando {{ $salidas->firstItem() ?? 0 }} - {{ $salidas->lastItem() ?? 0 }} de
                                    {{ $salidas->total() }} salidas
                                </div>
                                <div>
                                    {!! $salidas->appends(request()->query())->links() !!}
                                </div>
                            </div>
                        </div>
                    @elseif(method_exists($salidas, 'total') && $salidas->total() > 0)
                        <div
                            style="padding: 15px; background: white; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08); text-align: center; color: #666; font-size: 14px;">
                            Mostrando {{ $salidas->total() }} salida(s)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Búsqueda en tiempo real
            const searchInput = document.getElementById('search-salidas-main');
            const table = document.getElementById('salidas-main-table');
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

            // Confirmación de eliminación
            document.querySelectorAll('form[action*="salidas/"][method="POST"] button.btn-delete').forEach(function (btn) {
                btn.closest('form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Eliminar salida?',
                        text: '¡Esta acción no puede deshacerse!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#e12d39',
                        cancelButtonColor: '#6c757d'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            e.target.submit();
                        }
                    });
                });
            });

            @if(session('success'))
                Swal.fire({ icon: 'success', title: '', text: '{{ session('success') }}', toast: true, position: 'top-end', showConfirmButton: false, timer: 2900 });
            @endif
            @if(session('error'))
                Swal.fire({ icon: 'error', title: '', text: '{{ session('error') }}', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
            @endif
    });
    </script>
@endsection