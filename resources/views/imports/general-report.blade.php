<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe General de Importaciones</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 landscape;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 8px; 
            color: #232323; 
            margin: 0;
            padding: 5px;
        }
        .title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-top: 10px; 
            margin-bottom: 5px; 
            text-align: center; 
        }
        .subtitle { 
            color: #007bff; 
            font-size: 11px; 
            margin-bottom: 10px; 
            text-align: center; 
        }
        .label { 
            color: #666; 
            font-size: 10px; 
            font-weight: bold; 
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 15px;
            page-break-inside: auto;
            font-size: 7px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 3px 2px;
            text-align: left;
            font-size: 7px;
            word-wrap: break-word;
        }
        th {
            background: #edf5ff;
            font-weight: bold;
            font-size: 7px;
            padding: 4px 2px;
        }
        tr { 
            page-break-inside: avoid; 
            page-break-after: auto; 
        }
        .section-title { 
            font-size: 12px; 
            font-weight: bold; 
            margin-top: 15px; 
            margin-bottom: 8px; 
            color: #0066cc; 
        }
        .footer {
            margin-top: 20px; 
            text-align: right; 
            font-size: 9px;
            color: #777;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 4px;
            font-size: 6px;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-pending {
            background: #ffc107;
            color: #212529;
        }
        .status-completed {
            background: #4caf50;
            color: white;
        }
        .status-in-transit {
            background: #0dcaf0;
            color: #212529;
        }
        .status-recibido {
            background: #17a2b8;
            color: white;
        }
        .col-do { width: 5%; }
        .col-usuario { width: 8%; }
        .col-invoice { width: 6%; }
        .col-proforma { width: 6%; }
        .col-bl { width: 6%; }
        .col-origen { width: 7%; }
        .col-destino { width: 7%; }
        .col-fecha { width: 5%; }
        .col-naviera { width: 8%; }
        .col-dias { width: 4%; }
        .col-estado { width: 6%; }
        .col-creditos { width: 4%; }
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div style="width:100%;text-align:center;margin-top:5px;margin-bottom:10px;">
        @php
            $logoPath = public_path('logo.png');
            $logoBase64 = '';
            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
            }
        @endphp
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" style="max-width:120px;max-height:50px;">
        @else
            <img src="{{ asset('logo.png') }}" style="max-width:120px;max-height:50px;">
        @endif
    </div>
    <div class="title">Informe General de Importaciones</div>
    <div class="subtitle">Fecha: {{ date('d/m/Y H:i') }}</div>

    <!-- Sección de Importaciones -->
    <div class="section-title">IMPORTACIONES</div>
    <table>
        <thead>
            <tr>
                <th class="col-do">DO</th>
                <th class="col-usuario">Usuario</th>
                <th class="col-invoice">N° Com. Invoice</th>
                <th class="col-proforma">N° Prof. Invoice</th>
                <th class="col-bl">N° Bill of Lading</th>
                <th class="col-origen">Origen</th>
                <th class="col-destino">Destino</th>
                <th class="col-fecha">F. Salida</th>
                <th class="col-fecha">F. Llegada</th>
                <th class="col-naviera">Naviera/Agencia</th>
                <th class="col-dias">Días Libres</th>
                <th class="col-estado">Estado</th>
                <th class="col-creditos">Créditos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($imports as $import)
            @php
                $departureDate = $import->departure_date ? \Carbon\Carbon::parse($import->departure_date) : null;
                $arrivalDate = $import->arrival_date ? \Carbon\Carbon::parse($import->arrival_date) : null;
                // Acortar nombres de usuario si son muy largos
                $userName = $import->user->nombre_completo ?? $import->user->email;
                if (strlen($userName) > 20) {
                    $userName = substr($userName, 0, 17) . '...';
                }
            @endphp
            <tr>
                <td class="col-do"><strong>{{ $import->do_code }}</strong></td>
                <td class="col-usuario">{{ $userName }}</td>
                <td class="col-invoice">{{ $import->commercial_invoice_number ?? '-' }}</td>
                <td class="col-proforma">{{ $import->proforma_invoice_number ?? '-' }}</td>
                <td class="col-bl">{{ $import->bl_number ?? '-' }}</td>
                <td class="col-origen">{{ $import->origin ?? '-' }}</td>
                <td class="col-destino">{{ $import->destination ?? 'Colombia' }}</td>
                <td class="col-fecha">{{ $departureDate ? $departureDate->format('d/m/Y') : '-' }}</td>
                <td class="col-fecha">{{ $arrivalDate ? $arrivalDate->format('d/m/Y') : '-' }}</td>
                <td class="col-fecha">{{ $import->created_at ? $import->created_at->format('d/m/Y H:i') : '-' }}</td>
                <td class="col-naviera">{{ $import->shipping_company ?? '-' }}</td>
                <td class="col-dias">{{ $import->free_days_at_dest ?? '-' }}</td>
                <td class="col-estado">
                    @if($import->status === 'pending')
                        <span class="status-badge status-pending">Pend.</span>
                    @elseif($import->status === 'completed')
                        <span class="status-badge status-completed">Comp.</span>
                    @elseif($import->status === 'in_transit')
                        <span class="status-badge status-in-transit">Tránsito</span>
                    @elseif($import->status === 'recibido')
                        <span class="status-badge status-recibido">Arribo Confirmado</span>
                    @elseif($import->status === 'pendiente_por_confirmar')
                        <span class="status-badge" style="background: #ff9800; color: white;">Pend. conf.</span>
                    @else
                        {{ ucfirst(substr($import->status, 0, 6)) }}
                    @endif
                </td>
                <td class="col-creditos">
                    @if($import->credit_time)
                        {{ $import->credit_time }}d
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer" style="margin-top:60px; text-align:right; font-size:13px;color:#777;">
        Generado por VIDRIOS J&P S.A.S. - {{ now()->format('d/m/Y h:i A') }}
    </div>
</body>
</html>
