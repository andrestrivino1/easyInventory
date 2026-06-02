<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\ExpenseCategory;
use App\Models\Liquidacion;
use App\Models\LiquidacionExpense;
use App\Models\LiquidacionToll;
use App\Models\User;
use App\Services\LiquidacionCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionPanelTotalesTest extends TestCase
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

    /**
     * Crea una liquidación con un gasto operativo y un peaje, recalculada.
     */
    private function makeLiq(User $u, Driver $d, array $over, int $gastoOp, int $peaje): Liquidacion
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

        $cat = ExpenseCategory::create(['code' => 'C' . random_int(1000, 999999), 'name' => 'ACPM', 'has_galones' => true, 'sort_order' => 1, 'active' => true]);
        LiquidacionExpense::create(['liquidacion_id' => $liq->id, 'expense_category_id' => $cat->id, 'valor' => $gastoOp, 'galones' => null]);
        LiquidacionToll::create(['liquidacion_id' => $liq->id, 'name' => 'Peaje X', 'valor' => $peaje, 'sort_order' => 1, 'direction' => 'ida', 'is_adhoc' => false, 'is_used' => true, 'paid_by' => 'empresa']);

        $liq->load('expenses', 'tolls');
        LiquidacionCalculator::recalcAndSave($liq);

        return $liq->fresh();
    }

    /** @test */
    public function recalc_aplica_las_formulas_nuevas_del_panel(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $liq = $this->makeLiq($admin, $driver, [
            'anticipo_empresa' => 4690000, 'anticipo_conductor' => 3000000, 'sobreanticipo' => 500000,
            'descuentos' => 100000, 'valor_flete' => 6700000,
        ], gastoOp: 3159000, peaje: 981000);

        // Sumatoria de gastos = 3.159.000 + 100.000 (descuento) -> total con peajes
        $this->assertSame(3159000, $liq->sumatoria_gastos_operativos);
        $this->assertSame(4240000, $liq->sumatoria_gastos_totales);      // 3.159.000 + 100.000 + 981.000
        $this->assertSame(2010000, $liq->saldo_pendiente);               // saldo adeudado empresa = 6.700.000 - 4.690.000
        $this->assertSame(-241000, $liq->saldo_viaje);                   // ant-gastos = 3.259.000 - 3.500.000
        $this->assertSame(2460000, $liq->ganancia_viaje);                // 6.700.000 - 4.240.000
        $this->assertSame(8190000, $liq->total_anticipos);               // 4.690.000 + 3.000.000 + 500.000
        $this->assertSame(Liquidacion::AFAVOR_EMPRESA, $liq->a_favor_de); // ant-gastos < 0 -> empresa
    }

    /** @test */
    public function show_muestra_las_etiquetas_y_valores_del_panel_reordenado(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $liq = $this->makeLiq($admin, $driver, [
            'anticipo_empresa' => 4690000, 'anticipo_conductor' => 3000000, 'sobreanticipo' => 500000,
            'descuentos' => 100000, 'valor_flete' => 6700000,
        ], gastoOp: 3159000, peaje: 981000);

        $this->actingAs($admin)->get(route('liquidaciones.show', $liq))
            ->assertOk()
            ->assertSee('Sumatoria de gastos')
            ->assertSee('Suma de gastos total de viaje')
            ->assertSee('Saldo adeudado empresa de transporte')
            ->assertSee('Anticipos conductor')
            ->assertSee('Ant - gastos')
            ->assertSee('Ganancia final de viaje')
            ->assertSee('3.259.000')   // Sumatoria de gastos (op + descuento)
            ->assertSee('2.010.000')   // Saldo adeudado empresa
            ->assertSee('2.460.000');  // Ganancia final
    }

    /** @test */
    public function peaje_pagado_por_conductor_cuenta_como_gasto_suyo_en_ant_gastos(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $liq = Liquidacion::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 4689997, 'anticipo_conductor' => 3000000, 'sobreanticipo' => 0, 'descuentos' => 0,
            'fecha_inicio' => '2026-03-25', 'fecha_fin' => '2026-03-30', 'valor_flete' => 6700000,
            'estado' => Liquidacion::ESTADO_BORRADOR,
            'sumatoria_gastos_operativos' => 0, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => 0, 'total_anticipos' => 0, 'saldo_pendiente' => 0,
            'saldo_viaje' => 0, 'ganancia_viaje' => 0, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);

        $cat = ExpenseCategory::create(['code' => 'C' . random_int(1000, 999999), 'name' => 'ACPM', 'has_galones' => true, 'sort_order' => 1, 'active' => true]);
        LiquidacionExpense::create(['liquidacion_id' => $liq->id, 'expense_category_id' => $cat->id, 'valor' => 3007050, 'galones' => null]);
        // Peaje que paga la empresa + peaje que paga el conductor (de su bolsillo).
        LiquidacionToll::create(['liquidacion_id' => $liq->id, 'name' => 'Empresa', 'valor' => 911200, 'sort_order' => 1, 'direction' => 'ida', 'is_adhoc' => false, 'is_used' => true, 'paid_by' => 'empresa']);
        LiquidacionToll::create(['liquidacion_id' => $liq->id, 'name' => 'Chicoral', 'valor' => 58000, 'sort_order' => 2, 'direction' => 'ida', 'is_adhoc' => false, 'is_used' => true, 'paid_by' => 'conductor']);

        $liq->load('expenses', 'tolls');
        LiquidacionCalculator::recalcAndSave($liq);
        $liq = $liq->fresh();

        // El peaje del conductor (58.000) entra en "Ant - gastos" como gasto suyo,
        // pero NO se resta de la sumatoria de peajes ni se duplica en el total.
        $this->assertSame(58000, $liq->sumatoria_peajes_conductor);
        $this->assertSame(969200, $liq->sumatoria_peajes);                    // todos los peajes (sin restar el del conductor)
        $this->assertSame(65050, $liq->saldo_viaje);                          // (3.007.050 + 58.000) − 3.000.000
        $this->assertSame(Liquidacion::AFAVOR_CONDUCTOR, $liq->a_favor_de);
        $this->assertSame(3976250, $liq->sumatoria_gastos_totales);           // 3.007.050 + 969.200 (peaje conductor contado una vez)
        $this->assertSame(2723750, $liq->ganancia_viaje);                     // 6.700.000 − 3.976.250 (sin duplicar)

        // El panel muestra el peaje del conductor sumado en "Sumatoria de gastos".
        $this->actingAs($admin)->get(route('liquidaciones.show', $liq))
            ->assertOk()
            ->assertSee('3.065.050')   // Sumatoria de gastos = 3.007.050 + 58.000
            ->assertSee('969.200');    // Sumatoria de peajes (todos)
    }

    /** @test */
    public function el_descuento_empresa_suma_a_gastos_y_baja_la_ganancia(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $sinDescuento = $this->makeLiq($admin, $this->driver(), [
            'valor_flete' => 6700000,
        ], gastoOp: 3159000, peaje: 981000);
        $this->assertSame(4140000, $sinDescuento->sumatoria_gastos_totales); // 3.159.000 + 0 + 981.000

        $conDescuento = $this->makeLiq($admin, $driver, [
            'valor_flete' => 6700000, 'descuentos' => 100000,
        ], gastoOp: 3159000, peaje: 981000);
        $this->assertSame(4240000, $conDescuento->sumatoria_gastos_totales); // +100.000
        $this->assertSame(
            $sinDescuento->ganancia_viaje - 100000,
            $conDescuento->ganancia_viaje
        );
    }
}
