<?php

use App\Http\Middleware\LocalApiCsrfToken;
use App\Http\Responses\ApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // ->withMiddleware(function (Middleware $middleware): void {
    //     //
    // })
    ->withMiddleware(function ($middleware) {
        $middleware->replaceInGroup(
            'web',
            ValidateCsrfToken::class,
            LocalApiCsrfToken::class,
        );

        $middleware->alias([
            'user.active' => \App\Http\Middleware\EnsureUserIsActive::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'branch.access' => \App\Http\Middleware\EnsureUserHasBranchAccess::class,
            'production.dashboard.access' => \App\Http\Middleware\EnsureProductionDashboardAccess::class,
            'local.dashboard.redirect' => \App\Http\Middleware\RedirectLocalLoginToDashboard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                $errors = [];

                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $errors[] = [
                            'field' => $field,
                            'message' => $message,
                        ];
                    }
                }

                return response()->json([
                    'code' => 422,
                    'success' => false,
                    'message' => 'Validation failed. Please review the provided data',
                    'errors' => $errors,
                ], 422);
            }

            $code = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;
            $message = match ($code) {
                401 => 'Unauthenticated.',
                403 => 'Forbidden.',
                404 => 'Not found.',
                405 => 'Method not allowed.',
                default => $code >= 500 ? 'Internal server error.' : $exception->getMessage(),
            };

            return ApiResponse::failure($message, $code);
        });
    })->create();
