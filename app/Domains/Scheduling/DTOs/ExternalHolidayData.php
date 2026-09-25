<?php

namespace App\Domains\Scheduling\DTOs;

class ExternalHolidayData
{
    public function __construct(
        public string $source,
        public string $externalId,
        public string $holidayDate,
        public string $originalName,
        public string $suggestedName,
        public string $holidayCode,
        public string $dayTypeCode = 'HOLIDAY_NATIONAL',
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'external_id' => $this->externalId,
            'holiday_date' => $this->holidayDate,
            'original_name' => $this->originalName,
            'suggested_name' => $this->suggestedName,
            'holiday_code' => $this->holidayCode,
            'day_type_code' => $this->dayTypeCode,
        ];
    }
}