<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class UpdateProductsCalibreFromName extends Command
{
    protected $signature = 'products:update-calibre-from-name {--recalc-weight : Forzar recálculo de peso por caja en todos los productos con calibre/alto/ancho}';
    protected $description = 'Extrae calibre, alto y ancho del nombre (y medidas) de cada producto y actualiza la BD';

    public function handle()
    {
        $products = Product::whereNull('almacen_id')->get();
        $recalcWeight = $this->option('recalc-weight');
        $updated = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $nombre = $product->nombre ?? '';
            $medidas = $product->medidas ?? '';

            $calibre = $this->extractCalibre($nombre);
            $ancho = null;
            $alto = null;

            // Dimensiones: primero del nombre (ej: "330*214" al final), sino del campo medidas
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*\*\s*(\d+(?:[.,]\d+)?)\s*$/u', $nombre, $m)) {
                $ancho = $this->normalizeDimension($m[1]);
                $alto = $this->normalizeDimension($m[2]);
            } elseif (preg_match('/^(\d+(?:[.,]\d+)?)\s*\*\s*(\d+(?:[.,]\d+)?)\s*$/u', trim($medidas), $m)) {
                $ancho = $this->normalizeDimension($m[1]);
                $alto = $this->normalizeDimension($m[2]);
            }

            if ($calibre === null && $ancho === null && $alto === null) {
                $this->line("Omitido (sin datos): {$product->codigo} - {$nombre}");
                $skipped++;
                continue;
            }

            $changed = false;
            if ($calibre !== null && (float) $product->calibre !== (float) $calibre) {
                $product->calibre = $calibre;
                $changed = true;
            }
            if ($ancho !== null && (float) ($product->ancho ?? 0) !== (float) $ancho) {
                $product->ancho = $ancho;
                $changed = true;
            }
            if ($alto !== null && (float) ($product->alto ?? 0) !== (float) $alto) {
                $product->alto = $alto;
                $changed = true;
            }

            $shouldSave = $changed;
            if (!$changed && $recalcWeight && $product->calibre !== null && $product->alto !== null && $product->ancho !== null) {
                $product->peso_empaque = $product->peso_empaque ?? 2.5;
                $product->recalculateWeightPerBox();
                $product->save();
                $updated++;
                $this->line("Recalc peso: {$product->codigo} | weight_per_box={$product->weight_per_box} | {$nombre}");
                $shouldSave = true;
            }
            if ($changed) {
                $product->peso_empaque = $product->peso_empaque ?? 2.5;
                $product->save();
                $updated++;
                $this->line("OK: {$product->codigo} | calibre={$product->calibre} alto={$product->alto} ancho={$product->ancho} | {$nombre}");
            } elseif (!$shouldSave) {
                $skipped++;
            }
        }

        $this->info("Listo. Actualizados: {$updated}, omitidos/sin cambios: {$skipped}.");
        return 0;
    }

    private function extractCalibre(string $nombre): ?float
    {
        // "3+3 MM" o "4+4MM" -> suma
        if (preg_match('/(\d+)\s*\+\s*(\d+)\s*MM/i', $nombre, $m)) {
            return (float) ((int) $m[1] + (int) $m[2]);
        }
        // "3+3" o "4+4" sin MM (ej: LAMINADO CERAMIC WHITE 3+3 330*214)
        if (preg_match('/(\d+)\s*\+\s*(\d+)(?=\s+\d|\s*\d+\s*\*)/u', $nombre, $m)) {
            return (float) ((int) $m[1] + (int) $m[2]);
        }
        // "10 MM", "4 MM", "4MM"
        if (preg_match('/(\d+)\s*MM/i', $nombre, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    private function normalizeDimension(string $value): float
    {
        $value = str_replace(',', '.', trim($value));
        $num = (float) $value;
        // Si es menor que 10 se asume metros (ej 3.66, 2.14) -> pasar a cm
        if ($num > 0 && $num < 10) {
            $num = $num * 100;
        }
        return round($num, 2);
    }
}
