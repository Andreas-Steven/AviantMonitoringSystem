<?php

namespace Tests\Feature;

use App\Domains\Production\Services\ProductionDashboardService;
use Mockery;
use Tests\TestCase;

class ProductionApiCsrfTest extends TestCase
{
    public function test_local_dashboard_bypass_can_post_without_csrf_token(): void
    {
        $this->app['env'] = 'local';
        config([
            'app.env' => 'local',
            'app.local_dashboard_bypass' => true,
        ]);

        $service = Mockery::mock(ProductionDashboardService::class);
        $service->shouldReceive('missingTables')->once()->andReturn(['work_order']);
        $this->app->instance(ProductionDashboardService::class, $service);

        $this->postJson('/api/production-results')->assertStatus(503);
    }

    public function test_local_api_still_requires_csrf_when_dashboard_bypass_is_disabled(): void
    {
        $this->app['env'] = 'local';
        config([
            'app.env' => 'local',
            'app.local_dashboard_bypass' => false,
        ]);

        $this->postJson('/api/production-results')->assertStatus(419);
    }

    public function test_production_api_still_requires_csrf_when_local_bypass_is_enabled(): void
    {
        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'app.local_dashboard_bypass' => true,
        ]);

        $this->postJson('/api/production-results')->assertStatus(419);
    }
}
