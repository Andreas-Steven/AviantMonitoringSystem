<?php

namespace App\Domains\Requests\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OvertimeResolverService
{
    public function resolveForDate(int $empId, string $workDate): array
    {
        $request = DB::table('overtime_requests')
            ->where('emp_id', $empId)
            ->where('request_status_code', 'APPROVED')
            ->whereDate('work_date', $workDate)
            ->orderByDesc('approved_at')
            ->orderByDesc('overtime_request_id')
            ->first();

        if (!$request) {
            return [
                'has_approved_overtime' => false,
                'overtime_request_id' => null,
                'approved_overtime_min' => 0,
                'planned_overtime_min' => 0,
                'actual_overtime_min' => 0,
                'notes' => null,
            ];
        }

        $plannedMin = 0;
        $actualMin = 0;

        if (!empty($request->planned_start_datetime) && !empty($request->planned_end_datetime)) {
            $plannedStart = Carbon::parse($request->planned_start_datetime);
            $plannedEnd = Carbon::parse($request->planned_end_datetime);
            $plannedMin = max(0, $plannedStart->diffInMinutes($plannedEnd, false));
        }

        if (!empty($request->actual_start_datetime) && !empty($request->actual_end_datetime)) {
            $actualStart = Carbon::parse($request->actual_start_datetime);
            $actualEnd = Carbon::parse($request->actual_end_datetime);
            $actualMin = max(0, $actualStart->diffInMinutes($actualEnd, false));
        }

        // Phase 1 policy:
        // prefer actual approved window if present, otherwise fallback to planned window
        $approvedMin = $actualMin > 0 ? $actualMin : $plannedMin;

        return [
            'has_approved_overtime' => true,
            'overtime_request_id' => (int) $request->overtime_request_id,
            'approved_overtime_min' => $approvedMin,
            'planned_overtime_min' => $plannedMin,
            'actual_overtime_min' => $actualMin,
            'notes' => $request->notes ?? $request->reason ?? null,
        ];
    }
}