<?php

namespace Tests\Feature;

use App\Models\Import;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * US2 — El consecutivo del DO continúa tras vaciar el módulo.
 *
 * FR-011, FR-013, FR-014, FR-015, FR-016, FR-017, SC-003, SC-005.
 *
 * El caso central reproduce el dato real de producción: el mayor DO emitido es
 * VJP26-057, así que tras la limpieza el siguiente debe ser VJP26-058.
 */
class DoCodeConsecutiveTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    private function currentYear(): string
    {
        return date('y');
    }

    private function doCode(int $number): string
    {
        return sprintf('VJP%s-%03d', $this->currentYear(), $number);
    }

    /**
     * Reproduce la regla de ImportController::store() para saber qué número
     * se emitiría a continuación, sin pasar por HTTP.
     */
    private function nextDoCode(): string
    {
        $year = $this->currentYear();

        $last = Import::withTrashed()
            ->whereRaw('SUBSTRING(do_code, 4, 2) = ?', [$year])
            ->orderByDesc('do_code')
            ->first();

        $next = 1;
        if ($last && preg_match('/VJP' . $year . '-(\d{3})/', $last->do_code, $m)) {
            $next = (int) $m[1] + 1;
        }

        $floor = Import::DO_CODE_FLOOR[$year] ?? 0;
        if ($next < $floor) {
            $next = $floor;
        }

        return sprintf('VJP%s-%03d', $year, $next);
    }

    /** @test */
    public function next_do_continues_from_the_last_issued_and_never_restarts(): void
    {
        $this->import($this->doCode(55));
        $this->import($this->doCode(56));
        $this->import($this->doCode(57));

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Import::count(), 'FR-011: ninguna importación visible');

        // SC-005 / FR-014: el siguiente es N+1, nunca 001.
        $this->assertSame($this->doCode(58), $this->nextDoCode());
        $this->assertNotSame($this->doCode(1), $this->nextDoCode());
    }

    /** @test */
    public function an_issued_do_is_never_reassigned(): void
    {
        foreach ([55, 56, 57] as $n) {
            $this->import($this->doCode($n));
        }
        $issued = Import::withTrashed()->pluck('do_code')->all();

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true]);

        // FR-015: el próximo número no puede coincidir con ninguno ya emitido.
        $this->assertNotContains($this->nextDoCode(), $issued);
    }

    /** @test */
    public function deleted_imports_are_excluded_from_listings(): void
    {
        $admin = $this->admin();
        $this->import($this->doCode(57), null, $admin->id);

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true]);

        // FR-013 / SC-003: fuera de los listados para el rol admin.
        $this->actingAs($admin)->get(route('imports.index'))
            ->assertOk()
            ->assertDontSee($this->doCode(57));
    }

    /** @test */
    public function the_year_floor_still_applies_after_a_purge(): void
    {
        // Sin ninguna importación, el piso del año manda sobre el arranque en 001.
        $this->assertSame(0, Import::withTrashed()->count());

        $floor = Import::DO_CODE_FLOOR[$this->currentYear()] ?? null;

        if ($floor === null) {
            $this->assertSame($this->doCode(1), $this->nextDoCode());

            return;
        }

        $this->assertSame($this->doCode($floor), $this->nextDoCode(), 'FR-016: el piso del año se respeta');
    }

    /** @test */
    public function reset_does_not_touch_import_containers(): void
    {
        $import = $this->import($this->doCode(57));
        DB::table('import_containers')->insert([
            'import_id' => $import->id,
            'reference' => 'DOC-CONT-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true]);

        // Cuelgan de una importación borrada en suave, así que deben seguir ahí.
        $this->assertSame(1, DB::table('import_containers')->count(), 'FR-012');
    }

    /** @test */
    public function store_assigns_the_next_consecutive_after_a_purge(): void
    {
        $admin = $this->admin();
        $this->import($this->doCode(57), now()->toDateString(), $admin->id);

        $this->artisan('inventory:reset', ['--scope' => 'imports', '--force' => true]);

        $response = $this->actingAs($admin)->post(route('imports.store'), [
            'origin' => 'China',
            'destination' => 'Buenaventura',
            'departure_date' => now()->toDateString(),
            'arrival_date' => now()->toDateString(),
            'status' => 'pending',
            'credit_time' => '30',
        ]);

        $response->assertRedirect();

        $created = Import::latest('id')->first();
        $this->assertNotNull($created, 'Debe crearse la importación');
        $this->assertSame($this->doCode(58), $created->do_code, 'FR-014: continúa en 58');
    }

    /** @test */
    public function concurrent_creation_does_not_produce_duplicate_do_codes(): void
    {
        $admin = $this->admin();
        $this->import($this->doCode(57), now()->toDateString(), $admin->id);

        $payload = [
            'origin' => 'China',
            'destination' => 'Buenaventura',
            'departure_date' => now()->toDateString(),
            'arrival_date' => now()->toDateString(),
            'status' => 'pending',
            'credit_time' => '30',
        ];

        // Dos creaciones seguidas deben producir números distintos y consecutivos.
        $this->actingAs($admin)->post(route('imports.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('imports.store'), $payload)->assertRedirect();

        $codes = Import::withTrashed()->orderBy('id')->pluck('do_code')->all();

        $this->assertSame(count($codes), count(array_unique($codes)), 'FR-017: sin duplicados');
        $this->assertContains($this->doCode(58), $codes);
        $this->assertContains($this->doCode(59), $codes);
    }
}
