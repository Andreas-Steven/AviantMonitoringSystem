<?php

namespace App\Domains\Scheduling\Services\HolidayProviders;

use App\Domains\Scheduling\DTOs\ExternalHolidayData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleCalendarHolidayProvider
{
    public function fetchByYear(int $year): Collection
    {
        $calendarId = config('services.google_holiday.calendar_id');

        $encodedCalendarId = rawurlencode($calendarId);
        $url = "https://calendar.google.com/calendar/ical/{$encodedCalendarId}/public/basic.ics";

        $response = Http::get($url);

        if (!$response->successful()) {
            throw new \RuntimeException(
                'Failed to fetch Google Holiday ICS. Status: '
                . $response->status()
                . ' Response: '
                . $response->body()
            );
        }

        return $this->parseIcs($response->body(), $year);
    }

    protected function parseIcs(string $icsContent, int $year): Collection
    {
        $events = collect();
        $blocks = preg_split('/BEGIN:VEVENT/', $icsContent);

        foreach ($blocks as $block) {
            if (!str_contains($block, 'END:VEVENT')) {
                continue;
            }

            $date = $this->extractDate($block);
            $summary = $this->extractSummary($block);
            $uid = $this->extractUid($block);

            if (!$date || !$summary || !str_starts_with($date, (string) $year)) {
                continue;
            }

            $events->push(new ExternalHolidayData(
                source: 'GOOGLE_ICS',
                externalId: $uid ?: Str::uuid()->toString(),
                holidayDate: $date,
                originalName: $summary,
                suggestedName: $this->mapName($summary),
                holidayCode: $this->generateCode($date, $summary),
            ));
        }

        return $events
            ->unique(fn (ExternalHolidayData $dto) => $dto->holidayDate . '|' . $dto->originalName)
            ->sortBy('holidayDate')
            ->values();
    }

    protected function extractDate(string $block): ?string
    {
        if (preg_match('/DTSTART(?:;VALUE=DATE)?:([0-9]{8})/', $block, $matches)) {
            return substr($matches[1], 0, 4)
                . '-'
                . substr($matches[1], 4, 2)
                . '-'
                . substr($matches[1], 6, 2);
        }

        return null;
    }

    protected function extractSummary(string $block): ?string
    {
        if (preg_match('/SUMMARY:(.+)/', $block, $matches)) {
            return trim($this->decodeIcsText($matches[1]));
        }

        return null;
    }

    protected function extractUid(string $block): ?string
    {
        if (preg_match('/UID:(.+)/', $block, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function decodeIcsText(string $value): string
    {
        return str_replace(
            ['\\,', '\\;', '\\n', '\\N'],
            [',', ';', ' ', ' '],
            trim($value)
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
            'Ascension Day' => 'Kenaikan Isa Almasih',
            'Vesak Day' => 'Hari Raya Waisak',
            'Pancasila Day' => 'Hari Lahir Pancasila',
            'Eid al-Adha' => 'Idul Adha',
            'Islamic New Year' => 'Tahun Baru Islam',
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

        return "GOOGLE_{$datePart}_{$slug}";
    }
}