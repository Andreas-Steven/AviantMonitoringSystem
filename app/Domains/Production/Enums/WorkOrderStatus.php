<?php

namespace App\Domains\Production\Enums;

enum WorkOrderStatus: string
{
    case Running = 'RUNNING';
    case Finished = 'FINISHED';
    case Open = 'OPEN';
    case Cancelled = 'CANCELLED';

    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }
}
