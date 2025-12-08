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
        $response->headers->set('Content-Security-Policy', $this->getCSP());

        // Strict Transport Security (HTTPS only)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Additional security headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        return $response;
    }

    protected function getCSP(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://*.cloudflare.com https://static.cloudflareinsights.com https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            "img-src 'self' data: blob: https: http:",
            "connect-src 'self' https://challenges.cloudflare.com https://*.cloudflare.com wss: ws:",
            "frame-src 'self' https://challenges.cloudflare.com https://*.cloudflare.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "media-src 'self' blob:",
        ]);
    }
}
