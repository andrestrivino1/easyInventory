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
            'nombre_completo' => 'Admin', 'name' => 'admin@test.com', 'email' => 'admin@test.com',
            'rol' => 'admin', 'password' => bcrypt('secret123'),
        ]);
    }

    private function driver(string $name = 'Conductor A', string $plate = 'AAA111'): Driver
    {
        return Driver::create([
            'name' => $name, 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => $plate, 'active' => 1,
        ]);
    }

    private function month(array $amounts = [], bool $registrar = true): array
    {
        $row = array_merge([
            'sueldo_conductor' => 0, 'seguridad_social' => 0, 'cuota_banco' => 0, 'cuota_tercero' => 0,
            'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0, 'otro_descripcion' => null,
        ], $amounts);
        if ($registrar) {
            $row['registrar'] = '1';
        }
        return $row;
    }

    private function existing(Driver $driver, User $admin, int $mes, int $sueldo): MonthlyExpense
    {
        return MonthlyExpense::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate,
            'anio' => 2026, 'mes' => $mes, 'sueldo_conductor' => $sueldo,
            'seguridad_social' => 0, 'cuota_banco' => 0, 'cuota_tercero' => 0,
            'satelital' => 0, 'seguro_vehiculo' => 0, 'otro_valor' => 0,
            'created_by' => $admin->id, 'updated_by' => $admin->id,
        ]);
    }

    /** @test */
    public function admin_can_save_a_year_grid_only_for_the_marked_months(): void
    {
        $admin = $this->admin();
        $driver = $this->driver('Pedro', 'XYZ987');

        $this->actingAs($admin)->post(route('liquidaciones.gastos.year.save'), [
            'driver_id' => $driver->id,
            'anio' => 2026,
            'meses' => [
                1 => $this->month(['sueldo_conductor' => 2000000, 'seguridad_social' => 300000]),
                2 => $this->month(['sueldo_conductor' => 2100000]),
                5 => $this->month(['sueldo_conductor' => 999], registrar: false), // sin marcar -> no se guarda
            ],
        ])->assertRedirect();

        $this->assertSame(2, MonthlyExpense::where('driver_id', $driver->id)->where('anio', 2026)->count());
        $this->assertDatabaseHas('monthly_expenses', [
            'driver_id' => $driver->id, 'anio' => 2026, 'mes' => 1,
            'sueldo_conductor' => 2000000, 'seguridad_social' => 300000,
            'vehicle_plate' => 'XYZ987', // placa derivada del conductor (server-side)
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('monthly_expenses', ['anio' => 2026, 'mes' => 2, 'sueldo_conductor' => 2100000]);
        $this->assertDatabaseMissing('monthly_expenses', ['anio' => 2026, 'mes' => 5]);
    }

    /** @test */
    public function saving_updates_marked_months_and_deletes_unmarked_existing_ones(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->existing($driver, $admin, 3, 1000000);  // se editará
        $this->existing($driver, $admin, 7, 2000000);  // se desmarcará -> se elimina

        $this->actingAs($admin)->post(route('liquidaciones.gastos.year.save'), [
            'driver_id' => $driver->id,
            'anio' => 2026,
            'meses' => [
                3 => $this->month(['sueldo_conductor' => 5555555]),       // editar
                7 => $this->month(['sueldo_conductor' => 2000000], registrar: false), // borrar
            ],
        ])->assertRedirect();

        $this->assertSame(5555555, (int) MonthlyExpense::where('driver_id', $driver->id)->where('mes', 3)->value('sueldo_conductor'));
        $this->assertDatabaseMissing('monthly_expenses', ['driver_id' => $driver->id, 'anio' => 2026, 'mes' => 7]);
    }

    /** @test */
    public function years_are_independent_for_the_same_driver(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.gastos.year.save'), [
            'driver_id' => $driver->id, 'anio' => 2025,
            'meses' => [1 => $this->month(['sueldo_conductor' => 100])],
        ]);
        $this->actingAs($admin)->post(route('liquidaciones.gastos.year.save'), [
            'driver_id' => $driver->id, 'anio' => 2026,
            'meses' => [1 => $this->month(['sueldo_conductor' => 200])],
        ]);

        $this->assertSame(2, MonthlyExpense::where('driver_id', $driver->id)->count());
    }

    /** @test */
    public function year_grid_view_loads_with_existing_data(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->existing($driver, $admin, 4, 1234567);

        $this->actingAs($admin)
            ->get(route('liquidaciones.gastos.year', ['driver_id' => $driver->id, 'anio' => 2026]))
            ->assertOk()
            ->assertViewHas('existing', fn ($e) => $e->has(4));
    }

    /** @test */
    public function index_lists_registered_year_groups(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();
        $this->existing($driver, $admin, 1, 100);
        $this->existing($driver, $admin, 2, 200);

        $this->actingAs($admin)->get(route('liquidaciones.gastos.index'))
            ->assertOk()
            ->assertViewHas('groups', fn ($g) => $g->count() === 1 && (int) $g->first()->meses === 2 && (int) $g->first()->total_anio === 300);
    }
}
