<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EmployeeShiftRosterCoverageService
{
    public function getCoverage(array $filters = []): array
    {
        $referenceDate = !empty($filters['reference_date'])
            ? Carbon::parse($filters['reference_date'])->toDateString()
            : Carbon::today()->toDateString();

        $employees = Employee::query()
            ->with([
                'rosters' => function ($query) use ($referenceDate) {
                    $query->with(['shift', 'sourceType'])
                        ->whereDate('work_date', $referenceDate)
                        ->orderByDesc('roster_id');
                },
            ])
            ->where('active', true)
            ->when(!empty($filters['q']), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['q']);

                $query->where(function ($sub) use ($keyword): void {
                    $sub->where('emp_code', 'ilike', "%{$keyword}%")
                        ->orWhere('full_name', 'ilike', "%{$keyword}%")
                        ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                });
            })
            ->orderBy('full_name')
            ->get();

        $rows = $employees->map(function (Employee $employee): array {
            $rosters = $employee->rosters ?? new Collection();

            $rosterCount = $rosters->count();
            $roster = $rosterCount === 1 ? $rosters->first() : null;

            $coverageStatus = match (true) {
                $rosterCount === 0 => 'NO_ROSTER',
                $rosterCount === 1 => 'HAS_ROSTER',
                default => 'DUPLICATE_ROSTER',
            };

            $coverageNote = match ($coverageStatus) {
                'NO_ROSTER' => 'Belum memiliki roster pada tanggal referensi.',
                'HAS_ROSTER' => 'Employee memiliki satu roster pada tanggal referensi.',
                'DUPLICATE_ROSTER' => $rosterCount . ' roster ditemukan pada tanggal referensi.',
                default => '-',
            };

            return [
                'employee' => $employee,
                'coverage_status' => $coverageStatus,
                'roster_count' => $rosterCount,
                'roster' => $roster,
                'rosters' => $rosters,
                'coverage_note' => $coverageNote,
            ];
        });

        if (!empty($filters['coverage_status'])) {
            $selectedStatus = strtoupper((string) $filters['coverage_status']);
            $rows = $rows
                ->filter(fn (array $row): bool => $row['coverage_status'] === $selectedStatus)
                ->values();
        }

        $summary = [
            'no_roster' => $rows->where('coverage_status', 'NO_ROSTER')->count(),
            'has_roster' => $rows->where('coverage_status', 'HAS_ROSTER')->count(),
            'duplicate_roster' => $rows->where('coverage_status', 'DUPLICATE_ROSTER')->count(),
        ];

        return [
            'referenceDate' => $referenceDate,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}