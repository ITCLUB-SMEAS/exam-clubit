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

        // Skip for binary responses (PDF, Excel, etc)
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/pdf') || 
            str_contains($contentType, 'spreadsheet') ||
            str_contains($contentType, 'octet-stream')) {
            return $response;
        }

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - allow camera & microphone for anti-cheat
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(self), geolocation=()');

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', $this->getCSP());

        // Strict Transport Security (HTTPS only)
        if ($request->secure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function getCSP(): string
    {
        $nonce = base64_encode(random_bytes(16));
        
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://*.cloudflare.com https://cdn.jsdelivr.net blob:",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://challenges.cloudflare.com https://*.cloudflare.com https://cdn.jsdelivr.net https://generativelanguage.googleapis.com wss: ws:",
            "frame-src 'self' https://challenges.cloudflare.com https://*.cloudflare.com",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "media-src 'self' blob: data:",
            "worker-src 'self' blob:",
            "manifest-src 'self'",
        ];

        // Only add upgrade-insecure-requests in production
        if (config('app.env') === 'production') {
            $directives[] = "upgrade-insecure-requests";
        }

        return implode('; ', $directives);
    }
}
