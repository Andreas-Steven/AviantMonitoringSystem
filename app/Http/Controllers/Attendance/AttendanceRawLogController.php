<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Http\Controllers\Controller;
use App\Domains\Attendance\Models\AttendanceLogRaw;
use Illuminate\Http\Request;

class AttendanceRawLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLogRaw::query()
            ->with(['employee', 'branch'])
            ->orderByDesc('log_datetime')
            ->orderByDesc('log_id');

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('device_user_id', 'ilike', "%{$keyword}%")
                    ->orWhere('device_id', 'ilike', "%{$keyword}%")
                    ->orWhereHas('employee', function ($sub) use ($keyword): void {
                        $sub->where('emp_code', 'ilike', "%{$keyword}%")
                            ->orWhere('full_name', 'ilike', "%{$keyword}%")
                            ->orWhere('biometric_code', 'ilike', "%{$keyword}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('attendance.raw-logs.index', compact('logs'));
    }
}