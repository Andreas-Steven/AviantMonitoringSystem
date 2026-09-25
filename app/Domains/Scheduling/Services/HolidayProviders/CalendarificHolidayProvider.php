<?php

namespace App\Domains\Scheduling\Services\HolidayProviders;

use App\Domains\Scheduling\DTOs\ExternalHolidayData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CalendarificHolidayProvider
{
    protected string $baseUrl = 'https://calendarific.com/api/v2/holidays';

    public function fetchByYear(int $year): Collection
    {
        $apiKey = config('services.calendarific.api_key');
        $country = config('services.calendarific.country', 'ID');

        if (blank($apiKey)) {
            throw new \RuntimeException('Calendarific API key belum dikonfigurasi.');
        }

        $response = Http::get($this->baseUrl, [
            'api_key' => $apiKey,
            'country' => $country,
            'year' => $year,
            'type' => 'national',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Failed to fetch Calendarific API. Status: '
                . $response->status()
                . ' Response: '
                . $response->body()
            );
        }

        $holidays = $response->json('response.holidays', []);

        return collect($holidays)
            ->map(fn (array $item): ExternalHolidayData => $this->mapToDTO($item))
            ->unique(fn (ExternalHolidayData $dto): string => $dto->holidayDate . '|' . $dto->originalName)
            ->sortBy('holidayDate')
            ->values();
    }

    protected function mapToDTO(array $item): ExternalHolidayData
    {
        $date = (string) data_get($item, 'date.iso');
        $name = (string) data_get($item, 'name', 'Unknown Holiday');

        return new ExternalHolidayData(
            source: 'CALENDARIFIC',
            externalId: (string) data_get($item, 'uuid', Str::uuid()->toString()),
            holidayDate: substr($date, 0, 10),
            originalName: $name,
            suggestedName: $this->mapName($name),
            holidayCode: $this->generateCode(substr($date, 0, 10), $name),
        );
    }

    protected function mapName(string $name): string
    {
        $mapping = [
            "New Year's Day" => 'Tahun Baru',
            'Chinese New Year' => 'Tahun Baru Imlek',
            'Isra and Mi\'raj' => 'Isra Mikraj',
            'Hindu New Year' => 'Hari Suci Nyepi',
            'Good Friday' => 'Wafat Isa Almasih',
            'Easter Sunday' => 'Hari Paskah',
            'Eid al-Fitr' => 'Idul Fitri',
            'Labor Day' => 'Hari Buruh',
            'Ascension Day of Jesus Christ' => 'Kenaikan Isa Almasih',
            'Ascension Day' => 'Kenaikan Isa Almasih',
            'Vesak Day' => 'Hari Raya Waisak',
            'Pancasila Day' => 'Hari Lahir Pancasila',
            'Eid al-Adha' => 'Idul Adha',
            'Islamic New Year' => 'Tahun Baru Islam',
            'Indonesian Independence Day' => 'Hari Kemerdekaan Republik Indonesia',
            'Independence Day' => 'Hari Kemerdekaan Republik Indonesia',
            'The Prophet Muhammad\'s Birthday' => 'Maulid Nabi Muhammad SAW',
            'Christmas Day' => 'Hari Raya Natal',
        ];

        return $mapping[$name] ?? $name;
    }

    protected function generateCode(string $date, string $name): string
    {
        $datePart = str_replace('-', '', $date);
        $slug = Str::upper(Str::slug($name, '_'));

        return "CALENDARIFIC_{$datePart}_{$slug}";
    }
}