<?php

use App\Http\Middleware\EnsureProductionDashboardAccess;
use App\Http\Middleware\EnsureUserHasBranchAccess;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\LocalApiCsrfToken;
use App\Http\Middleware\RedirectLocalLoginToDashboard;
use App\Http\Responses\ApiResponse;
use App\Shared\Enums\HttpStatusCode;
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
            'user.active' => EnsureUserIsActive::class,
            'permission' => EnsureUserHasPermission::class,
            'branch.access' => EnsureUserHasBranchAccess::class,
            'production.dashboard.access' => EnsureProductionDashboardAccess::class,
            'local.dashboard.redirect' => RedirectLocalLoginToDashboard::class,
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
                    'code' => HttpStatusCode::UnprocessableEntity->value,
                    'success' => false,
                    'message' => 'Validation failed. Please review the provided data',
                    'errors' => $errors,
                ], HttpStatusCode::UnprocessableEntity->value);
            }

            $code = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : HttpStatusCode::InternalServerError->value;
            $message = match ($code) {
                HttpStatusCode::Unauthorized->value => 'Unauthenticated.',
                HttpStatusCode::Forbidden->value => 'Forbidden.',
                HttpStatusCode::NotFound->value => 'Not found.',
                HttpStatusCode::MethodNotAllowed->value => 'Method not allowed.',
                default => $code >= 500 ? 'Internal server error.' : $exception->getMessage(),
            };

            return ApiResponse::failure($message, $code);
        });
    })->create();
