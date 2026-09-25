<?php

namespace App\Http\Controllers\Web\Audit;

use App\Http\Controllers\Controller;
use App\Domains\Audit\Models\AppLoginAudit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppLoginAuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AppLoginAudit::query()
            ->with(['user'])
            ->orderByDesc('logged_at')
            ->orderByDesc('login_audit_id');

        if ($request->filled('login_status')) {
            $query->where('login_status', $request->string('login_status')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('logged_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('logged_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));

            $query->where(function ($q) use ($keyword): void {
                $q->where('email', 'ilike', "%{$keyword}%")
                    ->orWhere('google_sub', 'ilike', "%{$keyword}%")
                    ->orWhere('ip_address', '::text ilike', "%{$keyword}%")
                    ->orWhere('notes', 'ilike', "%{$keyword}%");
            });
        }

        $rows = $query->paginate(20)->withQueryString();

        $loginStatuses = AppLoginAudit::query()
            ->select('login_status')
            ->distinct()
            ->orderBy('login_status')
            ->pluck('login_status');

        return view('audit.login-audits.index', [
            'rows' => $rows,
            'loginStatuses' => $loginStatuses,
        ]);
    }

    public function show(int $loginAudit): View
    {
        $row = AppLoginAudit::query()
            ->with(['user'])
            ->findOrFail($loginAudit);

        return view('audit.login-audits.show', compact('row'));
    }
}