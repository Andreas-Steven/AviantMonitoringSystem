<?php

namespace App\Domains\Production\Repositories;

use App\Domains\Production\Models\Downtime;
use App\Domains\Production\Models\Employee;
use App\Domains\Production\Models\Machine;
use App\Domains\Production\Models\Product;
use App\Domains\Production\Models\ProductionResult;
use App\Domains\Production\Models\WorkOrder;
use Illuminate\Support\Facades\Schema;

class ProductionSchemaRepository
{
    private const REQUIRED_MODELS = [
        Employee::class,
        Machine::class,
        Product::class,
        WorkOrder::class,
        ProductionResult::class,
        Downtime::class,
    ];

    public function missingTables(): array
    {
        $tables = array_map(
            static fn (string $modelClass): string => (new $modelClass())->getTable(),
            self::REQUIRED_MODELS,
        );

        return array_values(array_filter(
            $tables,
            fn (string $table): bool => ! Schema::hasTable($table),
        ));
    }
}
