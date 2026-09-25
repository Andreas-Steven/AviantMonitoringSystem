<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasBranchAccess
{
    public function handle(Request $request, Closure $next, string $routeParam = 'branch'): Response
    {
        $user = $request->user();
        $branchId = (int) $request->route($routeParam);

        if ($branchId && ! $user->hasBranchAccess($branchId)) {
            abort(403, 'Anda tidak memiliki akses ke cabang ini.');
        }

        return $next($request);
    }
}