<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Security Headers Middleware
 *
 * Adds security headers and sanitizes API responses
 */
class ApiSecurityHeaders
{
    /**
     * Sensitive fields that should be removed from responses
     */
    protected array $sensitiveFields = [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'session_id',
        'api_token',
    ];

    /**
     * Fields that should be masked (show partial)
     */
    protected array $maskedFields = [
        'nisn' => 4, // Show last 4 digits
        'email' => 3, // Show first 3 chars
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-API-Version', '1.0');

        // Prevent caching of sensitive data
        if ($this->isSensitiveEndpoint($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
        }

        // Add deprecation header for legacy routes
        if ($this->isDeprecatedEndpoint($request)) {
            $response->headers->set('Deprecation', 'true');
            $response->headers->set('Sunset', 'Sat, 01 Jan 2028 00:00:00 GMT');
        }

        // Sanitize JSON responses
        if ($this->isJsonResponse($response)) {
            $this->sanitizeResponse($response);
        }

        return $response;
    }

    /**
     * Check if this is a sensitive endpoint
     */
    protected function isSensitiveEndpoint(Request $request): bool
    {
        $sensitivePatterns = [
            'api/*/login',
            'api/*/me',
            'api/*/students*',
            'api/*/grades*',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if endpoint is deprecated
     */
    protected function isDeprecatedEndpoint(Request $request): bool
    {
        // Legacy endpoints without /v1/ prefix
        return $request->is('api/*') && ! $request->is('api/v1/*');
    }

    /**
     * Check if response is JSON
     */
    protected function isJsonResponse(Response $response): bool
    {
        return str_contains($response->headers->get('Content-Type', ''), 'application/json');
    }

    /**
     * Sanitize sensitive data from response
     */
    protected function sanitizeResponse(Response $response): void
    {
        $content = $response->getContent();

        if (empty($content)) {
            return;
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return;
        }

        $sanitized = $this->sanitizeArray($data);
        $response->setContent(json_encode($sanitized));
    }

    /**
     * Recursively sanitize an array
     */
    protected function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            // Remove sensitive fields entirely
            if (in_array($key, $this->sensitiveFields, true)) {
                unset($data[$key]);

                continue;
            }

            // Mask certain fields
            if (array_key_exists($key, $this->maskedFields) && is_string($value)) {
                $data[$key] = $this->maskValue($value, $this->maskedFields[$key], $key);

                continue;
            }

            // Recurse into nested arrays
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value);
            }
        }

        return $data;
    }

    /**
     * Mask a value showing only specified chars
     */
    protected function maskValue(string $value, int $visibleChars, string $fieldType): string
    {
        if (strlen($value) <= $visibleChars) {
            return str_repeat('*', strlen($value));
        }

        if ($fieldType === 'email') {
            // Show first N chars + domain
            $parts = explode('@', $value);
            if (count($parts) === 2) {
                $localPart = $parts[0];
                $domain = $parts[1];
                $visible = substr($localPart, 0, $visibleChars);
                $masked = str_repeat('*', max(strlen($localPart) - $visibleChars, 3));

                return $visible.$masked.'@'.$domain;
            }
        }

        // Default: show last N chars (for IDs like NISN)
        $masked = str_repeat('*', strlen($value) - $visibleChars);
        $visible = substr($value, -$visibleChars);

        return $masked.$visible;
    }
}
