<?php

namespace App\Http\Controllers\Web\Summary;

use App\Domains\Dashboard\Services\OperationalStatusService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SummaryWorkspaceController extends Controller
{
    public function index(OperationalStatusService $statusService): View
    {
        $activePeriod = $statusService->activePayrollPeriod();

        $summaryOverview = $statusService->summaryOverview();

        $payrollReadiness = $statusService->payrollReadiness();

        return view('summary.workspace.index', compact(
            'activePeriod',
            'summaryOverview',
            'payrollReadiness'
        ));
    }
}