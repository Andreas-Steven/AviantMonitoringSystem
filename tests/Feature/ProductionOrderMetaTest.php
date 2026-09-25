<?php

namespace Tests\Feature;

use App\Domains\Production\DTOs\ProductionOrderFiltersData;
use App\Domains\Production\Services\ProductionDashboardService;
use App\Domains\Production\Services\ProductionOrderService;
use App\Http\Middleware\EnsureProductionDashboardAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class ProductionOrderMetaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProductionDashboardAccess::class);

        $dashboardService = Mockery::mock(ProductionDashboardService::class);
        $dashboardService->shouldReceive('missingTables')->andReturn([]);
        $this->app->instance(ProductionDashboardService::class, $dashboardService);
    }

    public function test_production_order_filter_meta_defaults_to_an_empty_array(): void
    {
        $orderService = Mockery::mock(ProductionOrderService::class);
        $orderService->shouldReceive('paginate')
            ->once()
            ->with(Mockery::on(fn ($filters): bool => $filters instanceof ProductionOrderFiltersData
                && $filters->status === null
                && $filters->search === null
                && $filters->product === null
                && $filters->machine === null))
            ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        $this->app->instance(ProductionOrderService::class, $orderService);

        $response = $this->getJson('/api/production-orders')
            ->assertOk()
            ->assertJsonPath('meta.sort.by', 'plan_start')
            ->assertJsonPath('meta.pagination.total', 0);

        $payload = json_decode($response->getContent());
        $this->assertIsArray($payload->meta->filter);
    }

    public function test_production_order_filter_meta_remains_an_object_when_filters_are_present(): void
    {
        $orderService = Mockery::mock(ProductionOrderService::class);
        $orderService->shouldReceive('paginate')
            ->once()
            ->with(Mockery::on(fn ($filters): bool => $filters instanceof ProductionOrderFiltersData
                && $filters->status?->value === 'RUNNING'))
            ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        $this->app->instance(ProductionOrderService::class, $orderService);

        $response = $this->getJson('/api/production-orders?status=running')->assertOk();
        $payload = json_decode($response->getContent());

        $this->assertIsObject($payload->meta->filter);
        $this->assertSame('RUNNING', $payload->meta->filter->status);
    }
}
