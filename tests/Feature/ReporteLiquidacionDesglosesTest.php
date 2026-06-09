<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\ExpenseCategory;
use App\Models\Liquidacion;
use App\Models\LiquidacionExpense;
use App\Models\MonthlyExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteLiquidacionDesglosesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Admin', 'name' => 'admin@test.com', 'email' => 'admin@test.com',
            'rol' => 'admin', 'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(string $name, string $plate): Driver
    {
        return Driver::create([
            'name' => $name, 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate, 'active' => 1,
        ]);
    }

    private function trip(Driver $d, User $u, int $flete, int $ganancia, int $gastosOp, string $fecha): Liquidacion
    {
        return Liquidacion::create([
            'driver_id' => $d->id, 'vehicle_plate' => $d->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 0, 'anticipo_conductor' => 0, 'descuentos' => 0,
            'fecha_inicio' => $fecha, 'fecha_fin' => $fecha, 'valor_flete' => $flete,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'sumatoria_gastos_operativos' => $gastosOp, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => $flete - $ganancia, 'total_anticipos' => 0, 'saldo_pendiente' => 0,
            'saldo_viaje' => 0, 'ganancia_viaje' => $ganancia, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    private function category(string $code, string $name, int $sort): ExpenseCategory
    {
        return ExpenseCategory::create([
            'code' => $code, 'name' => $name, 'has_galones' => false, 'sort_order' => $sort, 'active' => true,
        ]);
    }

    private function expense(Liquidacion $liq, ExpenseCategory $cat, int $valor): void
    {
        LiquidacionExpense::create([
            'liquidacion_id' => $liq->id, 'expense_category_id' => $cat->id, 'valor' => $valor, 'galones' => null,
        ]);
    }

    private function fixed(Driver $d, User $u, int $sueldo, int $mes): void
    {
        MonthlyExpense::create([
            'driver_id' => $d->id, 'vehicle_plate' => $d->vehicle_plate, 'anio' => 2026, 'mes' => $mes,
            'sueldo_conductor' => $sueldo, 'seguridad_social' => 0, 'cuota_banco' => 0, 'cuota_tercero' => 0,
            'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    private function visit(User $u, array $params)
    {
        return $this->actingAs($u)->get(route('liquidaciones.reportes.index', $params));
    }

    /** @test */
    public function category_breakdown_sums_to_operativos(): void
    {
        $admin = $this->admin();
        $driver = $this->driver('A', 'AAA111');
        $acpm = $this->category('ACPM', 'ACPM', 1);
        $viaticos = $this->category('VIATICOS', 'VIÁTICOS', 2);

        $trip = $this->trip($driver, $admin, 5000000, 1000000, 800000, '2026-02-10');
        $this->expense($trip, $acpm, 500000);
        $this->expense($trip, $viaticos, 300000);

        $this->visit($admin, ['tipo' => 'mes', 'anio' => 2026, 'mes' => 2])
            ->assertOk()
            ->assertViewHas('categorias', function ($cats) {
                $total = collect($cats)->sum('total');
                $names = collect($cats)->pluck('name')->all();

                return $total === 800000
                    && in_array('VIÁTICOS', $names, true)
                    && in_array('ACPM', $names, true);
            });
    }

    /** @test */
    public function annual_evolution_identifies_best_and_worst_month(): void
    {
        $admin = $this->admin();
        $driver = $this->driver('A', 'AAA111');
        // Feb gana, Mar pierde (gasto fijo alto)
        $this->trip($driver, $admin, 5000000, 2000000, 0, '2026-02-10');
        $this->trip($driver, $admin, 5000000, 500000, 0, '2026-03-10');
        $this->fixed($driver, $admin, 3000000, 3); // marzo: utilidad neta 500k - 3M = -2.5M

        $this->visit($admin, ['tipo' => 'anio', 'anio' => 2026])
            ->assertOk()
            ->assertViewHas('mejorMes', fn ($m) => $m['periodo'] === '2026-02')
            ->assertViewHas('peorMes', fn ($m) => $m['periodo'] === '2026-03');
    }

    /** @test */
    public function per_driver_utilidad_sums_to_consolidado(): void
    {
        $admin = $this->admin();
        $d1 = $this->driver('A', 'AAA111');
        $d2 = $this->driver('B', 'BBB222');

        $this->trip($d1, $admin, 5000000, 2000000, 0, '2026-02-10');
        $this->trip($d2, $admin, 4000000, 1000000, 0, '2026-02-15');
        $this->fixed($d1, $admin, 500000, 2);
        $this->fixed($d2, $admin, 300000, 2);

        $resp = $this->visit($admin, ['tipo' => 'mes', 'anio' => 2026, 'mes' => 2])->assertOk();

        $resumen = $resp->viewData('resumen');
        $porConductor = $resp->viewData('porConductor');

        $sumaConductores = collect($porConductor)->sum('utilidad_neta');

        // Consolidado: (2M+1M) - (0.5M+0.3M) = 2.2M ; suma por conductor idéntica
        $this->assertSame(2200000, (int) $resumen['utilidad_neta']);
        $this->assertSame(2200000, (int) $sumaConductores);
        $this->assertCount(2, $porConductor);
    }

    /** @test */
    public function filtering_by_driver_scopes_the_report(): void
    {
        $admin = $this->admin();
        $d1 = $this->driver('A', 'AAA111');
        $d2 = $this->driver('B', 'BBB222');
        $this->trip($d1, $admin, 5000000, 2000000, 0, '2026-02-10');
        $this->trip($d2, $admin, 4000000, 1000000, 0, '2026-02-15');

        $this->visit($admin, ['tipo' => 'mes', 'anio' => 2026, 'mes' => 2, 'driver_id' => $d1->id])
            ->assertOk()
            ->assertViewHas('resumen', fn ($r) => (int) $r['sum_ganancia'] === 2000000 && (int) $r['count'] === 1)
            ->assertViewHas('porConductor', fn ($p) => collect($p)->isEmpty());
    }
}
