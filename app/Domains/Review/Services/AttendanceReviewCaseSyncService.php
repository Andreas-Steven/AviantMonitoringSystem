<?php

namespace App\Domains\Review\Services;

use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Review\Models\AttendanceReviewCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceReviewCaseSyncService
{
    /**
     * Sinkronkan review case untuk 1 employee + 1 work_date berdasarkan hasil attendance_daily terbaru.
     *
     * Return example:
     * [
     *   'created' => 1,
     *   'updated' => 0,
     *   'closed' => 0,
     *   'skipped' => false,
     *   'reason' => null,
     * ]
     */
    public function syncForEmployeeDay(int $empId, string $workDate): array
    {
        /** @var AttendanceDaily|null $daily */
        $daily = AttendanceDaily::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->first();

        if (! $daily) {
            return [
                'created' => 0,
                'updated' => 0,
                'closed' => 0,
                'skipped' => true,
                'reason' => 'attendance_daily_not_found',
            ];
        }

        $caseTypeCode = $this->resolveCaseTypeCode($daily);
        $severityCode = $this->resolveSeverityCode($daily);

        if ($caseTypeCode === null) {
            $closed = $this->closeOpenCasesForDate($empId, $workDate);

            return [
                'created' => 0,
                'updated' => 0,
                'closed' => $closed,
                'skipped' => false,
                'reason' => 'daily_not_reviewable',
            ];
        }

        $existing = AttendanceReviewCase::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->where('case_type_code', $caseTypeCode)
            ->orderByDesc('review_case_id')
            ->first();

        $created = 0;
        $updated = 0;

        if ($existing) {
            $payload = [
                'severity_code' => $severityCode,
                'updated_at' => now(),
            ];

            // Jika sebelumnya final lalu daily sekarang masih bermasalah, reopen.
            if (in_array($existing->review_status_code, ['RESOLVED', 'REJECTED', 'CLOSED'], true)) {
                $payload['review_status_code'] = 'OPEN';
                $payload['resolution_type_code'] = null;
                $payload['resolved_by'] = null;
                $payload['resolved_at'] = null;
            }

            $notes = $this->buildSystemSyncNote($daily);
            if ($notes !== null) {
                $payload['notes'] = $this->mergeNotes($existing->notes, $notes);
            }

            AttendanceReviewCase::query()
                ->where('review_case_id', $existing->review_case_id)
                ->update($payload);

            $updated = 1;
        } else {
            AttendanceReviewCase::query()->create([
                'emp_id' => $empId,
                'work_date' => $workDate,
                'case_type_code' => $caseTypeCode,
                'severity_code' => $severityCode,
                'detected_at' => now(),
                'review_status_code' => 'OPEN',
                'resolution_type_code' => null,
                'resolved_by' => null,
                'resolved_at' => null,
                'notes' => $this->buildSystemSyncNote($daily),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created = 1;
        }

        // Tutup case open/in_review lain pada tanggal itu yang sekarang sudah tidak relevan.
        $closed = $this->closeOtherOpenCasesForDate(
            empId: $empId,
            workDate: $workDate,
            keepCaseTypeCode: $caseTypeCode,
        );

        return [
            'created' => $created,
            'updated' => $updated,
            'closed' => $closed,
            'skipped' => false,
            'reason' => null,
            'case_type_code' => $caseTypeCode,
            'severity_code' => $severityCode,
        ];
    }

    private function resolveCaseTypeCode(AttendanceDaily $daily): ?string
    {
        $reviewReason = strtoupper((string) ($daily->review_reason_code ?? ''));

        if ($reviewReason === 'SHIFT_MISSING') {
            return 'SHIFT_MISMATCH';
        }

        if (in_array($reviewReason, ['MISSING_IN', 'FORGOT_CHECKIN_APPROVED'], true)) {
            return 'MISSING_IN';
        }

        if (in_array($reviewReason, ['MISSING_OUT', 'FORGOT_CHECKOUT_APPROVED'], true)) {
            return 'MISSING_OUT';
        }

        if ($daily->attendance_status_code === 'MANUAL_REVIEW') {
            return 'UNMATCHED_LOG';
        }

        if ($daily->attendance_status_code === 'INCOMPLETE') {
            if ($daily->actual_in_datetime === null && $daily->actual_out_datetime !== null) {
                return 'MISSING_IN';
            }

            if ($daily->actual_in_datetime !== null && $daily->actual_out_datetime === null) {
                return 'MISSING_OUT';
            }

            return 'UNMATCHED_LOG';
        }

        if ((bool) $daily->anomaly_flag) {
            return 'UNMATCHED_LOG';
        }

        return null;
    }

    private function resolveSeverityCode(AttendanceDaily $daily): string
    {
        $reviewReason = strtoupper((string) ($daily->review_reason_code ?? ''));

        if ($reviewReason === 'SHIFT_MISSING') {
            return 'HIGH';
        }

        if ($daily->attendance_status_code === 'MANUAL_REVIEW') {
            return 'HIGH';
        }

        if ($daily->attendance_status_code === 'INCOMPLETE') {
            return 'MEDIUM';
        }

        if ((bool) $daily->anomaly_flag) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    private function closeOpenCasesForDate(int $empId, string $workDate): int
    {
        $rows = AttendanceReviewCase::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
            ->get();

        $count = 0;

        foreach ($rows as $row) {
            $row->update([
                'review_status_code' => 'CLOSED',
                'resolution_type_code' => 'NO_ACTION',
                'resolved_at' => now(),
                'updated_at' => now(),
                'notes' => $this->mergeNotes(
                    $row->notes,
                    $this->systemClosedNote()
                ),
            ]);

            $count++;
        }

        return $count;
    }

    private function closeOtherOpenCasesForDate(int $empId, string $workDate, string $keepCaseTypeCode): int
    {
        $rows = AttendanceReviewCase::query()
            ->where('emp_id', $empId)
            ->whereDate('work_date', $workDate)
            ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
            ->where('case_type_code', '!=', $keepCaseTypeCode)
            ->get();

        $count = 0;

        foreach ($rows as $row) {
            $row->update([
                'review_status_code' => 'CLOSED',
                'resolution_type_code' => 'NO_ACTION',
                'resolved_at' => now(),
                'updated_at' => now(),
                'notes' => $this->mergeNotes(
                    $row->notes,
                    $this->systemClosedNote()
                ),
            ]);

            $count++;
        }

        return $count;
    }

    private function buildSystemSyncNote(AttendanceDaily $daily): ?string
    {
        $parts = ['[SYSTEM SYNC]'];

        if ($daily->attendance_status_code) {
            $parts[] = 'status=' . $daily->attendance_status_code;
        }

        if ($daily->presence_type_code) {
            $parts[] = 'presence=' . $daily->presence_type_code;
        }

        if ($daily->review_reason_code) {
            $parts[] = 'reason=' . $daily->review_reason_code;
        }

        if ((bool) $daily->anomaly_flag) {
            $parts[] = 'anomaly=1';
        }

        return implode(' | ', $parts);
    }

    private function systemClosedNote(): string
    {
        return '[SYSTEM SYNC] Closed automatically because latest daily result is no longer reviewable.';
    }

    private function mergeNotes(?string $existing, string $append): string
    {
        $existing = trim((string) $existing);
        $append = trim($append);

        if ($existing === '') {
            return $append;
        }

        // Hindari duplikasi note identik berturut-turut
        if (str_ends_with($existing, $append)) {
            return $existing;
        }

        return $existing . PHP_EOL . $append;
    }
}