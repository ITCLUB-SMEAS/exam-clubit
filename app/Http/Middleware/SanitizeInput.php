<?php

namespace App\Http\Middleware;

use App\Services\SanitizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should allow rich text (HTML).
     * These fields are typically used for formatted content like questions and descriptions.
     */
    protected array $richTextFields = [
        'question',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'option_5',
        'description',
        'content',
    ];

    /**
     * Fields that should be excluded from sanitization.
     * Passwords and tokens should not be modified.
     */
    protected array $excludedFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        '_token',
        '_method',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only sanitize for POST, PUT, PATCH requests
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $input = $request->all();
            $sanitized = $this->sanitizeInput($input);
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Recursively sanitize input data.
     *
     * @param array $input
     * @param string $prefix
     * @return array
     */
    protected function sanitizeInput(array $input, string $prefix = ''): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip excluded fields
            if (in_array($key, $this->excludedFields)) {
                $sanitized[$key] = $value;
                continue;
            }

            // Skip file uploads
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                // Recursively sanitize arrays
                $sanitized[$key] = $this->sanitizeInput($value, $fullKey);
            } elseif (is_string($value)) {
                // Check if field should allow rich text
                if ($this->isRichTextField($key)) {
                    $sanitized[$key] = SanitizationService::cleanRichText($value);
                } else {
                    $sanitized[$key] = SanitizationService::clean($value);
                }
            } else {
                // Non-string values pass through unchanged
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if a field should allow rich text.
     *
     * @param string $key
     * @return bool
     */
    protected function isRichTextField(string $key): bool
    {
        return in_array($key, $this->richTextFields);
    }
}
