<?php

namespace App\Domains\Production\DTOs;

final readonly class StoredProductionResultData
{
    public function __construct(
        public int $id,
        public string $workOrderNumber,
        public string $productionDate,
        public string $productionFinish,
        public int $quantityGood,
        public int $quantityReject,
        public int $runtimeMinutes,
        public float $achievement,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'wo_number' => $this->workOrderNumber,
            'production_date' => $this->productionDate,
            'production_finish' => $this->productionFinish,
            'qty_good' => $this->quantityGood,
            'qty_reject' => $this->quantityReject,
            'runtime_minutes' => $this->runtimeMinutes,
            'achievement' => $this->achievement,
        ];
    }
}
