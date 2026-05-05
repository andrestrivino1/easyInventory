<?php

namespace App\Console\Commands;

use App\Models\Import;
use App\Models\Itr;
use Illuminate\Console\Command;

class SyncItrsFromImports extends Command
{
    protected $signature = 'itr:sync-from-imports';
    protected $description = 'Crea registros ITR para importaciones con arribo confirmado que aún no tienen ITR';

    public function handle()
    {
        $imports = Import::whereNotNull('actual_arrival_date')
            ->whereDoesntHave('itr')
            ->get();

        if ($imports->isEmpty()) {
            $this->info('No hay importaciones con arribo confirmado sin ITR.');
            return 0;
        }

        $created = 0;
        foreach ($imports as $import) {
            $diasLibres = (int) ($import->free_days_at_dest ?? 4);
            $fechaLlegada = \Carbon\Carbon::parse($import->actual_arrival_date);
            // Fecha vencimiento = fecha llegada + días libres - 4 días (por defecto)
            $diasParaVencimiento = max(0, $diasLibres - 4);
            $fechaVencimiento = $fechaLlegada->copy()->addDays($diasParaVencimiento);

            Itr::create([
                'import_id' => $import->id,
                'do_code' => $import->do_code ?? 'DO-' . $import->id,
                'bl_number' => $import->bl_number,
                'fecha_llegada' => $fechaLlegada,
                'dias_libres' => $diasLibres,
                'fecha_vencimiento' => $fechaVencimiento,
            ]);
            $created++;
            $this->line("ITR creado para importación DO: " . ($import->do_code ?? $import->id));
        }

        $this->info("Se crearon {$created} registro(s) ITR.");
        return 0;
    }
}
