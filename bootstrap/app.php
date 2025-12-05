<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . "/../routes/web.php",
        api: __DIR__ . "/../routes/api.php",
        commands: __DIR__ . "/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware for all requests
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        $middleware->web(
            append: [
                \App\Http\Middleware\HandleInertiaRequests::class,
                \App\Http\Middleware\SanitizeInput::class,
            ],
        );
        
        // Exclude from CSRF
        $middleware->validateCsrfTokens(except: [
            'telegram/webhook',
            'student/logout',
        ]);
        
        $middleware->alias([
            "student" => \App\Http\Middleware\AuthStudent::class,
            "admin.only" => \App\Http\Middleware\AdminOnly::class,
            "admin.or.guru" => \App\Http\Middleware\AdminOrGuru::class,
            "ability" => \App\Http\Middleware\CheckApiAbility::class,
            "turnstile" => \App\Http\Middleware\ValidateTurnstile::class,
            "2fa" => \App\Http\Middleware\TwoFactorChallenge::class,
            "file.validate" => \App\Http\Middleware\ValidateFileUpload::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            $status = $e->getStatusCode();
            $errorPages = [401, 403, 404, 419, 429, 500, 501, 502, 503, 504];

            if (in_array($status, $errorPages) && !$request->expectsJson()) {
                return response()->view("errors.{$status}", [
                    'exception' => $e
                ], $status);
            }
        });
    })
    ->create();
