<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionTollDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Admin', 'name' => 'admin@test.com', 'email' => 'admin@test.com',
            'rol' => 'admin', 'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(): Driver
    {
        return Driver::create([
            'name' => 'Conductor A', 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => 'AAA111', 'active' => 1,
        ]);
    }

    private function basePayload(Driver $driver): array
    {
        return [
            'driver_id' => $driver->id,
            'vehicle_plate' => $driver->vehicle_plate,
            'transportadora' => 'Transporte X',
            'anticipo_empresa' => 0,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
            'valor_flete' => 0,
        ];
    }

    private function toll(string $name, int $valor, int $order): array
    {
        return [
            'name' => $name, 'valor' => $valor, 'sort_order' => $order,
            'direction' => 'ida', 'is_used' => 1, 'paid_by' => 'empresa', 'is_adhoc' => 1,
        ];
    }

    /** @test */
    public function removing_a_toll_from_the_update_payload_deletes_it_and_recomputes_totals(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        // Crear con 2 peajes (80.000).
        $this->actingAs($admin)->post(route('liquidaciones.store'), array_merge($this->basePayload($driver), [
            'tolls' => [$this->toll('P1', 50000, 1), $this->toll('P2', 30000, 2)],
        ]))->assertRedirect();

        $liq = Liquidacion::first();
        $this->assertSame(2, $liq->tolls()->count());
        $this->assertSame(80000, (int) $liq->sumatoria_peajes);

        // Actualizar dejando solo 1 peaje (50.000) -> el otro se elimina.
        $this->actingAs($admin)->put(route('liquidaciones.update', $liq), array_merge($this->basePayload($driver), [
            'tolls' => [$this->toll('P1', 50000, 1)],
        ]))->assertRedirect();

        $liq->refresh();
        $this->assertSame(1, $liq->tolls()->count());
        $this->assertSame(50000, (int) $liq->sumatoria_peajes);
        $this->assertDatabaseMissing('liquidacion_tolls', ['liquidacion_id' => $liq->id, 'name' => 'P2']);
    }
}
