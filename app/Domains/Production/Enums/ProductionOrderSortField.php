<?php

namespace App\Domains\Production\Enums;

enum ProductionOrderSortField: string
{
    case WorkOrderNumber = 'wo_number';
    case Product = 'product';
    case Machine = 'machine';
    case Status = 'status';
    case PlanStart = 'plan_start';
    case TargetQuantity = 'target_qty';
    case GoodQuantity = 'good_qty';
    case RejectQuantity = 'reject_qty';

    public static function values(): array
    {
        return array_map(
            static fn (self $field): string => $field->value,
            self::cases(),
        );
    }
}
