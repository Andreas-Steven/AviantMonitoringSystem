<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceLogRaw;
use App\Domains\Attendance\Models\AttendanceNormalizedLog;
use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Review\Models\AttendanceReviewCase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Attendance\Services\AttendanceDailyBuilderService;
use App\Domains\Attendance\Services\AttendanceNormalizationService;
use App\Domains\Master\Models\Branch;
use App\Domains\Scheduling\Models\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use App\Domains\Attendance\Models\AttendanceOperationRun;
use App\Domains\Attendance\Services\AttendanceOperationRunLogger;
use Illuminate\Support\Facades\DB;
use Throwable;

class AttendanceOperationController extends Controller
{
    public function index(Request $request): View
    {
        [$selectedPeriod, $dateFrom, $dateTo] = $this->resolveScopeForDashboard($request);

        $stats = $this->buildPipelineStats($dateFrom, $dateTo);

        return view('attendance.operations.index', [
            'payrollPeriods' => PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->get(),
            'branches' => $this->availableBranches(),
            'operationRuns' => $this->decorateOperationRuns(
                AttendanceOperationRun::query()
                    ->with(['payrollPeriod', 'triggeredByUser'])
                    ->orderByDesc('started_at')
                    ->limit(15)
                    ->get()
            ),
            'selectedPayrollPeriodId' => $selectedPeriod?->payroll_period_id,
            'selectedDateFrom' => $dateFrom,
            'selectedDateTo' => $dateTo,
            'pipelineStats' => $stats,
        ]);
    }

    public function normalize(
        Request $request,
        AttendanceNormalizationService $normalizationService,
        AttendanceOperationRunLogger $operationRunLogger
    ): RedirectResponse {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $payrollPeriodId = $this->resolvePayrollPeriodId($request);

        $run = $operationRunLogger->start(
            'NORMALIZE',
            $dateFrom,
            $dateTo,
            $payrollPeriodId,
            $this->currentUserId()
        );

        try {
            $result = $normalizationService->normalize($dateFrom, $dateTo);

            $operationRunLogger->succeed($run, $result);

            return redirect()
                ->route('attendance.operations.index')
                ->with('success', sprintf(
                    'Normalize selesai. Range: %s s/d %s. Groups: %d, Processed: %d, Inserted: %d.',
                    $dateFrom,
                    $dateTo,
                    (int) ($result['group_count'] ?? 0),
                    (int) ($result['processed_count'] ?? 0),
                    (int) ($result['inserted_count'] ?? 0),
                ));
        } catch (Throwable $exception) {
            $operationRunLogger->fail($run, $exception);

            return redirect()
                ->route('attendance.operations.index')
                ->with('error', 'Normalize gagal: ' . $exception->getMessage());
        }
    }

    public function buildDaily(
        Request $request,
        AttendanceDailyBuilderService $dailyBuilderService,
        AttendanceOperationRunLogger $operationRunLogger
    ): RedirectResponse {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $payrollPeriodId = $this->resolvePayrollPeriodId($request);

        $run = $operationRunLogger->start(
            'BUILD_DAILY',
            $dateFrom,
            $dateTo,
            $payrollPeriodId,
            $this->currentUserId()
        );

        try {
            $result = $dailyBuilderService->build($dateFrom, $dateTo);

            $operationRunLogger->succeed($run, $result);

            return redirect()
                ->route('attendance.operations.index')
                ->with('success', sprintf(
                    'Build daily selesai. Range: %s s/d %s. Employees: %d, Processed Days: %d, Upserted: %d, Skipped Days: %d.',
                    $dateFrom,
                    $dateTo,
                    (int) ($result['employee_count'] ?? 0),
                    (int) ($result['processed_days'] ?? 0),
                    (int) ($result['upserted_count'] ?? 0),
                    (int) ($result['skipped_days'] ?? 0),
                ));
        } catch (Throwable $exception) {
            $operationRunLogger->fail($run, $exception);

            return redirect()
                ->route('attendance.operations.index')
                ->with('error', 'Build daily gagal: ' . $exception->getMessage());
        }
    }

    public function runPipeline(
        Request $request,
        AttendanceNormalizationService $normalizationService,
        AttendanceDailyBuilderService $dailyBuilderService,
        AttendanceOperationRunLogger $operationRunLogger
    ): RedirectResponse {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $payrollPeriodId = $this->resolvePayrollPeriodId($request);

        $run = $operationRunLogger->start(
            'FULL_PIPELINE',
            $dateFrom,
            $dateTo,
            $payrollPeriodId,
            $this->currentUserId()
        );

        try {
            $normalizeResult = $normalizationService->normalize($dateFrom, $dateTo);
            $dailyResult = $dailyBuilderService->build($dateFrom, $dateTo);

            $result = [
                'normalize' => $normalizeResult,
                'build_daily' => $dailyResult,
            ];

            $operationRunLogger->succeed($run, $result);

            return redirect()
                ->route('attendance.operations.index')
                ->with('success', sprintf(
                    'Full pipeline selesai. Range: %s s/d %s. Normalize => Groups: %d, Processed: %d, Inserted: %d. Build Daily => Employees: %d, Processed Days: %d, Upserted: %d, Skipped Days: %d.',
                    $dateFrom,
                    $dateTo,
                    (int) ($normalizeResult['group_count'] ?? 0),
                    (int) ($normalizeResult['processed_count'] ?? 0),
                    (int) ($normalizeResult['inserted_count'] ?? 0),
                    (int) ($dailyResult['employee_count'] ?? 0),
                    (int) ($dailyResult['processed_days'] ?? 0),
                    (int) ($dailyResult['upserted_count'] ?? 0),
                    (int) ($dailyResult['skipped_days'] ?? 0),
                ));
        } catch (Throwable $exception) {
            $operationRunLogger->fail($run, $exception);

            return redirect()
                ->route('attendance.operations.index')
                ->with('error', 'Full pipeline gagal: ' . $exception->getMessage());
        }
    }

    protected function resolveDateRange(Request $request): array
    {
        $periodId = $request->input('payroll_period_id');

        if (!empty($periodId)) {
            $period = PayrollPeriod::query()->findOrFail((int) $periodId);

            return [
                $period->period_start_date->toDateString(),
                $period->period_end_date->toDateString(),
            ];
        }

        $validator = Validator::make(
            $request->all(),
            [
                'date_from' => ['required', 'date'],
                'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            ],
            [
                'date_from.required' => 'Date From wajib diisi jika payroll period belum dipilih.',
                'date_to.required' => 'Date To wajib diisi jika payroll period belum dipilih.',
                'date_to.after_or_equal' => 'Date To tidak boleh lebih kecil dari Date From.',
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return [
            (string) $request->input('date_from'),
            (string) $request->input('date_to'),
        ];
    }

    protected function availableBranches()
    {
        $user = auth()->user();

        if ($user && $user->hasRole('SUPER_ADMIN')) {
            return Branch::query()
                ->where('active', true)
                ->orderBy('branch_name')
                ->get();
        }

        return $user
            ? $user->branchAccesses()
                ->where('branches.active', true)
                ->orderBy('branch_name')
                ->get()
            : collect();
    }

    protected function currentUserId(): ?int
    {
        return auth()->user()?->user_id;
    }

    protected function resolvePayrollPeriodId(Request $request): ?int
    {
        $periodId = $request->input('payroll_period_id');

        return !empty($periodId) ? (int) $periodId : null;
    }

    protected function resolveScopeForDashboard(Request $request): array
    {
        $selectedPeriod = null;

        if ($request->filled('payroll_period_id')) {
            $selectedPeriod = PayrollPeriod::query()->find((int) $request->input('payroll_period_id'));
        }

        if (!$selectedPeriod) {
            $selectedPeriod = PayrollPeriod::query()
                ->orderByDesc('period_start_date')
                ->first();
        }

        if ($selectedPeriod) {
            $dateFrom = $request->filled('date_from')
                ? (string) $request->input('date_from')
                : $selectedPeriod->period_start_date->toDateString();

            $dateTo = $request->filled('date_to')
                ? (string) $request->input('date_to')
                : $selectedPeriod->period_end_date->toDateString();

            return [$selectedPeriod, $dateFrom, $dateTo];
        }

        $dateFrom = $request->filled('date_from') ? (string) $request->input('date_from') : now()->toDateString();
        $dateTo = $request->filled('date_to') ? (string) $request->input('date_to') : now()->toDateString();

        return [null, $dateFrom, $dateTo];
    }

    protected function buildPipelineStats(string $dateFrom, string $dateTo): array
    {
        $user = auth()->user();

        $rawQuery = AttendanceLogRaw::query()
            ->whereBetween('log_date', [$dateFrom, $dateTo]);

        $normalizedQuery = AttendanceNormalizedLog::query()
            ->whereBetween(DB::raw('DATE(log_datetime)'), [$dateFrom, $dateTo]);

        $dailyQuery = AttendanceDaily::query()
            ->whereBetween('work_date', [$dateFrom, $dateTo]);

        $reviewQuery = AttendanceReviewCase::query()
            ->whereBetween('work_date', [$dateFrom, $dateTo]);

        if ($user && !$user->hasRole('SUPER_ADMIN')) {
            $allowedBranchIds = $user->branchAccesses()->pluck('branches.branch_id')->all();

            if (!empty($allowedBranchIds)) {
                $rawQuery->whereIn('location_branch_id', $allowedBranchIds);

                $normalizedQuery->whereHas('rawLog', function (Builder $query) use ($allowedBranchIds): void {
                    $query->whereIn('location_branch_id', $allowedBranchIds);
                });

                $dailyQuery->whereIn('branch_id', $allowedBranchIds);

                $reviewQuery->whereHas('employee.assignments', function (Builder $query) use ($allowedBranchIds, $dateFrom, $dateTo): void {
                    $query->whereIn('branch_id', $allowedBranchIds)
                        ->whereDate('effective_start_date', '<=', $dateTo)
                        ->where(function (Builder $subQuery) use ($dateFrom): void {
                            $subQuery->whereNull('effective_end_date')
                                ->orWhereDate('effective_end_date', '>=', $dateFrom);
                        });
                });
            } else {
                $rawQuery->whereRaw('1 = 0');
                $normalizedQuery->whereRaw('1 = 0');
                $dailyQuery->whereRaw('1 = 0');
                $reviewQuery->whereRaw('1 = 0');
            }
        }

        $rawCount = (clone $rawQuery)->count();
        $normalizedCount = (clone $normalizedQuery)->count();
        $dailyCount = (clone $dailyQuery)->count();
        $reviewCount = (clone $reviewQuery)->count();

        $pendingNormalizeCount = (clone $rawQuery)
            ->whereDoesntHave('normalizedLog')
            ->count();

        $pendingReviewCount = (clone $reviewQuery)
            ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
            ->count();

        $lastNormalizedAt = (clone $normalizedQuery)->max('processed_at');
        $lastCalculatedAt = (clone $dailyQuery)->max('calculated_at');

        return [
            'raw_count' => $rawCount,
            'normalized_count' => $normalizedCount,
            'daily_count' => $dailyCount,
            'review_count' => $reviewCount,
            'pending_normalize_count' => $pendingNormalizeCount,
            'pending_review_count' => $pendingReviewCount,
            'last_normalized_at' => $lastNormalizedAt ? Carbon::parse($lastNormalizedAt) : null,
            'last_calculated_at' => $lastCalculatedAt ? Carbon::parse($lastCalculatedAt) : null,
        ];
    } 

    protected function decorateOperationRuns($runs)
    {
        return $runs->map(function ($run) {
            $run->display_status_tone = $this->mapOperationStatusTone($run->operation_status_code);
            $run->display_duration = $this->formatRunDuration($run->started_at, $run->finished_at);
            $run->display_scope = sprintf(
                '%s → %s',
                optional($run->date_from)->format('Y-m-d') ?: '-',
                optional($run->date_to)->format('Y-m-d') ?: '-'
            );
            $run->display_summary_lines = $this->buildOperationRunSummaryLines($run);

            return $run;
        });
    }

    protected function mapOperationStatusTone(?string $statusCode): string
    {
        return match ($statusCode) {
            'SUCCESS', 'SUCCEEDED', 'COMPLETED' => 'success',
            'FAILED', 'ERROR' => 'danger',
            'RUNNING', 'PROCESSING' => 'info',
            'PENDING', 'QUEUED' => 'warning',
            default => 'neutral',
        };
    }

    protected function formatRunDuration($startedAt, $finishedAt): string
    {
        if (!$startedAt) {
            return '-';
        }

        if (!$finishedAt) {
            return 'In progress';
        }

        $seconds = $finishedAt->diffInSeconds($startedAt);

        if ($seconds < 60) {
            return $seconds . ' sec';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? sprintf('%d min %d sec', $minutes, $remainingSeconds)
                : sprintf('%d min', $minutes);
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? sprintf('%d hr %d min', $hours, $remainingMinutes)
            : sprintf('%d hr', $hours);
    }

    protected function buildOperationRunSummaryLines(AttendanceOperationRun $run): array
    {
        $result = is_array($run->result_json) ? $run->result_json : [];

        return match ($run->operation_type_code) {
            'NORMALIZE' => $this->buildNormalizeSummaryLines($result),
            'BUILD_DAILY' => $this->buildBuildDailySummaryLines($result),
            'FULL_PIPELINE' => $this->buildFullPipelineSummaryLines($result),
            default => $this->buildGenericSummaryLines($result),
        };
    }

    protected function buildNormalizeSummaryLines(array $result): array
    {
        return array_filter([
            'Groups: ' . number_format((int) Arr::get($result, 'group_count', 0)),
            'Processed: ' . number_format((int) Arr::get($result, 'processed_count', 0)),
            'Inserted: ' . number_format((int) Arr::get($result, 'inserted_count', 0)),
        ]);
    }

    protected function buildBuildDailySummaryLines(array $result): array
    {
        return array_filter([
            'Employees: ' . number_format((int) Arr::get($result, 'employee_count', 0)),
            'Processed Days: ' . number_format((int) Arr::get($result, 'processed_days', 0)),
            'Upserted: ' . number_format((int) Arr::get($result, 'upserted_count', 0)),
            'Skipped Days: ' . number_format((int) Arr::get($result, 'skipped_days', 0)),
        ]);
    }

    protected function buildFullPipelineSummaryLines(array $result): array
    {
        $normalize = Arr::get($result, 'normalize', []);
        $buildDaily = Arr::get($result, 'build_daily', []);

        return array_filter([
            'Normalize → Groups: ' . number_format((int) Arr::get($normalize, 'group_count', 0)),
            'Normalize → Processed: ' . number_format((int) Arr::get($normalize, 'processed_count', 0)),
            'Normalize → Inserted: ' . number_format((int) Arr::get($normalize, 'inserted_count', 0)),
            'Build Daily → Employees: ' . number_format((int) Arr::get($buildDaily, 'employee_count', 0)),
            'Build Daily → Processed Days: ' . number_format((int) Arr::get($buildDaily, 'processed_days', 0)),
            'Build Daily → Upserted: ' . number_format((int) Arr::get($buildDaily, 'upserted_count', 0)),
            'Build Daily → Skipped Days: ' . number_format((int) Arr::get($buildDaily, 'skipped_days', 0)),
        ]);
    }

    protected function buildGenericSummaryLines(array $result): array
    {
        if (empty($result)) {
            return ['No structured result'];
        }

        return collect($result)
            ->take(6)
            ->map(function ($value, $key) {
                if (is_array($value)) {
                    return sprintf('%s: [complex]', (string) $key);
                }

                return sprintf('%s: %s', (string) $key, (string) $value);
            })
            ->values()
            ->all();
    }    
}