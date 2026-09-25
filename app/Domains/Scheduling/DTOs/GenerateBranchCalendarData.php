<?php

namespace App\Domains\Scheduling\DTOs;

final class GenerateBranchCalendarData
{
    public function __construct(
        public readonly int $branchId,
        public readonly int $payrollPeriodId,
        public readonly bool $overwriteExisting = false,
        public readonly ?int $generatedByUserId = null,
    ) {}
}