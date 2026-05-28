<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Liquidacion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquidacionPdfAjustesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'nombre_completo' => 'Admin', 'name' => 'admin@test.com', 'email' => 'admin@test.com',
            'rol' => 'admin', 'password' => bcrypt('secret123'),
        ]);
    }

    private function liq(User $u): Liquidacion
    {
        $driver = Driver::create([
            'name' => 'Conductor A', 'identity' => (string) random_int(10000000, 99999999),
            'vehicle_plate' => 'P' . random_int(10000, 99999), 'active' => 1,
        ]);

        return Liquidacion::create([
            'driver_id' => $driver->id, 'vehicle_plate' => $driver->vehicle_plate, 'transportadora' => 'X',
            'anticipo_empresa' => 4690000, 'anticipo_conductor' => 3000000, 'sobreanticipo' => 500000, 'descuentos' => 100000,
            'fecha_inicio' => '2026-03-06', 'fecha_fin' => '2026-03-09', 'valor_flete' => 6700000,
            'estado' => Liquidacion::ESTADO_BORRADOR,
            'sumatoria_gastos_operativos' => 3159000, 'sumatoria_peajes' => 981000, 'sumatoria_peajes_conductor' => 0,
            'sumatoria_gastos_totales' => 4240000, 'total_anticipos' => 8190000, 'saldo_pendiente' => 2010000,
            'saldo_viaje' => -241000, 'ganancia_viaje' => 2460000, 'a_favor_de' => Liquidacion::AFAVOR_EMPRESA,
            'created_by' => $u->id, 'updated_by' => $u->id,
        ]);
    }

    /** @test */
    public function el_pdf_responde_ok_para_admin(): void
    {
        $admin = $this->admin();
        $liq = $this->liq($admin);

        $this->actingAs($admin)->get(route('liquidaciones.pdf', $liq))->assertOk();
    }

    /** @test */
    public function el_encabezado_del_pdf_no_muestra_anticipo_empresa_pero_si_la_firma_y_totales(): void
    {
        $admin = $this->admin();
        $liq = $this->liq($admin)->load('driver', 'route', 'tolls', 'expenses');

        $html = view('liquidaciones.pdf', ['liq' => $liq])->render();

        // El encabezado superior ya NO lleva la celda "ANTICIPO EMPRESA"
        $this->assertStringNotContainsString('ANTICIPO EMPRESA</td>', $html);
        // pero el recuadro de totales SÍ muestra la celda de empresa y el saldo adeudado
        $this->assertStringContainsString('ANTICIPO EMPRESA DE TRANSPORTE', $html);
        $this->assertStringContainsString('SALDO ADEUDADO EMPRESA DE TRANSPORTE', $html);
        // firma actualizada
        $this->assertStringContainsString('FIRMA FUNCIONARIO REVISÓ', $html);
        $this->assertStringNotContainsString('FIRMA CONDUCTOR', $html);
    }
}
