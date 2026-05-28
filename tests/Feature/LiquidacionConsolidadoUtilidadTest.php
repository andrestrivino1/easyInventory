<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\MonthlyExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionConsolidadoUtilidadTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol, string $email): User
    {
        return User::create([
            'nombre_completo' => ucfirst($rol), 'name' => $email, 'email' => $email,
            'rol' => $rol, 'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(string $plate = 'AAA111'): Driver
    {
        return Driver::create([
            'name' => 'Conductor A', 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate, 'active' => 1,
        ]);
    }

    private function trip(Driver $driver, User $creator, int $ganancia, string $fecha = '2026-02-10'): Liquidacion
    {
        return Liquidacion::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 0, 'anticipo_conductor' => 0, 'descuentos' => 0,
            'fecha_inicio' => $fecha, 'fecha_fin' => $fecha, 'valor_flete' => 0,
            'estado' => Liquidacion::ESTADO_BORRADOR,
            'sumatoria_gastos_operativos' => 0, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => 0, 'total_anticipos' => 0, 'saldo_pendiente' => 0, 'saldo_viaje' => 0,
            'ganancia_viaje' => $ganancia, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $creator->id, 'updated_by' => $creator->id,
        ]);
    }

    private function monthlyExpense(Driver $driver, User $creator, int $sueldo, int $anio = 2026, int $mes = 2): void
    {
        MonthlyExpense::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate,
            'anio' => $anio, 'mes' => $mes, 'sueldo_conductor' => $sueldo,
            'seguridad_social' => 0, 'cuota_banco' => 0, 'cuota_tercero' => 0,
            'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0,
            'created_by' => $creator->id, 'updated_by' => $creator->id,
        ]);
    }

    private function febIndex(User $actor)
    {
        return $this->actingAs($actor)->get(route('liquidaciones.index', [
            'fecha_desde' => '2026-02-01', 'fecha_hasta' => '2026-02-28',
        ]));
    }

    /** @test */
    public function consolidado_subtracts_monthly_expenses_to_show_utilidad_final(): void
    {
        $admin = $this->user('admin', 'admin@test.com');
        $driver = $this->driver();
        $this->trip($driver, $admin, 1500000);
        $this->monthlyExpense($driver, $admin, 2700000);

        $this->febIndex($admin)
            ->assertOk()
            ->assertViewHas('consolidado', function ($c) {
                return (int) $c['sum_ganancia'] === 1500000
                    && (int) $c['sum_gastos_mensuales'] === 2700000
                    && (int) $c['utilidad_final'] === -1200000; // 1.5M - 2.7M
            });
    }

    /** @test */
    public function monthly_expense_counts_once_even_with_several_trips_in_the_month(): void
    {
        $admin = $this->user('admin', 'admin@test.com');
        $driver = $this->driver();
        $this->trip($driver, $admin, 1000000, '2026-02-05');
        $this->trip($driver, $admin, 1000000, '2026-02-20');
        $this->monthlyExpense($driver, $admin, 2700000);

        $this->febIndex($admin)
            ->assertOk()
            ->assertViewHas('consolidado', function ($c) {
                return (int) $c['sum_ganancia'] === 2000000
                    && (int) $c['sum_gastos_mensuales'] === 2700000   // una sola vez, no x2
                    && (int) $c['utilidad_final'] === -700000;
            });
    }

    /** @test */
    public function placas_consolidado_does_not_expose_monthly_expenses_or_utilidad(): void
    {
        $admin = $this->user('admin', 'admin@test.com');
        $placas = $this->user('placas', 'placas@test.com');
        $driver = $this->driver();
        $placas->assignedDrivers()->sync([$driver->id]);
        $this->trip($driver, $admin, 1500000);
        $this->monthlyExpense($driver, $admin, 2700000);

        $this->febIndex($placas)
            ->assertOk()
            ->assertViewHas('consolidado', function ($c) {
                return ! array_key_exists('sum_gastos_mensuales', $c)
                    && ! array_key_exists('utilidad_final', $c);
            });
    }
}
