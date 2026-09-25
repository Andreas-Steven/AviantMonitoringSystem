<?php

namespace App\Domains\Attendance\DTOs;

class ExceptionApplyResult
{
    public function __construct(
        public array $dailyDraft,
        public array $appliedExceptions = [],
        public array $detailLogs = [],
    ) {
    }
}