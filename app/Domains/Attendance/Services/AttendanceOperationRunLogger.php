<?php

namespace App\Domains\Attendance\Services;

use App\Domains\Attendance\Models\AttendanceOperationRun;
use Illuminate\Support\Carbon;
use Throwable;

class AttendanceOperationRunLogger
{
    public function start(
        string $operationTypeCode,
        string $dateFrom,
        string $dateTo,
        ?int $payrollPeriodId = null,
        ?int $triggeredBy = null
    ): AttendanceOperationRun {
        return AttendanceOperationRun::query()->create([
            'operation_type_code' => $operationTypeCode,
            'payroll_period_id' => $payrollPeriodId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'triggered_by' => $triggeredBy,
            'operation_status_code' => 'RUNNING',
            'result_json' => null,
            'error_message' => null,
            'started_at' => now(),
            'finished_at' => null,
        ]);
    }

    public function succeed(AttendanceOperationRun $run, array $result = []): AttendanceOperationRun
    {
        $run->update([
            'operation_status_code' => 'SUCCESS',
            'result_json' => $result,
            'finished_at' => now(),
            'error_message' => null,
        ]);

        return $run->fresh();
    }

    public function fail(AttendanceOperationRun $run, Throwable $exception, array $result = []): AttendanceOperationRun
    {
        $run->update([
            'operation_status_code' => 'FAILED',
            'result_json' => $result,
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);

        return $run->fresh();
    }
}