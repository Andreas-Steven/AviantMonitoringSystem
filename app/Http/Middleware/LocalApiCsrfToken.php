<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class LocalApiCsrfToken extends ValidateCsrfToken
{
    protected function inExceptArray($request)
    {
        return (
            app()->environment('local')
            && config('app.local_dashboard_bypass', false)
            && $request->is('api/*')
        ) || parent::inExceptArray($request);
    }
}
