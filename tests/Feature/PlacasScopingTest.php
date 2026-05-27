<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlacasScopingTest extends TestCase
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

    private function makeLiquidacion(Driver $driver, User $creator, string $estado = Liquidacion::ESTADO_BORRADOR): Liquidacion
    {
        return Liquidacion::create([
            'driver_id' => $driver->id,
            'vehicle_plate' => $driver->vehicle_plate,
            'route_id' => null,
            'transportadora' => 'Transporte X',
            'anticipo_empresa' => 0,
            'anticipo_conductor' => 0,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
            'valor_flete' => 0,
            'estado' => $estado,
            'sumatoria_gastos_operativos' => 0,
            'sumatoria_peajes' => 0,
            'sumatoria_gastos_totales' => 0,
            'total_anticipos' => 0,
            'saldo_viaje' => 0,
            'ganancia_viaje' => 0,
            'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
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

    /** @test */
    public function index_only_lists_liquidaciones_of_assigned_drivers(): void
    {
        $assigned = $this->makeDriver('Conductor A', 'AAA111');
        $other = $this->makeDriver('Conductor B', 'BBB222');
        $placas = $this->placasWith($assigned);

        $this->makeLiquidacion($assigned, $placas);
        $this->makeLiquidacion($other, $placas);

        $response = $this->actingAs($placas)->get(route('liquidaciones.index'));

        $response->assertOk();
        $response->assertSee('AAA111');
        $response->assertDontSee('BBB222');
    }

    /** @test */
    public function show_of_assigned_driver_is_allowed(): void
    {
        $assigned = $this->makeDriver('Conductor A', 'AAA111');
        $placas = $this->placasWith($assigned);
        $liq = $this->makeLiquidacion($assigned, $placas);

        $this->actingAs($placas)->get(route('liquidaciones.show', $liq))->assertOk();
    }

    /** @test */
    public function show_of_non_assigned_driver_is_forbidden(): void
    {
        $assigned = $this->makeDriver('Conductor A', 'AAA111');
        $other = $this->makeDriver('Conductor B', 'BBB222');
        $placas = $this->placasWith($assigned);
        $foreign = $this->makeLiquidacion($other, $placas);

        $this->actingAs($placas)->get(route('liquidaciones.show', $foreign))->assertForbidden();
    }
}
