<?php

namespace Tests\Feature;

use App\Domains\Production\Actions\StoreProductionResultAction;
use App\Domains\Production\DTOs\StoredProductionResultData;
use App\Domains\Production\DTOs\StoreProductionResultData;
use App\Domains\Production\Models\ProductionResult;
use App\Domains\Production\Models\WorkOrder;
use App\Domains\Production\Repositories\ProductionOrderRepository;
use App\Domains\Production\Repositories\ProductionResultRepository;
use App\Domains\Production\Services\ProductionDashboardService;
use App\Http\Middleware\EnsureProductionDashboardAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\PresenceVerifierInterface;
use Mockery;
use Tests\TestCase;

class ProductionResultDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(EnsureProductionDashboardAccess::class);

        $dashboardService = Mockery::mock(ProductionDashboardService::class);
        $dashboardService->shouldReceive('missingTables')->andReturn([]);
        $this->app->instance(ProductionDashboardService::class, $dashboardService);

        $presenceVerifier = Mockery::mock(PresenceVerifierInterface::class);
        $presenceVerifier->shouldReceive('getCount')->andReturn(1);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);
    }

    public function test_action_persists_datetime_fields_without_losing_time(): void
    {
        $productionDate = today()->setTime(8, 0)->toDateTimeString();
        $productionFinish = today()->addDays(5)->setTime(15, 0)->toDateTimeString();
        $workOrder = new WorkOrder([
            'wo_number' => 'WO2026000898',
            'status' => 'RUNNING',
            'target_qty' => 1000,
        ]);
        $orderRepository = Mockery::mock(ProductionOrderRepository::class);
        $orderRepository->shouldReceive('findForUpdate')
            ->once()
            ->with('WO2026000898')
            ->andReturn($workOrder);

        $resultRepository = Mockery::mock(ProductionResultRepository::class);
        $resultRepository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($attributes): bool => $attributes['wo_number'] === 'WO2026000898'
                && $attributes['actual_start'] === $productionDate
                && $attributes['actual_finish'] === $productionFinish
                && $attributes['runtime_minutes'] === 420
                && $attributes['good_qty'] === 1500
                && $attributes['reject_qty'] === 25
                && $attributes['achievement'] === 150.0))
            ->andReturn(new ProductionResult(['id' => 1]));

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($callback) => $callback());

        $result = (new StoreProductionResultAction($orderRepository, $resultRepository))->execute(
            new StoreProductionResultData(
                workOrderNumber: 'WO2026000898',
                productionDate: $productionDate,
                quantityGood: 1500,
                quantityReject: 25,
                runtimeMinutes: 420,
                productionFinish: $productionFinish,
            ),
        );

        $this->assertSame($productionDate, $result->productionDate);
        $this->assertSame($productionFinish, $result->productionFinish);
    }

    public function test_production_result_api_accepts_datetime_start_and_finish_values(): void
    {
        $productionDate = today()->setTime(8, 0)->toDateTimeString();
        $productionFinish = today()->addDays(5)->setTime(15, 0)->toDateTimeString();

        $action = Mockery::mock(StoreProductionResultAction::class);
        $action->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn ($data): bool => $data instanceof StoreProductionResultData
                && $data->workOrderNumber === 'WO2026000898'
                && $data->productionDate === $productionDate
                && $data->productionFinish === $productionFinish
                && $data->quantityGood === 1500
                && $data->quantityReject === 25
                && $data->runtimeMinutes === 420))
            ->andReturn(new StoredProductionResultData(
                id: 1,
                workOrderNumber: 'WO2026000898',
                productionDate: $productionDate,
                productionFinish: $productionFinish,
                quantityGood: 1500,
                quantityReject: 25,
                runtimeMinutes: 420,
                achievement: 100.0,
            ));
        $this->app->instance(StoreProductionResultAction::class, $action);

        $this->postJson('/api/production-results', [
            'wo_number' => 'WO2026000898',
            'production_date' => $productionDate,
            'production_finish' => $productionFinish,
            'qty_good' => 1500,
            'qty_reject' => 25,
            'runtime_minutes' => 420,
        ])->assertCreated()
            ->assertJsonPath('data.production_date', $productionDate)
            ->assertJsonPath('data.production_finish', $productionFinish);
    }

    public function test_production_result_api_rejects_date_only_values(): void
    {
        $date = today()->toDateString();
        $finish = today()->addDay()->toDateString();

        $this->postJson('/api/production-results', [
            'wo_number' => 'WO2026000898',
            'production_date' => $date,
            'production_finish' => $finish,
            'qty_good' => 1500,
            'qty_reject' => 25,
            'runtime_minutes' => 420,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonFragment(['field' => 'production_date'])
            ->assertJsonFragment(['field' => 'production_finish'])
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('meta');
    }

    public function test_production_result_api_rejects_a_finish_datetime_before_the_production_datetime(): void
    {
        $productionDate = today()->setTime(8, 0)->toDateTimeString();
        $productionFinish = today()->setTime(7, 0)->toDateTimeString();

        $this->postJson('/api/production-results', [
            'wo_number' => 'WO2026000898',
            'production_date' => $productionDate,
            'production_finish' => $productionFinish,
            'qty_good' => 1500,
            'qty_reject' => 25,
            'runtime_minutes' => 420,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonPath('errors.0.field', 'production_finish')
            ->assertJsonPath('errors.0.message', 'The production finish field must be a date after or equal to production date.')
            ->assertJsonStructure(['errors' => [['field', 'message']]])
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('meta');
    }

    public function test_production_result_api_still_rejects_a_future_production_date(): void
    {
        $this->postJson('/api/production-results', [
            'wo_number' => 'WO2026000898',
            'production_date' => today()->addDay()->setTime(8, 0)->toDateTimeString(),
            'production_finish' => today()->addDays(2)->setTime(15, 0)->toDateTimeString(),
            'qty_good' => 1500,
            'qty_reject' => 25,
            'runtime_minutes' => 420,
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonPath('errors.0.field', 'production_date');
    }
}
