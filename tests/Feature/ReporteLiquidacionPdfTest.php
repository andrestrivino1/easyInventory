<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteLiquidacionPdfTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol): User
    {
        return User::create([
            'nombre_completo' => ucfirst($rol), 'name' => "{$rol}@test.com", 'email' => "{$rol}@test.com",
            'rol' => $rol, 'password' => bcrypt('secret123'),
        ]);
    }

    private function seedTrip(User $u): void
    {
        $driver = Driver::create([
            'name' => 'Conductor A', 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => 'AAA111', 'active' => 1,
        ]);
        Liquidacion::create([
            'driver_id' => $driver->id, 'vehicle_plate' => 'AAA111', 'transportadora' => 'X',
            'anticipo_empresa' => 0, 'anticipo_conductor' => 0, 'descuentos' => 0,
            'fecha_inicio' => '2026-02-10', 'fecha_fin' => '2026-02-10', 'valor_flete' => 5000000,
            'estado' => Liquidacion::ESTADO_CERRADA,
            'sumatoria_gastos_operativos' => 0, 'sumatoria_peajes' => 0, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => 3500000, 'total_anticipos' => 0, 'saldo_pendiente' => 0,
            'saldo_viaje' => 0, 'ganancia_viaje' => 1500000, 'a_favor_de' => Liquidacion::AFAVOR_NINGUNO,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    /** @test */
    public function admin_downloads_pdf_without_chart_images(): void
    {
        $admin = $this->user('admin');
        $this->seedTrip($admin);

        $resp = $this->actingAs($admin)->post(route('liquidaciones.reportes.pdf'), [
            'tipo' => 'mes', 'anio' => 2026, 'mes' => 2,
        ]);

        $resp->assertOk();
        $this->assertSame('application/pdf', $resp->headers->get('content-type'));
    }

    /** @test */
    public function non_admin_cannot_download_pdf(): void
    {
        // "placas" lo confina BlockImporterAccess (redirección a su módulo);
        // un rol que sí pasa ese middleware pero no el gate (funcionario) recibe 403.
        // En ambos casos no obtiene el PDF.
        $this->actingAs($this->user('placas'))
            ->post(route('liquidaciones.reportes.pdf'), ['tipo' => 'mes', 'anio' => 2026, 'mes' => 2])
            ->assertRedirect(route('liquidaciones.index'));

        $this->actingAs($this->user('funcionario'))
            ->post(route('liquidaciones.reportes.pdf'), ['tipo' => 'mes', 'anio' => 2026, 'mes' => 2])
            ->assertForbidden();
    }
}
