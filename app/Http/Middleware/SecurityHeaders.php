<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - allow camera for face detection
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // Content Security Policy
        if (app()->environment('production')) {
            $response->headers->set('Content-Security-Policy', $this->getCSP());
        }

        // Strict Transport Security (HTTPS only)
        if ($request->secure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function getCSP(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tiny.cloud https://cdnjs.cloudflare.com https://challenges.cloudflare.com https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.tiny.cloud https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://cdn.tiny.cloud https://challenges.cloudflare.com",
            "frame-src 'self' https://challenges.cloudflare.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
        ]);
    }
}
