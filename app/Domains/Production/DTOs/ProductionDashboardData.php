<?php

namespace App\Domains\Production\DTOs;

final readonly class ProductionDashboardData
{
    public function __construct(
        public array $summary,
        public array $trend7Days,
        public array $statusBreakdown,
        public array $topMachines,
    ) {
    }

    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'trend_7_days' => $this->trend7Days,
            'status_breakdown' => $this->statusBreakdown,
            'top_machines' => $this->topMachines,
        ];
    }
}
