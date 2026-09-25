<?php

namespace App\Domains\Summary\Services;

use Illuminate\Support\Facades\DB;

class PayrollRatePolicyResolverService
{
    public function resolve(
        int $branchId,
        string $summaryBasisTypeCode,
        string $dateFrom,
        string $dateTo
    ): ?object {
        $query = DB::table('payroll_rate_policies')
            ->where('active', true)
            ->whereDate('effective_start_date', '<=', $dateTo)
            ->where(function ($q) use ($dateFrom) {
                $q->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $dateFrom);
            });

        $query->orderByRaw("
            CASE
                WHEN branch_id = ? AND summary_basis_type_code = ? THEN 1
                WHEN branch_id = ? AND summary_basis_type_code IS NULL THEN 2
                WHEN branch_id IS NULL AND summary_basis_type_code = ? THEN 3
                WHEN branch_id IS NULL AND summary_basis_type_code IS NULL THEN 4
                ELSE 99
            END
        ", [$branchId, $summaryBasisTypeCode, $branchId, $summaryBasisTypeCode]);

        return $query->first();
    }
}