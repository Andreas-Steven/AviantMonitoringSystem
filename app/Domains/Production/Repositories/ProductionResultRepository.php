<?php

namespace App\Domains\Production\Repositories;

use App\Domains\Production\Models\ProductionResult;

class ProductionResultRepository
{
    public function create(array $attributes): ProductionResult
    {
        return ProductionResult::query()->create($attributes);
    }
}
