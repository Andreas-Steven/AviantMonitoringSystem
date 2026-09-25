<?php

namespace App\Http\Controllers\Web\Scheduling;

use App\Domains\Dashboard\Services\OperationalStatusService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SchedulingWorkspaceController extends Controller
{
    public function index(OperationalStatusService $service): View
    {
        $coverage = $service->schedulingCoverage();
        $branch_setup = $service->branchSetup();

        return view('scheduling.workspace.index', compact(
            'coverage',
            'branch_setup'
        ));
    }
}