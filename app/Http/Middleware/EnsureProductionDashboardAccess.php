<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Shared\Enums\HttpStatusCode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductionDashboardAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local') && config('app.local_dashboard_bypass', false)) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $request->expectsJson() || $request->is('api/*')
                ? ApiResponse::failure('Unauthenticated.', HttpStatusCode::Unauthorized->value)
                : redirect()->guest(route('login'));
        }

        if (! $user->is_active) {
            auth()->logout();

            return $request->expectsJson() || $request->is('api/*')
                ? ApiResponse::failure('User is inactive.', HttpStatusCode::Forbidden->value)
                : redirect()->route('login')->withErrors(['email' => 'User tidak aktif.']);
        }

        return $next($request);
    }
}
