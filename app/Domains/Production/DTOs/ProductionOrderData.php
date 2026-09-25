<?php

namespace App\Domains\Production\DTOs;

use App\Domains\Production\Enums\WorkOrderStatus;
use App\Domains\Production\Models\WorkOrder;

final readonly class ProductionOrderData
{
    public function __construct(
        public string $workOrderNumber,
        public string $productCode,
        public string $productName,
        public string $machineCode,
        public string $machineName,
        public string $employeeNumber,
        public string $employeeName,
        public string $shift,
        public int $targetQuantity,
        public string $planStart,
        public string $planFinish,
        public WorkOrderStatus|string $status,
        public int $goodQuantity,
        public int $rejectQuantity,
    ) {
    }

    public static function fromModel(WorkOrder $workOrder): self
    {
        return new self(
            workOrderNumber: (string) $workOrder->wo_number,
            productCode: (string) $workOrder->product_code,
            productName: (string) $workOrder->product_name,
            machineCode: (string) $workOrder->machine_code,
            machineName: (string) $workOrder->machine_name,
            employeeNumber: (string) $workOrder->employee_no,
            employeeName: (string) $workOrder->employee_name,
            shift: (string) $workOrder->shift,
            targetQuantity: (int) $workOrder->target_qty,
            planStart: (string) $workOrder->plan_start,
            planFinish: (string) $workOrder->plan_finish,
            status: WorkOrderStatus::tryFrom(strtoupper((string) $workOrder->status)) ?? (string) $workOrder->status,
            goodQuantity: (int) $workOrder->good_qty,
            rejectQuantity: (int) $workOrder->reject_qty,
        );
    }

    public function toArray(): array
    {
        return [
            'wo_number' => $this->workOrderNumber,
            'product_code' => $this->productCode,
            'product_name' => $this->productName,
            'machine_code' => $this->machineCode,
            'machine_name' => $this->machineName,
            'employee_no' => $this->employeeNumber,
            'employee_name' => $this->employeeName,
            'shift' => $this->shift,
            'target_qty' => $this->targetQuantity,
            'plan_start' => $this->planStart,
            'plan_finish' => $this->planFinish,
            'status' => $this->status instanceof WorkOrderStatus ? $this->status->value : $this->status,
            'good_qty' => $this->goodQuantity,
            'reject_qty' => $this->rejectQuantity,
        ];
    }
}
