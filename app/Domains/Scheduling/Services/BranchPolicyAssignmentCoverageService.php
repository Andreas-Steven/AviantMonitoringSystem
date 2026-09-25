<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Master\Models\Branch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class BranchPolicyAssignmentCoverageService
{
    public function getCoverage(array $filters = []): array
    {
        $referenceDate = !empty($filters['reference_date'])
            ? Carbon::parse($filters['reference_date'])->toDateString()
            : Carbon::today()->toDateString();

        $branches = Branch::query()
            ->with([
                'policyAssignments' => function ($query) {
                    $query->with('policy')
                        ->orderByDesc('effective_start_date')
                        ->orderByDesc('branch_policy_assignment_id');
                },
            ])
            ->where('active', true)
            ->when(!empty($filters['q']), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['q']);

                $query->where(function ($sub) use ($keyword): void {
                    $sub->where('branch_code', 'ilike', "%{$keyword}%")
                        ->orWhere('branch_name', 'ilike', "%{$keyword}%");
                });
            })
            ->orderBy('branch_name')
            ->get();

        $rows = $branches->map(function (Branch $branch) use ($referenceDate): array {
            $assignments = $branch->policyAssignments ?? new Collection();

            $activeAssignments = $assignments->filter(function ($assignment) use ($referenceDate): bool {
                $start = optional($assignment->effective_start_date)->format('Y-m-d');
                $end = optional($assignment->effective_end_date)->format('Y-m-d');

                if ($start === null) {
                    return false;
                }

                return $start <= $referenceDate
                    && ($end === null || $end >= $referenceDate);
            })->values();

            $assignmentCount = $assignments->count();
            $activeCount = $activeAssignments->count();
            $latestAssignment = $assignments->first();
            $activeAssignment = $activeCount === 1 ? $activeAssignments->first() : null;

            $coverageStatus = match (true) {
                $assignmentCount === 0 => 'NO_ASSIGNMENT',
                $activeCount === 0 => 'NO_ACTIVE_ASSIGNMENT',
                $activeCount === 1 => 'HAS_ACTIVE_ASSIGNMENT',
                default => 'OVERLAP_DETECTED',
            };

            $coverageNote = match ($coverageStatus) {
                'NO_ASSIGNMENT' => 'Belum pernah memiliki policy assignment.',
                'NO_ACTIVE_ASSIGNMENT' => 'Tidak ada policy assignment aktif pada tanggal referensi.',
                'HAS_ACTIVE_ASSIGNMENT' => 'Branch memiliki satu policy assignment aktif.',
                'OVERLAP_DETECTED' => $activeCount . ' policy assignment aktif terdeteksi pada tanggal referensi.',
                default => '-',
            };

            return [
                'branch' => $branch,
                'coverage_status' => $coverageStatus,
                'active_assignment_count' => $activeCount,
                'active_assignment' => $activeAssignment,
                'active_assignments' => $activeAssignments,
                'latest_assignment' => $latestAssignment,
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
            'no_assignment' => $rows->where('coverage_status', 'NO_ASSIGNMENT')->count(),
            'no_active_assignment' => $rows->where('coverage_status', 'NO_ACTIVE_ASSIGNMENT')->count(),
            'has_active_assignment' => $rows->where('coverage_status', 'HAS_ACTIVE_ASSIGNMENT')->count(),
            'overlap_detected' => $rows->where('coverage_status', 'OVERLAP_DETECTED')->count(),
        ];

        return [
            'referenceDate' => $referenceDate,
            'rows' => $rows,
            'summary' => $summary,
        ];
    }
}