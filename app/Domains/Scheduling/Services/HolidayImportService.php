<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Scheduling\DTOs\ExternalHolidayData;
use App\Domains\Scheduling\Models\HolidayEvent;
use App\Domains\Scheduling\Models\HolidayEventScope;
use App\Domains\Scheduling\Services\HolidayProviders\CalendarificHolidayProvider;
use App\Domains\Scheduling\Services\HolidayProviders\GoogleCalendarHolidayProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HolidayImportService
{
    public function __construct(
        protected CalendarificHolidayProvider $calendarificProvider,
        protected GoogleCalendarHolidayProvider $googleProvider,
        protected BranchCalendarHolidaySyncService $calendarHolidaySyncService,
    ) {
    }

    public function preview(string $source, int $year): Collection
    {
        return match ($source) {
            'calendarific' => $this->previewCalendarific($year),
            'google' => $this->previewGoogle($year),
            default => collect(),
        };
    }

    public function previewCalendarific(int $year): Collection
    {
        return $this->calendarificProvider
            ->fetchByYear($year)
            ->map(fn (ExternalHolidayData $holiday): array => $this->buildPreviewRow($holiday))
            ->values();
    }

    public function previewGoogle(int $year): Collection
    {
        return $this->googleProvider
            ->fetchByYear($year)
            ->map(fn (ExternalHolidayData $holiday): array => $this->buildPreviewRow($holiday))
            ->values();
    }

    public function importSelected(array $rows, string $scopeMode, array $branchIds = []): array
    {
        $result = [
            'created_count' => 0,
            'skipped_count' => 0,
            'skipped' => [],
        ];

        $rows = collect($rows)
            ->filter(fn (array $row): bool => (string) ($row['enabled'] ?? '0') === '1')
            ->values()
            ->all();

        DB::transaction(function () use ($rows, $scopeMode, $branchIds, &$result): void {
            foreach ($rows as $row) {
                if (($row['status'] ?? null) !== 'NEW') {
                    $result['skipped_count']++;
                    $result['skipped'][] = [
                        'holiday_date' => $row['holiday_date'] ?? null,
                        'holiday_name' => $row['suggested_name'] ?? null,
                        'reason' => 'Only NEW rows can be imported in this phase.',
                    ];

                    continue;
                }

                if ($this->existsByCode((string) $row['holiday_code'])) {
                    $result['skipped_count']++;
                    $result['skipped'][] = [
                        'holiday_date' => $row['holiday_date'] ?? null,
                        'holiday_name' => $row['suggested_name'] ?? null,
                        'reason' => 'Holiday code already exists.',
                    ];

                    continue;
                }

                $event = HolidayEvent::query()->create([
                    'holiday_code' => (string) $row['holiday_code'],
                    'holiday_name' => (string) $row['suggested_name'],
                    'holiday_date' => (string) $row['holiday_date'],
                    'day_type_code' => (string) ($row['day_type_code'] ?? 'HOLIDAY_NATIONAL'),
                    'active' => true,
                    'notes' => 'Imported from ' . ((string) ($row['source'] ?? 'external')) . '. Original name: ' . ((string) ($row['original_name'] ?? '-')),
                ]);

                $this->createScopes($event, $scopeMode, $branchIds);

                $event->load('scopes');

                $this->calendarHolidaySyncService->syncDatesForBranches(
                    workDates: [$event->holiday_date->toDateString()],
                    branchIds: $this->calendarHolidaySyncService
                        ->resolveBranchIdsFromHolidayEvent($event)
                        ->all(),
                );

                $result['created_count']++;
            }
        });

        return $result;
    }

    protected function buildPreviewRow(ExternalHolidayData $holiday): array
    {
        $sameCode = HolidayEvent::query()
            ->where('holiday_code', $holiday->holidayCode)
            ->exists();

        if ($sameCode) {
            return array_merge($holiday->toArray(), [
                'status' => 'EXISTS',
                'status_note' => 'Holiday code already exists.',
            ]);
        }

        $sameDateEvents = HolidayEvent::query()
            ->whereDate('holiday_date', $holiday->holidayDate)
            ->get();

        if ($sameDateEvents->isEmpty()) {
            return array_merge($holiday->toArray(), [
                'status' => 'NEW',
                'status_note' => 'Ready to import.',
            ]);
        }

        $sameNameOnDate = $sameDateEvents
            ->contains(fn (HolidayEvent $event): bool =>
                trim(strtolower($event->holiday_name)) === trim(strtolower($holiday->suggestedName))
            );

        if ($sameNameOnDate) {
            return array_merge($holiday->toArray(), [
                'status' => 'EXISTS',
                'status_note' => 'Holiday with same date and name already exists.',
            ]);
        }

        return array_merge($holiday->toArray(), [
            'status' => 'CONFLICT',
            'status_note' => 'Another holiday already exists on this date.',
        ]);
    }

    protected function existsByCode(string $holidayCode): bool
    {
        return HolidayEvent::query()
            ->where('holiday_code', $holidayCode)
            ->exists();
    }

    protected function createScopes(HolidayEvent $event, string $scopeMode, array $branchIds): void
    {
        if ($scopeMode === 'all') {
            HolidayEventScope::query()->create([
                'holiday_event_id' => $event->holiday_event_id,
                'branch_id' => null,
                'applies_to_all_branches' => true,
                'notes' => 'Imported from holiday provider',
                'created_at' => now(),
            ]);

            return;
        }

        foreach (collect($branchIds)->filter()->unique() as $branchId) {
            HolidayEventScope::query()->create([
                'holiday_event_id' => $event->holiday_event_id,
                'branch_id' => (int) $branchId,
                'applies_to_all_branches' => false,
                'notes' => 'Imported from holiday provider',
                'created_at' => now(),
            ]);
        }
    }
}