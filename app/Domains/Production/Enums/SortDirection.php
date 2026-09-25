<?php

namespace App\Domains\Production\Enums;

enum SortDirection: string
{
    case Ascending = 'asc';
    case Descending = 'desc';

    public static function values(): array
    {
        return array_map(
            static fn (self $direction): string => $direction->value,
            self::cases(),
        );
    }
}
