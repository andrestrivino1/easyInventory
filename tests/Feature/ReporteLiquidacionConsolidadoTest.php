<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\MonthlyExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteLiquidacionConsolidadoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Admin', 'name' => 'admin@test.com', 'email' => 'admin@test.com',
            'rol' => 'admin', 'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(string $plate = 'AAA111'): Driver
    {
        return Driver::create([
            'name' => 'Conductor A', 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate, 'active' => 1,
        ]);
    }

    private function trip(Driver $d, User $u, int $flete, int $ganancia, string $estado, string $fecha = '2026-02-10'): Liquidacion
    {
        return Liquidacion::create([
            'driver_id' => $d->id, 'vehicle_plate' => $d->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 0, 'anticipo_conductor' => 0, 'descuentos' => 0,
            'fecha_inicio' => $fecha, 'fecha_fin' => $fecha, 'valor_flete' => $flete,
            'estado' => $estado,
            'sumatoria_gastos_operativos' => 0, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => $flete - $ganancia, 'total_anticipos' => 0, 'saldo_pendiente' => 0,
            'saldo_viaje' => 0, 'ganancia_viaje' => $ganancia, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    private function fixed(Driver $d, User $u, int $sueldo, int $anio = 2026, int $mes = 2): void
    {
        MonthlyExpense::create([
            'driver_id' => $d->id, 'vehicle_plate' => $d->vehicle_plate, 'anio' => $anio, 'mes' => $mes,
            'sueldo_conductor' => $sueldo, 'seguridad_social' => 0, 'cuota_banco' => 0, 'cuota_tercero' => 0,
            'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    private function visit(User $u, array $params = [])
    {
        return $this->actingAs($u)->get(route('liquidaciones.reportes.index', array_merge([
            'tipo' => 'mes', 'anio' => 2026, 'mes' => 2,
        ], $params)));
    }

    /** @test */
    public function utilidad_neta_is_ganancia_minus_gastos_fijos(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->trip($driver, $admin, 5000000, 1500000, Liquidacion::ESTADO_CERRADA);
        $this->fixed($driver, $admin, 2700000);

        $this->visit($admin)->assertOk()->assertViewHas('resumen', function ($r) {
            return (int) $r['sum_ganancia'] === 1500000
                && (int) $r['sum_gastos_mensuales'] === 2700000
                && (int) $r['utilidad_neta'] === -1200000
                && $r['resultado'] === 'perdida';
        });
    }

    /** @test */
    public function anulada_is_excluded_from_totals(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->trip($driver, $admin, 5000000, 1500000, Liquidacion::ESTADO_CERRADA);
        $this->trip($driver, $admin, 9000000, 9000000, Liquidacion::ESTADO_ANULADA);

        $this->visit($admin)->assertOk()->assertViewHas('resumen', function ($r) {
            return (int) $r['sum_ganancia'] === 1500000 && (int) $r['count'] === 1;
        });
    }

    /** @test */
    public function empty_period_returns_ok_with_zeros(): void
    {
        $admin = $this->admin();

        $this->visit($admin, ['mes' => 7])->assertOk()->assertViewHas('resumen', function ($r) {
            return (int) $r['count'] === 0 && (int) $r['utilidad_neta'] === 0 && $r['resultado'] === 'equilibrio';
        });
    }

    /** @test */
    public function fixed_costs_without_trips_show_loss(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        // Gasto fijo del mes pero un viaje con ganancia 0 (para que la tupla driver/mes exista)
        $this->trip($driver, $admin, 0, 0, Liquidacion::ESTADO_CERRADA);
        $this->fixed($driver, $admin, 2700000);

        $this->visit($admin)->assertOk()->assertViewHas('resumen', function ($r) {
            return (int) $r['utilidad_neta'] === -2700000 && $r['resultado'] === 'perdida';
        });
    }
}
