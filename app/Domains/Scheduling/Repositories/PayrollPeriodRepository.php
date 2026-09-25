<?php

namespace App\Domains\Scheduling\Repositories;

use App\Domains\Scheduling\Models\PayrollPeriod;

class PayrollPeriodRepository
{
    public function findOrFail(int $payrollPeriodId): PayrollPeriod
    {
        return PayrollPeriod::query()->findOrFail($payrollPeriodId);
    }

    public function findByCode(string $periodCode): ?PayrollPeriod
    {
        return PayrollPeriod::query()
            ->where('period_code', $periodCode)
            ->first();
    }
}