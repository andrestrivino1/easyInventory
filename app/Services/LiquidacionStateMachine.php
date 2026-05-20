<?php

namespace App\Services;

use App\Models\Liquidacion;
use App\Models\LiquidacionStateLog;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class LiquidacionStateMachine
{
    /**
     * Borrador → Cerrada. Sin motivo.
     */
    public function close(Liquidacion $liq, User $user): void
    {
        if (!$liq->isBorrador()) {
            throw new DomainException('Solo se puede cerrar una liquidación en estado Borrador.');
        }

        DB::transaction(function () use ($liq, $user) {
            $from = $liq->estado;
            $liq->estado = Liquidacion::ESTADO_CERRADA;
            $liq->updated_by = $user->id;
            $liq->save();

            LiquidacionStateLog::create([
                'liquidacion_id' => $liq->id,
                'user_id' => $user->id,
                'from_state' => $from,
                'to_state' => Liquidacion::ESTADO_CERRADA,
                'motivo' => null,
            ]);
        });
    }

    /**
     * Cerrada → Borrador. Requiere motivo (validado al nivel del request).
     */
    public function reopen(Liquidacion $liq, User $user, string $motivo): void
    {
        if (!$liq->isCerrada()) {
            throw new DomainException('Solo se puede reabrir una liquidación en estado Cerrada.');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new DomainException('Se requiere un motivo para reabrir la liquidación.');
        }

        DB::transaction(function () use ($liq, $user, $motivo) {
            $from = $liq->estado;
            $liq->estado = Liquidacion::ESTADO_BORRADOR;
            $liq->updated_by = $user->id;
            $liq->save();

            LiquidacionStateLog::create([
                'liquidacion_id' => $liq->id,
                'user_id' => $user->id,
                'from_state' => $from,
                'to_state' => Liquidacion::ESTADO_BORRADOR,
                'motivo' => $motivo,
            ]);
        });
    }

    /**
     * Cerrada → Anulada. Terminal. Requiere motivo.
     */
    public function cancel(Liquidacion $liq, User $user, string $motivo): void
    {
        if (!$liq->isCerrada()) {
            throw new DomainException('Solo se puede anular una liquidación en estado Cerrada.');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new DomainException('Se requiere un motivo para anular la liquidación.');
        }

        DB::transaction(function () use ($liq, $user, $motivo) {
            $from = $liq->estado;
            $liq->estado = Liquidacion::ESTADO_ANULADA;
            $liq->motivo_anulacion = $motivo;
            $liq->updated_by = $user->id;
            $liq->save();

            LiquidacionStateLog::create([
                'liquidacion_id' => $liq->id,
                'user_id' => $user->id,
                'from_state' => $from,
                'to_state' => Liquidacion::ESTADO_ANULADA,
                'motivo' => $motivo,
            ]);
        });
    }
}
