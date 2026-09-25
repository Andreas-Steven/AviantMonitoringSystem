<?php

namespace App\Domains\Scheduling\Actions;

use App\Domains\Scheduling\DTOs\GenerateBranchCalendarData;
use App\Domains\Scheduling\Services\CalendarGenerationService;

class GenerateBranchCalendarAction
{
    public function __construct(
        protected CalendarGenerationService $calendarGenerationService,
    ) {}

    public function execute(GenerateBranchCalendarData $data): void
    {
        $this->calendarGenerationService->generate($data);
    }
}