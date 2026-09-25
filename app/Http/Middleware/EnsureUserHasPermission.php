<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permissionCode): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permissionCode)) {
            abort(403, 'Anda tidak memiliki permission untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}