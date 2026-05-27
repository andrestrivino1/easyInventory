<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiquidacionManifiestoPdfTest extends TestCase
{
    use RefreshDatabase;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disk = config('filesystems.default');
        Storage::fake($this->disk);
    }

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
            'vehicle_plate' => 'AAA111', 'active' => 1,
        ]);
    }

    private function payload(Driver $driver, array $overrides = []): array
    {
        return array_merge([
            'driver_id' => $driver->id,
            'vehicle_plate' => $driver->vehicle_plate,
            'transportadora' => 'Transporte X',
            'anticipo_empresa' => 0,
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-05-02',
            'valor_flete' => 0,
        ], $overrides);
    }

    /** @test */
    public function uploading_a_pdf_associates_it_with_the_liquidacion(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'manifiesto_pdf' => UploadedFile::fake()->create('manifiesto.pdf', 200, 'application/pdf'),
        ]))->assertRedirect();

        $liq = Liquidacion::first();
        $this->assertNotNull($liq->manifiesto_pdf_path);
        Storage::disk($this->disk)->assertExists($liq->manifiesto_pdf_path);

        // Ver/descargar
        $this->actingAs($admin)->get(route('liquidaciones.manifiesto', $liq))->assertOk();
    }

    /** @test */
    public function non_pdf_files_are_rejected(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'manifiesto_pdf' => UploadedFile::fake()->create('archivo.txt', 10, 'text/plain'),
        ]))->assertSessionHasErrors('manifiesto_pdf');

        $this->assertDatabaseCount('liquidaciones', 0);
    }

    /** @test */
    public function uploading_a_new_manifiesto_replaces_the_previous_file(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'manifiesto_pdf' => UploadedFile::fake()->create('m1.pdf', 100, 'application/pdf'),
        ]));
        $liq = Liquidacion::first();
        $oldPath = $liq->manifiesto_pdf_path;

        $this->actingAs($admin)->put(route('liquidaciones.update', $liq), $this->payload($driver, [
            'manifiesto_pdf' => UploadedFile::fake()->create('m2.pdf', 100, 'application/pdf'),
        ]))->assertRedirect();

        $liq->refresh();
        $this->assertNotSame($oldPath, $liq->manifiesto_pdf_path);
        Storage::disk($this->disk)->assertMissing($oldPath);
        Storage::disk($this->disk)->assertExists($liq->manifiesto_pdf_path);
    }

    /** @test */
    public function deleting_the_manifiesto_clears_the_path_and_removes_the_file(): void
    {
        $admin = $this->admin();
        $driver = $this->driver();

        $this->actingAs($admin)->post(route('liquidaciones.store'), $this->payload($driver, [
            'manifiesto_pdf' => UploadedFile::fake()->create('m.pdf', 100, 'application/pdf'),
        ]));
        $liq = Liquidacion::first();
        $path = $liq->manifiesto_pdf_path;

        $this->actingAs($admin)->delete(route('liquidaciones.manifiesto.destroy', $liq))->assertRedirect();

        $this->assertNull($liq->fresh()->manifiesto_pdf_path);
        Storage::disk($this->disk)->assertMissing($path);
    }
}
