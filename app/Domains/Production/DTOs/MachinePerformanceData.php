<?php

namespace App\Domains\Production\DTOs;

final readonly class MachinePerformanceData
{
    public function __construct(
        public string $machineCode,
        public string $machineName,
        public int $totalOrder,
        public int $goodQty,
        public int $rejectQty,
        public int $downtimeMinutes,
        public float $achievement,
    ) {
    }

    public function toArray(): array
    {
        return [
            'machine_code' => $this->machineCode,
            'machine_name' => $this->machineName,
            'total_order' => $this->totalOrder,
            'good_qty' => $this->goodQty,
            'reject_qty' => $this->rejectQty,
            'downtime_minutes' => $this->downtimeMinutes,
            'achievement' => $this->achievement,
        ];
    }
}
