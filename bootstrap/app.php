<?php

// Load Docker secrets if running in container
$dockerSecretsFile = __DIR__.'/../docker/php/docker-secrets.php';
if (file_exists($dockerSecretsFile)) {
    require_once $dockerSecretsFile;
}

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware for all requests
        $middleware->append(\App\Http\Middleware\PreventDebugInProduction::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(
            append: [
                \App\Http\Middleware\SetLocale::class,
                \App\Http\Middleware\HandleInertiaRequests::class,
                \App\Http\Middleware\SanitizeInput::class,
            ],
        );

        // Exclude from CSRF (only webhook needs exclusion)
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
        ]);

        // API-specific middleware
        $middleware->api(
            append: [
                \App\Http\Middleware\ApiSecurityHeaders::class,
            ],
        );

        $middleware->alias([
            'student' => \App\Http\Middleware\AuthStudent::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'admin.or.guru' => \App\Http\Middleware\AdminOrGuru::class,
            'ability' => \App\Http\Middleware\CheckApiAbility::class,
            'turnstile' => \App\Http\Middleware\ValidateTurnstile::class,
            '2fa' => \App\Http\Middleware\TwoFactorChallenge::class,
            'file.validate' => \App\Http\Middleware\ValidateFileUpload::class,
            'anticheat.server' => \App\Http\Middleware\ServerSideAntiCheat::class,
            'ip.whitelist' => \App\Http\Middleware\IpWhitelist::class,
            'api.security' => \App\Http\Middleware\ApiSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle ModelNotFoundException (404)
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Data tidak ditemukan.'], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // Handle QueryException (database errors)
        $exceptions->render(function (QueryException $e, $request) {
            Log::error('Database error', [
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
            ]);

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Terjadi kesalahan database.'], 500);
            }

            return back()->withErrors(['error' => 'Terjadi kesalahan. Silakan coba lagi.'])->withInput();
        });

        // Handle HTTP exceptions
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            $status = $e->getStatusCode();
            $errorPages = [401, 403, 404, 419, 429, 500, 501, 502, 503, 504];

            if (in_array($status, $errorPages) && ! $request->expectsJson()) {
                return response()->view("errors.{$status}", [
                    'exception' => $e,
                ], $status);
            }
        });

        // Handle generic exceptions in production
        $exceptions->render(function (\Throwable $e, $request) {
            if (app()->environment('production') && ! $e instanceof HttpExceptionInterface) {
                Log::error('Unhandled exception', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Terjadi kesalahan. Silakan coba lagi.'], 500);
                }

                return back()->withErrors(['error' => 'Terjadi kesalahan. Silakan coba lagi.'])->withInput();
            }
        });
    })
    ->create();
