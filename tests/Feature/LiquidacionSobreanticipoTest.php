<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\ExpenseCategory;
use App\Models\Liquidacion;
use App\Models\LiquidacionExpense;
use App\Models\User;
use App\Services\LiquidacionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionSobreanticipoTest extends TestCase
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
            'vehicle_plate' => 'P' . random_int(10000, 99999), 'active' => 1,
        ]);
    }

    private function makeLiq(User $u, Driver $d, array $over, int $gastoOp): Liquidacion
    {
        $liq = Liquidacion::create(array_merge([
            'driver_id' => $d->id, 'vehicle_plate' => $d->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 0, 'anticipo_conductor' => 0, 'sobreanticipo' => 0, 'descuentos' => 0,
            'fecha_inicio' => '2026-03-06', 'fecha_fin' => '2026-03-09', 'valor_flete' => 0,
            'estado' => Liquidacion::ESTADO_BORRADOR,
            'sumatoria_gastos_operativos' => 0, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => 0, 'total_anticipos' => 0, 'saldo_pendiente' => 0,
            'saldo_viaje' => 0, 'ganancia_viaje' => 0, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ], $over));

        $cat = ExpenseCategory::create(['code' => 'C' . random_int(1000, 999999), 'name' => 'ACPM', 'has_galones' => false, 'sort_order' => 1, 'active' => true]);
        LiquidacionExpense::create(['liquidacion_id' => $liq->id, 'expense_category_id' => $cat->id, 'valor' => $gastoOp, 'galones' => null]);

        $liq->load('expenses', 'tolls');
        LiquidacionCalculator::recalcAndSave($liq);

        return $liq->fresh();
    }

    /** @test */
    public function anticipos_conductor_suma_anticipo_y_sobreanticipo(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        // gastos op = 4.000.000 -> sumGastos = 4.000.000 (sin descuento)
        $liq = $this->makeLiq($admin, $driver, [
            'anticipo_conductor' => 3000000, 'sobreanticipo' => 500000,
        ], gastoOp: 4000000);

        // Ant - gastos = 4.000.000 - (3.000.000 + 500.000) = 500.000 (positivo -> conductor)
        $this->assertSame(500000, $liq->saldo_viaje);
        $this->assertSame(Liquidacion::AFAVOR_CONDUCTOR, $liq->a_favor_de);

        $this->actingAs($admin)->get(route('liquidaciones.show', $liq))
            ->assertOk()
            ->assertSee('Anticipos conductor')
            ->assertSee('3.500.000'); // 3.000.000 + 500.000
    }

    /** @test */
    public function ant_gastos_negativo_queda_a_favor_de_la_empresa(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        // gastos op = 1.000.000; anticipos conductor = 3.500.000 -> ant-gastos negativo
        $liq = $this->makeLiq($admin, $driver, [
            'anticipo_conductor' => 3000000, 'sobreanticipo' => 500000,
        ], gastoOp: 1000000);

        $this->assertSame(-2500000, $liq->saldo_viaje);
        $this->assertSame(Liquidacion::AFAVOR_EMPRESA, $liq->a_favor_de);
    }

    /** @test */
    public function liquidacion_sin_sobreanticipo_lo_trata_como_cero(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $liq = $this->makeLiq($admin, $driver, [
            'anticipo_conductor' => 3000000, // sin sobreanticipo -> 0 por defecto
        ], gastoOp: 4000000);

        // Ant - gastos = 4.000.000 - 3.000.000 = 1.000.000
        $this->assertSame(0, $liq->sobreanticipo);
        $this->assertSame(1000000, $liq->saldo_viaje);
    }
}
