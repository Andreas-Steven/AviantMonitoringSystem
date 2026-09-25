<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\DayType;
use App\Domains\Scheduling\Models\HolidayEvent;
use App\Domains\Scheduling\Models\HolidayEventScope;
use App\Domains\Scheduling\Services\BranchCalendarHolidaySyncService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scheduling\StoreHolidayEventRequest;
use App\Http\Requests\Scheduling\UpdateHolidayEventRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HolidayEventController extends Controller
{
    public function index(Request $request): View
    {
        $query = HolidayEvent::query()
            ->with(['scopes.branch', 'dayType'])
            ->orderByDesc('holiday_date')
            ->orderBy('holiday_name');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('holiday_code', 'ilike', "%{$keyword}%")
                    ->orWhere('holiday_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('day_type_code')) {
            $query->where('day_type_code', $request->string('day_type_code'));
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->boolean('active'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('holiday_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('holiday_date', '<=', $request->input('date_to'));
        }

        $events = $query->paginate(15)->withQueryString();

        $dayTypes = DayType::query()
            ->whereIn('day_type_code', ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY', 'HALF_DAY', 'SPECIAL'])
            ->orderBy('day_type_name')
            ->get();

        return view('scheduling.holiday-events.index', compact('events', 'dayTypes'));
    }

    public function create(): View
    {
        return view('scheduling.holiday-events.create', $this->formData());
    }

    public function store(
        StoreHolidayEventRequest $request,
        BranchCalendarHolidaySyncService $calendarHolidaySyncService
    ): RedirectResponse {
        DB::transaction(function () use ($request, $calendarHolidaySyncService): void {
            $event = HolidayEvent::query()->create([
                'holiday_code' => $request->input('holiday_code'),
                'holiday_name' => $request->input('holiday_name'),
                'holiday_date' => $request->input('holiday_date'),
                'day_type_code' => $request->input('day_type_code'),
                'active' => $request->boolean('active', true),
                'notes' => $request->input('notes'),
            ]);

            $this->syncScopes(
                holidayEventId: $event->holiday_event_id,
                scopeMode: $request->input('scope_mode'),
                branchIds: $request->input('branch_ids', []),
                scopeNotes: $request->input('scope_notes'),
            );

            $event->load('scopes');

            $calendarHolidaySyncService->syncDatesForBranches(
                workDates: [$event->holiday_date->toDateString()],
                branchIds: $calendarHolidaySyncService
                    ->resolveBranchIdsFromHolidayEvent($event)
                    ->all(),
            );
        });

        return redirect()
            ->route('scheduling.holiday-events.index')
            ->with('success', 'Holiday event berhasil ditambahkan dan branch calendar yang sudah ada ikut disinkron.');
    }

    public function show(int $holiday_event): View
    {
        $event = HolidayEvent::query()
            ->with(['scopes.branch', 'dayType'])
            ->findOrFail($holiday_event);

        return view('scheduling.holiday-events.show', compact('event'));
    }

    public function edit(int $holiday_event): View
    {
        $event = HolidayEvent::query()
            ->with(['scopes.branch'])
            ->findOrFail($holiday_event);

        return view('scheduling.holiday-events.edit', array_merge(
            ['event' => $event],
            $this->formData()
        ));
    }

    public function update(
        UpdateHolidayEventRequest $request,
        int $holiday_event,
        BranchCalendarHolidaySyncService $calendarHolidaySyncService
    ): RedirectResponse {
        $event = HolidayEvent::query()
            ->with('scopes')
            ->findOrFail($holiday_event);

        $oldHolidayDate = $event->holiday_date->toDateString();
        $oldBranchIds = $calendarHolidaySyncService
            ->resolveBranchIdsFromHolidayEvent($event)
            ->all();

        DB::transaction(function () use (
            $request,
            $event,
            $calendarHolidaySyncService,
            $oldHolidayDate,
            $oldBranchIds
        ): void {
            $event->update([
                'holiday_code' => $request->input('holiday_code'),
                'holiday_name' => $request->input('holiday_name'),
                'holiday_date' => $request->input('holiday_date'),
                'day_type_code' => $request->input('day_type_code'),
                'active' => $request->boolean('active', true),
                'notes' => $request->input('notes'),
            ]);

            HolidayEventScope::query()
                ->where('holiday_event_id', $event->holiday_event_id)
                ->delete();

            $this->syncScopes(
                holidayEventId: $event->holiday_event_id,
                scopeMode: $request->input('scope_mode'),
                branchIds: $request->input('branch_ids', []),
                scopeNotes: $request->input('scope_notes'),
            );

            $event->refresh();
            $event->load('scopes');

            $newHolidayDate = $event->holiday_date->toDateString();

            $newBranchIds = $calendarHolidaySyncService
                ->resolveBranchIdsFromHolidayEvent($event)
                ->all();

            $affectedDates = collect([$oldHolidayDate, $newHolidayDate])
                ->filter()
                ->unique()
                ->values()
                ->all();

            $affectedBranchIds = collect($oldBranchIds)
                ->merge($newBranchIds)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $calendarHolidaySyncService->syncDatesForBranches(
                workDates: $affectedDates,
                branchIds: $affectedBranchIds,
            );
        });

        return redirect()
            ->route('scheduling.holiday-events.index')
            ->with('success', 'Holiday event berhasil diperbarui dan branch calendar yang sudah ada ikut disinkron.');
    }

    protected function formData(): array
    {
        return [
            'branches' => Branch::query()
                ->where('active', true)
                ->orderBy('branch_name')
                ->get(),

            'dayTypes' => DayType::query()
                ->whereIn('day_type_code', ['HOLIDAY_NATIONAL', 'HOLIDAY_COMPANY', 'HALF_DAY', 'SPECIAL'])
                ->orderBy('day_type_name')
                ->get(),
        ];
    }

    protected function syncScopes(
        int $holidayEventId,
        string $scopeMode,
        array $branchIds,
        ?string $scopeNotes = null
    ): void {
        if ($scopeMode === 'all') {
            HolidayEventScope::query()->create([
                'holiday_event_id' => $holidayEventId,
                'branch_id' => null,
                'applies_to_all_branches' => true,
                'notes' => $scopeNotes,
                'created_at' => now(),
            ]);

            return;
        }

        foreach (collect($branchIds)->filter()->unique() as $branchId) {
            HolidayEventScope::query()->create([
                'holiday_event_id' => $holidayEventId,
                'branch_id' => (int) $branchId,
                'applies_to_all_branches' => false,
                'notes' => $scopeNotes,
                'created_at' => now(),
            ]);
        }
    }
}