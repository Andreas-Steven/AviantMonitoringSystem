<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLocalLoginToDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            app()->environment('local')
            && config('app.local_dashboard_bypass', false)
            && ! $request->session()->has('url.intended')
        ) {
            return redirect()->route('production.dashboard');
        }

        return $next($request);
    }
}
