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
        'correct_answer',
        'answer',
        'short_answer',
        'left_option',
        'right_option',
    ];

    /**
     * Fields that should be excluded from sanitization.
     * Passwords, tokens, questions and answer options should not be modified.
     */
    protected array $excludedFields = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        '_token',
        '_method',
        'question',
        'option_1',
        'option_2',
        'option_3',
        'option_4',
        'option_5',
        'answer',
        'correct_answer',
        'short_answer',
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
            
            // Determine sanitization mode based on route
            $sanitizationMode = $this->getSanitizationMode($request);
            
            $sanitized = $this->sanitizeInput($input, '', $sanitizationMode);
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Determine the sanitization mode for this request.
     * 
     * @return string 'normal', 'code', or 'rich'
     */
    protected function getSanitizationMode(Request $request): string
    {
        // Routes that need code-friendly sanitization (Informatics questions)
        $codePatterns = [
            'admin/exams/*/questions*',
            'admin/question-bank*',
            'admin/ai/*',
            'student/exam-answer',
            'admin/essay-grading*',
        ];

        foreach ($codePatterns as $pattern) {
            if ($request->is($pattern)) {
                return 'code';
            }
        }

        return 'normal';
    }

    /**
     * Recursively sanitize input data.
     *
     * @param array $input
     * @param string $prefix
     * @param string $mode 'normal' or 'code'
     * @return array
     */
    protected function sanitizeInput(array $input, string $prefix = '', string $mode = 'normal'): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip excluded fields (passwords, tokens)
            if (in_array($key, $this->excludedFields)) {
                // For code mode, still sanitize question/answer fields but with code-safe method
                if ($mode === 'code' && in_array($key, $this->richTextFields)) {
                    $sanitized[$key] = is_string($value) 
                        ? SanitizationService::cleanCodeQuestion($value) 
                        : $value;
                } else {
                    $sanitized[$key] = $value;
                }
                continue;
            }

            // Skip file uploads
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                // Recursively sanitize arrays
                $sanitized[$key] = $this->sanitizeInput($value, $fullKey, $mode);
            } elseif (is_string($value)) {
                // Check if field should allow rich text
                if ($this->isRichTextField($key)) {
                    // Use code-safe sanitization for code mode
                    $sanitized[$key] = $mode === 'code'
                        ? SanitizationService::cleanCodeQuestion($value)
                        : SanitizationService::cleanRichText($value);
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
