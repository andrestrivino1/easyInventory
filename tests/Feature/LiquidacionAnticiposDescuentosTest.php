<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionAnticiposDescuentosTest extends TestCase
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

    private function payload(Driver $driver, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driver->id,
            'vehicle_plate' => $driver->vehicle_plate,
            'transportadora' => 'Transporte X',
            'anticipo_empresa' => 1000000,
            'anticipo_conductor' => 200000,
            'descuentos' => 300000,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
            'valor_flete' => 5000000,
        ], $overrides);
    }

    /** @test */
    public function saldo_adeudado_empresa_and_total_anticipos_are_computed_on_store(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        // sobreanticipo entra en total_anticipos (feature 005)
        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'sobreanticipo' => 50000,
        ]))->assertRedirect();

        $liq = Liquidacion::first();
        $this->assertSame(1250000, (int) $liq->total_anticipos);          // empresa + conductor + sobreanticipo
        $this->assertSame(4000000, (int) $liq->saldo_pendiente);          // saldo adeudado empresa = flete - anticipo empresa
        $this->assertSame(300000, (int) $liq->descuentos);
    }

    /** @test */
    public function saldo_adeudado_empresa_can_be_negative_when_anticipo_exceeds_flete(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        // anticipo empresa (6.000.000) > valor flete (5.000.000) -> saldo adeudado negativo
        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'anticipo_empresa' => 6000000,
        ]))->assertRedirect();

        $this->assertSame(-1000000, (int) Liquidacion::first()->saldo_pendiente);
    }

    /** @test */
    public function derived_fields_are_not_taken_from_client_input(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'saldo_pendiente' => 999999999, 'total_anticipos' => 888888888,
        ]))->assertRedirect();

        $liq = Liquidacion::first();
        $this->assertSame(4000000, (int) $liq->saldo_pendiente);          // flete - anticipo empresa
        $this->assertSame(1200000, (int) $liq->total_anticipos);          // empresa + conductor (sobreanticipo 0)
    }

    /** @test */
    public function consolidado_aggregates_descuentos(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver));

        $this->actingAs($admin)->get(route('liquidaciones.index'))
            ->assertOk()
            ->assertViewHas('consolidado', fn ($c) => (int) $c['sum_descuentos'] === 300000);
    }
}
