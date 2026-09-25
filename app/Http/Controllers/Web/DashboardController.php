<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceDaily;
use App\Domains\Master\Models\Branch;
use App\Domains\Master\Models\Employee;
use App\Domains\Requests\Models\EmployeeLeaveRequest;
use App\Domains\Requests\Models\OvertimeRequest;
use App\Domains\Review\Models\AttendanceReviewCase;
use App\Domains\Scheduling\Models\PayrollPeriod;
use App\Domains\Scheduling\Models\Shift;
use App\Domains\Dashboard\Services\OperationalStatusService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(OperationalStatusService $statusService): View
    {
        $activePayrollPeriod = $statusService->activePayrollPeriod();
        $today = now()->toDateString();
        $todayOverview = $statusService->attendanceTodayOverview();

        $stats = [
            'active_branches' => Branch::query()->where('active', true)->count(),
            'active_employees' => Employee::query()->where('active', true)->count(),
            'active_shifts' => Shift::query()->where('active', true)->count(),
            'active_payroll_period' => $activePayrollPeriod,

            'pending_leave_requests' => EmployeeLeaveRequest::query()
                ->where('request_status_code', 'PENDING')
                ->count(),

            'pending_overtime_requests' => OvertimeRequest::query()
                ->where('request_status_code', 'PENDING')
                ->count(),

            'open_review_cases' => AttendanceReviewCase::query()
                ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
                ->count(),

            'today_anomalies' => $todayOverview['anomaly_count'] ?? 0,
        ];

        $pendingLeaveRequests = EmployeeLeaveRequest::query()
            ->with(['employee', 'leaveType'])
            ->where('request_status_code', 'PENDING')
            ->orderBy('start_date')
            ->orderByDesc('leave_request_id')
            ->limit(5)
            ->get();

        $pendingOvertimeRequests = OvertimeRequest::query()
            ->with(['employee'])
            ->where('request_status_code', 'PENDING')
            ->orderByDesc('work_date')
            ->orderByDesc('overtime_request_id')
            ->limit(5)
            ->get();

        $openReviewCases = AttendanceReviewCase::query()
            ->with(['employee'])
            ->whereIn('review_status_code', ['OPEN', 'IN_REVIEW'])
            ->orderByDesc('detected_at')
            ->orderByDesc('review_case_id')
            ->limit(5)
            ->get();

        $todayAnomalies = AttendanceDaily::query()
            ->with(['employee', 'branch'])
            ->whereDate('work_date', $today)
            ->where(function ($query): void {
                $query->where('anomaly_flag', true)
                    ->orWhere('attendance_status_code', 'MANUAL_REVIEW')
                    ->orWhereNotNull('review_reason_code');
            })
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $smartSuggestions = [];

        if (($stats['today_anomalies'] ?? 0) > 0) {
            $smartSuggestions[] = [
                'type' => 'danger',
                'title' => 'Attendance needs review',
                'description' => $stats['today_anomalies'] . ' anomaly hari ini perlu diverifikasi.',
                'route' => route('attendance.daily.index', [
                    'anomaly' => 1,
                    'work_date' => $today,
                ]),
                'action' => 'Open Daily Verification',
            ];
        }

        if (($stats['open_review_cases'] ?? 0) > 0) {
            $smartSuggestions[] = [
                'type' => 'warning',
                'title' => 'Open review cases',
                'description' => $stats['open_review_cases'] . ' case belum diselesaikan.',
                'route' => route('review.attendance-cases.index', [
                    'review_status_code' => 'OPEN',
                ]),
                'action' => 'Open Review Cases',
            ];
        }

        if (($stats['pending_leave_requests'] ?? 0) > 0) {
            $smartSuggestions[] = [
                'type' => 'warning',
                'title' => 'Pending leave requests',
                'description' => $stats['pending_leave_requests'] . ' request leave menunggu approval.',
                'route' => route('requests.leave-requests.index', [
                    'request_status_code' => 'PENDING',
                ]),
                'action' => 'Review Leave',
            ];
        }

        if (($stats['pending_overtime_requests'] ?? 0) > 0) {
            $smartSuggestions[] = [
                'type' => 'warning',
                'title' => 'Pending overtime requests',
                'description' => $stats['pending_overtime_requests'] . ' request overtime menunggu approval.',
                'route' => route('requests.overtime-requests.index', [
                    'request_status_code' => 'PENDING',
                ]),
                'action' => 'Review Overtime',
            ];
        }

        if (!$activePayrollPeriod) {
            $smartSuggestions[] = [
                'type' => 'info',
                'title' => 'No active payroll period',
                'description' => 'Belum ada payroll period aktif untuk proses absensi/payroll.',
                'route' => route('scheduling.payroll-periods.index'),
                'action' => 'Setup Payroll Period',
            ];
        } elseif (($stats['today_anomalies'] ?? 0) > 0) {
            $smartSuggestions[] = [
                'type' => 'info',
                'title' => 'Payroll not ready',
                'description' => 'Masih ada attendance anomaly. Payroll berisiko salah jika diproses sekarang.',
                'route' => route('summary.workspace'),
                'action' => 'Open Payroll Workspace',
            ];
        }

        return view('dashboard.index', compact(
            'stats',
            'pendingLeaveRequests',
            'pendingOvertimeRequests',
            'openReviewCases',
            'todayAnomalies',
            'smartSuggestions'
        ));
    }
}