<?php

namespace App\Domains\Scheduling\Services;

use App\Domains\Scheduling\Models\BranchPolicyAssignment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BranchPolicyAssignmentDomainService
{
    public function getActiveAssignments(
        int $branchId,
        Carbon|string $referenceDate,
        array $ignoreIds = []
    ): Collection {
        $referenceDate = $referenceDate instanceof Carbon
            ? $referenceDate->toDateString()
            : Carbon::parse($referenceDate)->toDateString();

        return BranchPolicyAssignment::query()
            ->where('branch_id', $branchId)
            ->when(!empty($ignoreIds), function ($query) use ($ignoreIds) {
                $query->whereNotIn('branch_policy_assignment_id', $ignoreIds);
            })
            ->whereDate('effective_start_date', '<=', $referenceDate)
            ->where(function ($q) use ($referenceDate) {
                $q->whereNull('effective_end_date')
                  ->orWhereDate('effective_end_date', '>=', $referenceDate);
            })
            ->get();
    }

    public function hasOverlap(
        int $branchId,
        string $startDate,
        ?string $endDate = null,
        array $ignoreIds = []
    ): bool {
        $endDate = $endDate ?? '9999-12-31';

        return BranchPolicyAssignment::query()
            ->where('branch_id', $branchId)
            ->when(!empty($ignoreIds), function ($query) use ($ignoreIds) {
                $query->whereNotIn('branch_policy_assignment_id', $ignoreIds);
            })
            ->whereDate('effective_start_date', '<=', $endDate)
            ->whereRaw('? <= COALESCE(effective_end_date, DATE \'9999-12-31\')', [$startDate])
            ->exists();
    }

    public function assertStoreAllowed(array $data): void
    {
        if ($this->hasOverlap(
            branchId: (int)$data['branch_id'],
            startDate: $data['effective_start_date'],
            endDate: $data['effective_end_date'] ?? null
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Periode policy bertabrakan dengan policy lain pada branch ini.',
            ]);
        }
    }

    public function assertUpdateAllowed(BranchPolicyAssignment $assignment, array $data): void
    {
        if ((int)$data['branch_id'] !== (int)$assignment->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch tidak boleh diganti pada edit assignment.',
            ]);
        }

        if ($this->hasOverlap(
            branchId: (int)$assignment->branch_id,
            startDate: $data['effective_start_date'],
            endDate: $data['effective_end_date'] ?? null,
            ignoreIds: [$assignment->branch_policy_assignment_id]
        )) {
            throw ValidationException::withMessages([
                'effective_start_date' => 'Perubahan menyebabkan overlap dengan policy lain.',
            ]);
        }
    }
}