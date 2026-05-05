@extends('layouts.app')

@section('content')
<div class="container-fluid" style="padding-top:32px; min-height:88vh;">
    <div class="mx-auto" style="max-width:1200px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="color:#333;font-weight:bold;">Salida #{{ $salida->salida_number }}</h2>
            <div>
                <a href="{{ route('salidas.print', $salida) }}" class="btn btn-outline-secondary" target="_blank" style="margin-right:10px;"><i class="bi bi-printer"></i> Imprimir</a>
                <a href="{{ route('salidas.index') }}" class="btn btn-secondary">Volver</a>
            </div>
        </div>

        <div style="background:white; padding:25px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08); margin-bottom:20px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:20px;">
                <div>
                    <strong>Bodega:</strong> {{ $salida->warehouse->nombre ?? '-' }}
                </div>
                <div>
                    <strong>Fecha:</strong> {{ $salida->fecha->format('d/m/Y') }}
                </div>
                <div>
                    <strong>A nombre de:</strong> {{ $salida->a_nombre_de }}
                </div>
                <div>
                    <strong>NIT/Cédula:</strong> {{ $salida->nit_cedula }}
                </div>
            </div>
            @if($salida->note)
            <div style="margin-top:15px; padding:10px; background:#f8f9fa; border-radius:6px;">
                <strong>Notas:</strong> {{ $salida->note }}
            </div>
            @endif
        </div>

        <div style="background:white; padding:25px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08);">
            <h3 style="margin-bottom:20px; color:#333;">Productos</h3>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#0066cc; color:white;">
                        <th style="padding:12px; text-align:left;">Producto</th>
                        <th style="padding:12px; text-align:left;">Medidas</th>
                        <th style="padding:12px; text-align:center;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salida->products as $prod)
                        <tr style="border-bottom:1px solid #e6e6e6;">
                            <td style="padding:12px;">{{ $prod->nombre }} ({{ $prod->codigo }})</td>
                            <td style="padding:12px;">{{ $prod->medidas ?? '-' }}</td>
                            <td style="padding:12px; text-align:center;">{{ $prod->pivot->quantity }} láminas</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

