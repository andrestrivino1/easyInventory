<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsInventoryFixtures;
use Tests\TestCase;

/**
 * US1 — Las exportaciones deben generarse sobre módulos vacíos.
 *
 * FR-008, SC-009. Un PDF o un Excel que revienta con cero filas convierte un
 * arranque en limpio en un fallo visible para el usuario.
 */
class EmptyModuleExportsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsInventoryFixtures;

    /** @return array<string, array{0:string}> */
    public function exportRoutes(): array
    {
        return [
            'stock pdf' => ['stock.export-pdf'],
            'stock excel' => ['stock.export-excel'],
            'stock excel productos' => ['stock.export-excel-products'],
            'stock excel contenedores' => ['stock.export-excel-containers'],
            'stock excel transferencias' => ['stock.export-excel-transfers'],
            'stock excel salidas' => ['stock.export-excel-salidas'],
            'trazabilidad pdf' => ['traceability.export-pdf'],
            'trazabilidad excel' => ['traceability.export-excel'],
        ];
    }

    /**
     * @test
     * @dataProvider exportRoutes
     */
    public function export_succeeds_on_an_empty_module(string $routeName): void
    {
        $data = $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true]);

        $response = $this->actingAs($data['admin'])->get(route($routeName));

        $response->assertOk();
    }

    /** @test */
    public function index_screens_render_on_an_empty_module(): void
    {
        $data = $this->seedFullInventory();

        $this->artisan('inventory:reset', ['--scope' => 'all', '--force' => true]);

        $this->actingAs($data['admin'])->get(route('stock.index'))->assertOk();
        $this->actingAs($data['admin'])->get(route('traceability.index'))->assertOk();
        $this->actingAs($data['admin'])->get(route('imports.index'))->assertOk();
    }
}
