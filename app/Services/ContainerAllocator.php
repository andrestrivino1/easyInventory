<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reparto de cajas entre los contenedores de una bodega (funcionalidad 008).
 *
 * Cuando el usuario NO elige contenedor, el descuento debe salir igualmente de
 * filas concretas de `container_product`, que es donde vive el saldo físico.
 * Este servicio decide de cuáles y en qué orden, sin que el usuario intervenga.
 *
 * Dos detalles que condicionan la implementación:
 *
 *  1. Se itera POR FILA, no por contenedor. El índice
 *     unique(container_id, product_id) de `container_product` fue eliminado en
 *     la migración 2026_01_25_000000, así que un mismo producto puede aparecer
 *     varias veces en el mismo contenedor con distinto `sheets_per_box`.
 *
 *  2. Se valida el total ANTES de descontar nada. Un reparto que consumiera los
 *     primeros contenedores y luego descubriera que falta cantidad dejaría el
 *     stock descuadrado en silencio.
 */
class ContainerAllocator
{
    /**
     * Filas de saldo de un producto en una bodega, en orden FIFO.
     *
     * FIFO = orden de creación del contenedor (`containers.id` ascendente).
     */
    public function rows(int $warehouseId, int $productId, ?int $sheetsPerBox = null): array
    {
        $query = DB::table('container_product')
            ->join('containers', 'container_product.container_id', '=', 'containers.id')
            ->where('containers.warehouse_id', $warehouseId)
            ->where('container_product.product_id', $productId)
            ->where('container_product.boxes', '>', 0);

        if ($sheetsPerBox !== null && $sheetsPerBox > 0) {
            $query->where('container_product.sheets_per_box', $sheetsPerBox);
        }

        return $query
            ->orderBy('containers.id')
            ->orderBy('container_product.id')
            ->select(
                'container_product.id as pivot_id',
                'container_product.container_id',
                'container_product.boxes',
                'container_product.sheets_per_box'
            )
            ->get()
            ->all();
    }

    /** Total de cajas disponibles del producto en la bodega, sumando contenedores. */
    public function availableBoxes(int $warehouseId, int $productId, ?int $sheetsPerBox = null): int
    {
        $total = 0;

        foreach ($this->rows($warehouseId, $productId, $sheetsPerBox) as $row) {
            $total += (int) $row->boxes;
        }

        return $total;
    }

    /**
     * Calcula el plan de reparto sin aplicarlo.
     *
     * @return array<int, array{pivot_id:int, container_id:int, take:int}>
     *
     * @throws RuntimeException si el total disponible no alcanza
     */
    public function plan(int $warehouseId, int $productId, int $boxes, ?int $sheetsPerBox = null): array
    {
        if ($boxes < 1) {
            throw new RuntimeException('La cantidad debe ser mayor que cero.');
        }

        $rows = $this->rows($warehouseId, $productId, $sheetsPerBox);
        $available = 0;
        foreach ($rows as $row) {
            $available += (int) $row->boxes;
        }

        // Comprobar el total antes de tocar nada: sin esto un reparto podría
        // consumir los primeros contenedores y fallar a mitad.
        if ($available < $boxes) {
            throw new RuntimeException(
                "No hay suficientes cajas disponibles. Disponible: {$available} cajas, solicitado: {$boxes}."
            );
        }

        $plan = [];
        $remaining = $boxes;

        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((int) $row->boxes, $remaining);
            $remaining -= $take;

            $plan[] = [
                'pivot_id' => (int) $row->pivot_id,
                'container_id' => (int) $row->container_id,
                'take' => $take,
            ];
        }

        return $plan;
    }

    /**
     * Aplica un plan de reparto descontando fila por fila.
     *
     * Debe invocarse dentro de una transacción: quien llama ya la tiene abierta.
     */
    public function apply(array $plan): void
    {
        foreach ($plan as $step) {
            DB::table('container_product')
                ->where('id', $step['pivot_id'])
                ->decrement('boxes', $step['take']);
        }
    }

    /**
     * Valida y descuenta en un solo paso.
     *
     * @return array<int, array{pivot_id:int, container_id:int, take:int}> el plan aplicado
     *
     * @throws RuntimeException si el total disponible no alcanza
     */
    public function deduct(int $warehouseId, int $productId, int $boxes, ?int $sheetsPerBox = null): array
    {
        $plan = $this->plan($warehouseId, $productId, $boxes, $sheetsPerBox);

        $this->apply($plan);

        return $plan;
    }

    /** Repone un plan ya aplicado (usado al editar o anular una transferencia). */
    public function restore(array $plan): void
    {
        foreach ($plan as $step) {
            DB::table('container_product')
                ->where('id', $step['pivot_id'])
                ->increment('boxes', $step['take']);
        }
    }

    /**
     * Devuelve cajas a la bodega cuando el movimiento se registró SIN contenedor.
     *
     * Al editar o anular una transferencia sin contenedor hay que reponer el
     * saldo, pero no se guardó de qué filas concretas salió. Se repone sobre la
     * primera fila del producto en orden FIFO: el TOTAL de la bodega vuelve a
     * ser exacto, que es lo que determina el stock; el reparto entre
     * contenedores puede diferir del original, algo irrelevante para un
     * movimiento que por definición no tiene contenedor asociado.
     *
     * Ojo: aquí NO se filtra por `boxes > 0`, porque la fila de la que se
     * descontó puede haber quedado justamente en cero y hay que encontrarla.
     */
    public function restoreToWarehouse(int $warehouseId, int $productId, int $boxes, ?int $sheetsPerBox = null): bool
    {
        if ($boxes < 1) {
            return true;
        }

        $query = DB::table('container_product')
            ->join('containers', 'container_product.container_id', '=', 'containers.id')
            ->where('containers.warehouse_id', $warehouseId)
            ->where('container_product.product_id', $productId);

        if ($sheetsPerBox !== null && $sheetsPerBox > 0) {
            $query->where('container_product.sheets_per_box', $sheetsPerBox);
        }

        $row = $query
            ->orderBy('containers.id')
            ->orderBy('container_product.id')
            ->select('container_product.id as pivot_id')
            ->first();

        if (! $row) {
            // No queda ninguna fila donde reponer: el contenedor fue eliminado.
            // Se informa al llamador en lugar de perder el saldo en silencio.
            return false;
        }

        DB::table('container_product')
            ->where('id', $row->pivot_id)
            ->increment('boxes', $boxes);

        return true;
    }
}
