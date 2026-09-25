<?php

namespace App\Http\Controllers\Web\Attendance;

use App\Domains\Dashboard\Services\OperationalStatusService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AttendanceWorkspaceController extends Controller
{
    public function index(OperationalStatusService $statusService): View
    {
        $todayOverview = $statusService->attendanceTodayOverview();

        return view('attendance.workspace.index', compact('todayOverview'));
    }
}