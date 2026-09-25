<?php

namespace App\Http\Controllers\Web\AdminTools;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminToolsController extends Controller
{
    /**
     * Scheduling Tools Landing Page
     */
    public function scheduling(): View
    {
        return view('admin-tools.scheduling');
    }

    /**
     * Attendance Tools Landing Page
     */
    public function attendance(): View
    {
        return view('admin-tools.attendance');
    }

    /**
     * Payroll Tools Landing Page
     */
    public function payroll(): View
    {
        return view('admin-tools.payroll');
    }
}