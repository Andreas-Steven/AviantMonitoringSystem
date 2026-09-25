<?php

namespace App\Http\Controllers\Web\Audit;

use App\Http\Controllers\Controller;
use App\Domains\Audit\Models\SystemChangeLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemChangeLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = SystemChangeLog::query()
            ->with(['changer'])
            ->orderByDesc('changed_at')
            ->orderByDesc('change_log_id');

        if ($request->filled('table_name')) {
            $query->where('table_name', $request->string('table_name')->toString());
        }

        if ($request->filled('action_type_code')) {
            $query->where('action_type_code', $request->string('action_type_code')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('changed_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('changed_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('changed_by')) {
            $keyword = trim((string) $request->input('changed_by'));

            $query->whereHas('changer', function ($q) use ($keyword): void {
                $q->where('emp_code', 'ilike', "%{$keyword}%")
                    ->orWhere('full_name', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('record_pk', 'ilike', "%{$keyword}%")
                    ->orWhere('notes', 'ilike', "%{$keyword}%")
                    ->orWhere('table_name', 'ilike', "%{$keyword}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

        $tableNames = SystemChangeLog::query()
            ->select('table_name')
            ->distinct()
            ->orderBy('table_name')
            ->pluck('table_name');

        $actionTypes = collect(['INSERT', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'CALCULATE']);

        return view('audit.system-change-logs.index', [
            'rows' => $rows,
            'tableNames' => $tableNames,
            'actionTypes' => $actionTypes,
        ]);
    }

    public function show(int $changeLog): View
    {
        $row = SystemChangeLog::query()
            ->with(['changer'])
            ->findOrFail($changeLog);

        return view('audit.system-change-logs.show', compact('row'));
    }
}