<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsPoliceManager;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.role' => EnsureUserIsAdmin::class,
            'police.manager' => EnsureUserIsPoliceManager::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status_code' => 422,
                    'message' => 'Validation failed',
                    'errors' => $exception->errors(),
                ], 422);
            }

            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'status_code' => 401,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $safeMessage = $statusCode >= 500 && ! config('app.debug')
                ? 'Server error.'
                : $exception->getMessage();

            return response()->json([
                'status_code' => $statusCode,
                'message' => $safeMessage,
            ], $statusCode);
        });
    })->create();
