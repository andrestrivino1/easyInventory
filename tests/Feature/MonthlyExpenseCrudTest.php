<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\MonthlyExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyExpenseCrudTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Admin',
            'name' => 'admin@test.com',
            'email' => 'admin@test.com',
            'rol' => 'admin',
            'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(string $name = 'Conductor A', string $plate = 'AAA111'): Driver
    {
        return Driver::create([
            'name' => $name,
            'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate,
            'active' => 1,
        ]);
    }

    private function payload(Driver $driver, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driver->id,
            'anio' => 2026,
            'mes' => 5,
            'sueldo_conductor' => 2000000,
            'seguridad_social' => 300000,
            'cuota_banco' => 150000,
            'cuota_tercero' => 0,
            'satelital' => 50000,
            'seguro_vehiculo' => 120000,
            'otro_valor' => 80000,
            'otro_descripcion' => 'Lavado',
        ], $overrides);
    }

    /** @test */
    public function admin_can_create_a_monthly_expense_with_plate_derived_from_driver(): void
    {
        $admin = $this->admin();
        $driver = $this->driver('Pedro', 'XYZ987');

        $this->actingAs($admin)
            ->post(route('liquidaciones.gastos.store'), $this->payload($driver))
            ->assertRedirect(route('liquidaciones.gastos.index'));

        $this->assertDatabaseHas('monthly_expenses', [
            'driver_id' => $driver->id,
            'vehicle_plate' => 'XYZ987', // derivada server-side
            'anio' => 2026,
            'mes' => 5,
            'sueldo_conductor' => 2000000,
            'otro_descripcion' => 'Lavado',
            'created_by' => $admin->id,
        ]);
    }

    /** @test */
    public function admin_can_update_and_delete_a_monthly_expense(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($driver));
        $gasto = MonthlyExpense::first();

        $this->actingAs($admin)
            ->put(route('liquidaciones.gastos.update', $gasto), $this->payload($driver, ['sueldo_conductor' => 9999999]))
            ->assertRedirect(route('liquidaciones.gastos.index'));
        $this->assertSame(9999999, (int) $gasto->fresh()->sueldo_conductor);

        $this->actingAs($admin)
            ->delete(route('liquidaciones.gastos.destroy', $gasto))
            ->assertRedirect(route('liquidaciones.gastos.index'));
        $this->assertDatabaseMissing('monthly_expenses', ['id' => $gasto->id]);
    }

    /** @test */
    public function cannot_register_two_expenses_for_same_driver_and_period(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($driver));

        $this->actingAs($admin)
            ->post(route('liquidaciones.gastos.store'), $this->payload($driver))
            ->assertSessionHasErrors('driver_id');

        $this->assertSame(1, MonthlyExpense::where('driver_id', $driver->id)->where('anio', 2026)->where('mes', 5)->count());
    }

    /** @test */
    public function same_driver_can_have_expenses_in_different_periods(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($driver, ['mes' => 5]));
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($driver, ['mes' => 6]));

        $this->assertSame(2, MonthlyExpense::where('driver_id', $driver->id)->count());
    }

    /** @test */
    public function index_filters_by_plate(): void
    {
        $admin = $this->admin();
        $a = $this->driver('A', 'AAA111');
        $b = $this->driver('B', 'BBB222');
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($a));
        $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($b));

        $this->actingAs($admin)
            ->get(route('liquidaciones.gastos.index', ['placa' => 'AAA111']))
            ->assertOk()
            ->assertViewHas('gastos', fn ($p) => $p->total() === 1 && $p->first()->vehicle_plate === 'AAA111');
    }

    /** @test */
    public function index_is_paginated_at_25_per_page(): void
    {
        $admin = $this->admin();
        for ($i = 0; $i < 30; $i++) {
            $d = $this->driver('Driver ' . $i, 'PL' . str_pad((string) $i, 4, '0', STR_PAD_LEFT));
            $this->actingAs($admin)->post(route('liquidaciones.gastos.store'), $this->payload($d));
        }

        $this->actingAs($admin)
            ->get(route('liquidaciones.gastos.index'))
            ->assertOk()
            ->assertViewHas('gastos', fn ($p) => $p->total() === 30 && $p->count() === 25);
    }
}
