<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureProductionDashboardAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO SQLite is required for isolated production API feature tests.');
        }

        $this->withoutMiddleware(EnsureProductionDashboardAccess::class);
        $this->createProductionSchema();
        $this->seedProductionData();
    }

    public function test_dashboard_api_returns_kpis_complete_trend_and_top_machines(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_machine', 2)
            ->assertJsonPath('data.summary.running_order', 1)
            ->assertJsonPath('data.summary.finished_order', 1)
            ->assertJsonPath('data.summary.today_target', 1000)
            ->assertJsonPath('data.summary.today_good', 900)
            ->assertJsonPath('data.summary.today_reject', 30)
            ->assertJsonPath('data.summary.achievement', 90)
            ->assertJsonCount(7, 'data.trend_7_days')
            ->assertJsonPath('data.trend_7_days.5.date', '2026-07-18')
            ->assertJsonPath('data.trend_7_days.5.good_qty', 0)
            ->assertJsonPath('data.top_machines.0.machine_code', 'M1')
            ->assertJsonPath('data.top_machines.0.good_qty', 900);
    }

    public function test_machine_api_returns_performance_in_the_standard_envelope(): void
    {
        $response = $this->getJson('/api/dashboard/machine/M1');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.machine_code', 'M1')
            ->assertJsonPath('data.machine_name', 'Mixer A01')
            ->assertJsonPath('data.good_qty', 900)
            ->assertJsonPath('data.downtime_minutes', 30);
    }

    public function test_production_orders_api_filters_and_sorts_orders(): void
    {
        $response = $this->getJson('/api/production-orders?status=running&product=P1&sort=target_qty&direction=desc');

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.filter.status', 'RUNNING')
            ->assertJsonPath('meta.sort.by', 'target_qty')
            ->assertJsonPath('meta.sort.dir', 'desc')
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.pagination.display', 1)
            ->assertJsonPath('meta.pagination.page', 1)
            ->assertJsonPath('meta.pagination.page_size', 10)
            ->assertJsonPath('data.0.wo_number', 'WO1')
            ->assertJsonPath('data.0.product_name', 'Primer');
    }

    public function test_production_order_api_rejects_unknown_enum_values(): void
    {
        $this->getJson('/api/production-orders?status=PAUSED')
            ->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonPath('errors.0.field', 'status')
            ->assertJsonStructure(['errors' => [['field', 'message']]]);

        $this->getJson('/api/production-orders?sort=created_at')
            ->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('errors.0.field', 'sort');

        $this->getJson('/api/production-orders?direction=random')
            ->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('errors.0.field', 'direction');
    }

    public function test_production_result_api_stores_a_valid_result_for_a_running_order(): void
    {
        $productionDate = today()->setTime(8, 0)->toDateTimeString();
        $productionFinish = today()->addDays(5)->setTime(15, 0)->toDateTimeString();

        $response = $this->postJson('/api/production-results', [
            'wo_number' => 'WO1',
            'production_date' => $productionDate,
            'production_finish' => $productionFinish,
            'qty_good' => 950,
            'qty_reject' => 15,
            'runtime_minutes' => 360,
        ]);

        $response->assertCreated()
            ->assertJsonPath('code', 201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wo_number', 'WO1')
            ->assertJsonPath('data.production_date', $productionDate)
            ->assertJsonPath('data.production_finish', $productionFinish)
            ->assertJsonPath('data.qty_good', 950);

        $this->assertDatabaseHas('production_result', [
            'wo_number' => 'WO1',
            'actual_start' => $productionDate,
            'actual_finish' => $productionFinish,
            'good_qty' => 950,
            'reject_qty' => 15,
        ]);
    }

    public function test_production_result_api_rejects_future_dates_negative_quantities_and_non_running_orders(): void
    {
        $this->postJson('/api/production-results', [
            'wo_number' => 'WO1',
            'production_date' => today()->addDay()->setTime(8, 0)->toDateTimeString(),
            'qty_good' => -1,
            'qty_reject' => 0,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonPath('errors.0.field', 'production_date')
            ->assertJsonPath('errors.0.message', 'The production date field must be a date before or equal to today.')
            ->assertJsonPath('errors.1.field', 'qty_good')
            ->assertJsonPath('errors.1.message', 'The good quantity must be at least 0.')
            ->assertJsonStructure(['errors' => [['field', 'message']]])
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('meta');

        $this->postJson('/api/production-results', [
            'wo_number' => 'WO2',
            'production_date' => today()->setTime(8, 0)->toDateTimeString(),
            'qty_good' => 1,
            'qty_reject' => 0,
        ])->assertUnprocessable()
            ->assertJsonPath('code', 422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.0.field', 'wo_number')
            ->assertJsonPath('errors.0.message', 'The production order must have a status of RUNNING.');
    }

    public function test_production_result_api_uses_translated_required_validation_messages(): void
    {
        $this->postJson('/api/production-results', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed. Please review the provided data')
            ->assertJsonPath('errors.0.field', 'wo_number')
            ->assertJsonPath('errors.0.message', 'The production order field is required.');
    }

    private function createProductionSchema(): void
    {
        Schema::create('employee', function (Blueprint $table): void {
            $table->string('employee_no')->primary();
            $table->string('full_name');
        });

        Schema::create('machine', function (Blueprint $table): void {
            $table->string('machine_code')->primary();
            $table->string('machine_name');
            $table->string('production_line');
        });

        Schema::create('product', function (Blueprint $table): void {
            $table->string('product_code')->primary();
            $table->string('product_name');
            $table->string('category');
            $table->integer('target_min');
            $table->integer('target_max');
        });

        Schema::create('work_order', function (Blueprint $table): void {
            $table->string('wo_number')->primary();
            $table->string('product_code');
            $table->string('machine_code');
            $table->string('employee_no');
            $table->string('shift');
            $table->integer('target_qty');
            $table->dateTime('plan_start');
            $table->dateTime('plan_finish');
            $table->string('status');
        });

        Schema::create('production_result', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('wo_number');
            $table->dateTime('actual_start');
            $table->dateTime('actual_finish');
            $table->integer('runtime_minutes');
            $table->integer('good_qty');
            $table->integer('reject_qty');
            $table->decimal('achievement', 8, 2);
        });

        Schema::create('downtime', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('wo_number');
            $table->string('downtime_reason');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_minutes');
        });
    }

    private function seedProductionData(): void
    {
        DB::table('employee')->insert([
            ['employee_no' => 'E1', 'full_name' => 'Adi Putra'],
            ['employee_no' => 'E2', 'full_name' => 'Budi Santoso'],
        ]);

        DB::table('machine')->insert([
            ['machine_code' => 'M1', 'machine_name' => 'Mixer A01', 'production_line' => 'Liquid Paint'],
            ['machine_code' => 'M2', 'machine_name' => 'Filling Line 01', 'production_line' => 'Packaging'],
        ]);

        DB::table('product')->insert([
            ['product_code' => 'P1', 'product_name' => 'Primer', 'category' => 'Paint', 'target_min' => 100, 'target_max' => 1000],
            ['product_code' => 'P2', 'product_name' => 'Enamel', 'category' => 'Paint', 'target_min' => 100, 'target_max' => 1000],
        ]);

        DB::table('work_order')->insert([
            [
                'wo_number' => 'WO1',
                'product_code' => 'P1',
                'machine_code' => 'M1',
                'employee_no' => 'E1',
                'shift' => 'Shift 1',
                'target_qty' => 1000,
                'plan_start' => '2026-07-19 07:00:00',
                'plan_finish' => '2026-07-19 15:00:00',
                'status' => 'RUNNING',
            ],
            [
                'wo_number' => 'WO2',
                'product_code' => 'P2',
                'machine_code' => 'M2',
                'employee_no' => 'E2',
                'shift' => 'Shift 1',
                'target_qty' => 500,
                'plan_start' => '2026-07-17 07:00:00',
                'plan_finish' => '2026-07-17 15:00:00',
                'status' => 'FINISHED',
            ],
        ]);

        DB::table('production_result')->insert([
            [
                'wo_number' => 'WO1',
                'actual_start' => '2026-07-19 08:00:00',
                'actual_finish' => '2026-07-19 12:00:00',
                'runtime_minutes' => 240,
                'good_qty' => 800,
                'reject_qty' => 20,
                'achievement' => 80,
            ],
            [
                'wo_number' => 'WO1',
                'actual_start' => '2026-07-19 12:00:00',
                'actual_finish' => '2026-07-19 14:00:00',
                'runtime_minutes' => 120,
                'good_qty' => 100,
                'reject_qty' => 10,
                'achievement' => 10,
            ],
            [
                'wo_number' => 'WO2',
                'actual_start' => '2026-07-17 08:00:00',
                'actual_finish' => '2026-07-17 12:00:00',
                'runtime_minutes' => 240,
                'good_qty' => 450,
                'reject_qty' => 15,
                'achievement' => 90,
            ],
        ]);

        DB::table('downtime')->insert([
            [
                'wo_number' => 'WO1',
                'downtime_reason' => 'Changeover',
                'start_time' => '2026-07-19 10:00:00',
                'end_time' => '2026-07-19 10:30:00',
                'duration_minutes' => 30,
            ],
        ]);
    }
}
