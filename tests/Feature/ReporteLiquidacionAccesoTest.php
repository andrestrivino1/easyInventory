<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteLiquidacionAccesoTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $rol): User
    {
        return User::create([
            'nombre_completo' => ucfirst($rol), 'name' => "{$rol}@test.com", 'email' => "{$rol}@test.com",
            'rol' => $rol, 'password' => bcrypt('secret123'),
        ]);
    }

    /** @test */
    public function admin_can_access_the_report(): void
    {
        $this->actingAs($this->user('admin'))
            ->get(route('liquidaciones.reportes.index'))
            ->assertOk();
    }

    /** @test */
    public function placas_is_redirected_away_from_reports(): void
    {
        // El rol "placas" queda confinado a su módulo por BlockImporterAccess (grupo web),
        // que corre antes del gate y lo redirige: no llega a ver el informe.
        $this->actingAs($this->user('placas'))
            ->get(route('liquidaciones.reportes.index'))
            ->assertRedirect(route('liquidaciones.index'));
    }

    /** @test */
    public function other_non_admin_roles_get_forbidden(): void
    {
        foreach (['clientes', 'cliente_funcionario', 'funcionario'] as $rol) {
            $this->actingAs($this->user($rol))
                ->get(route('liquidaciones.reportes.index'))
                ->assertForbidden();
        }
    }

    /** @test */
    public function guest_is_redirected_to_login(): void
    {
        $this->get(route('liquidaciones.reportes.index'))->assertRedirect(route('login'));
    }
}
