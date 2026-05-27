<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacasFullFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeDriver(string $name, string $plate): Driver
    {
        return Driver::create([
            'name' => $name,
            'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate,
            'active' => 1,
        ]);
    }

    private function placasWith(Driver ...$drivers): User
    {
        $user = User::create([
            'nombre_completo' => 'Usuario Placas',
            'name' => 'placas@test.com',
            'email' => 'placas@test.com',
            'rol' => 'placas',
            'password' => bcrypt('secret123'),
        ]);
        $user->assignedDrivers()->sync(collect($drivers)->pluck('id')->all());

        return $user;
    }

    private function storePayload(Driver $driver): array
    {
        return [
            'driver_id' => $driver->id,
            'vehicle_plate' => $driver->vehicle_plate,
            'transportadora' => 'Transporte X',
            'anticipo' => 0,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
            'valor_flete' => 100000,
        ];
    }

    private function makeBorrador(Driver $driver, User $creator): Liquidacion
    {
        return Liquidacion::create(array_merge($this->storePayload($driver), [
            'estado' => Liquidacion::ESTADO_BORRADOR,
            'sobreanticipo' => 0,
            'sumatoria_gastos_operativos' => 0,
            'sumatoria_peajes' => 0,
            'sumatoria_gastos_totales' => 0,
            'total_anticipos' => 0,
            'saldo_viaje' => 0,
            'ganancia_viaje' => 0,
            'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]));
    }

    /** @test */
    public function placas_can_create_liquidacion_for_assigned_driver(): void
    {
        $driver = $this->makeDriver('Conductor A', 'AAA111');
        $placas = $this->placasWith($driver);

        $response = $this->actingAs($placas)->post(route('liquidaciones.store'), $this->storePayload($driver));

        $response->assertRedirect();
        $this->assertDatabaseHas('liquidaciones', [
            'driver_id' => $driver->id,
            'created_by' => $placas->id,
            'estado' => Liquidacion::ESTADO_BORRADOR,
        ]);
    }

    /** @test */
    public function placas_cannot_create_liquidacion_for_unassigned_driver(): void
    {
        $assigned = $this->makeDriver('Conductor A', 'AAA111');
        $unassigned = $this->makeDriver('Conductor B', 'BBB222');
        $placas = $this->placasWith($assigned);

        $response = $this->actingAs($placas)->post(route('liquidaciones.store'), $this->storePayload($unassigned));

        $response->assertSessionHasErrors('driver_id');
        $this->assertDatabaseMissing('liquidaciones', ['driver_id' => $unassigned->id]);
    }

    /** @test */
    public function placas_can_close_reopen_and_cancel_assigned_liquidacion(): void
    {
        $driver = $this->makeDriver('Conductor A', 'AAA111');
        $placas = $this->placasWith($driver);
        $liq = $this->makeBorrador($driver, $placas);

        // Cerrar
        $this->actingAs($placas)->post(route('liquidaciones.cerrar', $liq))->assertRedirect();
        $this->assertSame(Liquidacion::ESTADO_CERRADA, $liq->fresh()->estado);

        // Reabrir (motivo requerido)
        $this->actingAs($placas)->post(route('liquidaciones.reabrir', $liq), ['motivo' => 'Corrección de valores del flete'])->assertRedirect();
        $this->assertSame(Liquidacion::ESTADO_BORRADOR, $liq->fresh()->estado);

        // Cerrar de nuevo y anular
        $this->actingAs($placas)->post(route('liquidaciones.cerrar', $liq));
        $this->actingAs($placas)->post(route('liquidaciones.anular', $liq), ['motivo' => 'Liquidación duplicada por error'])->assertRedirect();
        $this->assertSame(Liquidacion::ESTADO_ANULADA, $liq->fresh()->estado);
    }

    /** @test */
    public function placas_can_download_pdf_of_assigned_liquidacion(): void
    {
        $driver = $this->makeDriver('Conductor A', 'AAA111');
        $placas = $this->placasWith($driver);
        $liq = $this->makeBorrador($driver, $placas);

        $this->actingAs($placas)->get(route('liquidaciones.pdf', $liq))->assertOk();
    }

    /** @test */
    public function placas_cannot_act_on_unassigned_liquidacion(): void
    {
        $assigned = $this->makeDriver('Conductor A', 'AAA111');
        $other = $this->makeDriver('Conductor B', 'BBB222');
        $placas = $this->placasWith($assigned);
        $foreign = $this->makeBorrador($other, $placas);

        $this->actingAs($placas)->post(route('liquidaciones.cerrar', $foreign))->assertForbidden();
        $this->actingAs($placas)->get(route('liquidaciones.edit', $foreign))->assertForbidden();
        $this->actingAs($placas)->delete(route('liquidaciones.destroy', $foreign))->assertForbidden();
    }
}
