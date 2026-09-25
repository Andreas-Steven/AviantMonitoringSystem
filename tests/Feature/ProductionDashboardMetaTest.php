<?php

namespace Tests\Feature;

use App\Domains\Production\DTOs\MachinePerformanceData;
use App\Domains\Production\DTOs\ProductionDashboardData;
use App\Domains\Production\Services\ProductionDashboardService;
use App\Http\Middleware\EnsureProductionDashboardAccess;
use Mockery;
use Tests\TestCase;

class ProductionDashboardMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProductionDashboardAccess::class);
    }

    public function test_dashboard_response_omits_meta(): void
    {
        $service = Mockery::mock(ProductionDashboardService::class);
        $service->shouldReceive('missingTables')->once()->andReturn([]);
        $service->shouldReceive('dashboard')->once()->andReturn(new ProductionDashboardData(
            summary: [],
            trend7Days: [],
            statusBreakdown: [],
            topMachines: [],
        ));
        $this->app->instance(ProductionDashboardService::class, $service);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonMissingPath('meta');
    }

    public function test_machine_meta_contains_the_requested_machine_code_filter(): void
    {
        $service = Mockery::mock(ProductionDashboardService::class);
        $service->shouldReceive('missingTables')->once()->andReturn([]);
        $service->shouldReceive('machine')
            ->once()
            ->with('WMX01')
            ->andReturn(new MachinePerformanceData(
                machineCode: 'WMX01',
                machineName: 'Waterproof Mixer 01',
                totalOrder: 0,
                goodQty: 0,
                rejectQty: 0,
                downtimeMinutes: 0,
                achievement: 0.0,
            ));
        $this->app->instance(ProductionDashboardService::class, $service);

        $response = $this->getJson('/api/dashboard/machine/WMX01')
            ->assertOk()
            ->assertJsonPath('meta.filter.machine_code', 'WMX01')
            ->assertJsonPath('meta.sort.by', 'id')
            ->assertJsonPath('meta.sort.dir', 'desc')
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.pagination.display', 1)
            ->assertJsonPath('meta.pagination.page', 1)
            ->assertJsonPath('meta.pagination.page_size', 10);
        $payload = json_decode($response->getContent());

        $this->assertIsObject($payload->meta->filter);
    }
}
