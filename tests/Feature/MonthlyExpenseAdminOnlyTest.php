<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MonthlyExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyExpenseAdminOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol): User
    {
        return User::create([
            'nombre_completo' => ucfirst($rol),
            'name' => $rol . '@test.com',
            'email' => $rol . '@test.com',
            'rol' => $rol,
            'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(): Driver
    {
        return Driver::create([
            'name' => 'Conductor A',
            'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => 'AAA111',
            'active' => 1,
        ]);
    }

    private function gasto(User $creator, Driver $driver): MonthlyExpense
    {
        return MonthlyExpense::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate,
            'anio' => 2026, 'mes' => 5,
            'sueldo_conductor' => 1000000, 'seguridad_social' => 0, 'cuota_banco' => 0,
            'cuota_tercero' => 0, 'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0,
            'created_by' => $creator->id, 'updated_by' => $creator->id,
        ]);
    }

    /**
     * El rol "placas" queda fuera de Gastos mensuales: BlockImporterAccess lo redirige
     * a liquidaciones.index (convención del módulo), además del gate admin-only.
     *
     * @test
     */
    public function placas_user_is_blocked_from_all_monthly_expense_routes(): void
    {
        $placas = $this->user('placas');
        $admin = $this->user('admin');
        $driver = $this->driver();
        $gasto = $this->gasto($admin, $driver);

        $this->actingAs($placas)->get(route('liquidaciones.gastos.index'))->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($placas)->get(route('liquidaciones.gastos.year', ['driver_id' => $driver->id, 'anio' => 2026]))->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($placas)->post(route('liquidaciones.gastos.year.save'), ['driver_id' => $driver->id, 'anio' => 2026])->assertRedirect(route('liquidaciones.index'));
        $this->actingAs($placas)->delete(route('liquidaciones.gastos.destroy', $gasto))->assertRedirect(route('liquidaciones.index'));
    }

    /** @test */
    public function admin_can_reach_monthly_expense_screens(): void
    {
        $admin = $this->user('admin');
        $driver = $this->driver();
        $this->actingAs($admin)->get(route('liquidaciones.gastos.index'))->assertOk();
        $this->actingAs($admin)->get(route('liquidaciones.gastos.year', ['driver_id' => $driver->id, 'anio' => 2026]))->assertOk();
    }
}
