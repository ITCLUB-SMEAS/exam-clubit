<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    protected int $minLength;

    protected bool $requireUppercase;

    protected bool $requireLowercase;

    protected bool $requireNumbers;

    protected bool $requireSpecialChars;

    public function __construct(
        ?int $minLength = null,
        ?bool $requireUppercase = null,
        ?bool $requireLowercase = null,
        ?bool $requireNumbers = null,
        ?bool $requireSpecialChars = null
    ) {
        // Load from config with fallback to parameters or defaults
        $this->minLength = $minLength ?? (int) config('security.password.min_length', 8);
        $this->requireUppercase = $requireUppercase ?? (bool) config('security.password.require_uppercase', true);
        $this->requireLowercase = $requireLowercase ?? (bool) config('security.password.require_lowercase', true);
        $this->requireNumbers = $requireNumbers ?? (bool) config('security.password.require_numbers', true);
        $this->requireSpecialChars = $requireSpecialChars ?? (bool) config('security.password.require_symbols', false);
    }

    /**
     * Create an instance using config values (convenience method)
     */
    public static function fromConfig(): self
    {
        return new self;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < $this->minLength) {
            $fail("Password minimal {$this->minLength} karakter.");
        }

        if ($this->requireUppercase && ! preg_match('/[A-Z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf besar.');
        }

        if ($this->requireLowercase && ! preg_match('/[a-z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf kecil.');
        }

        if ($this->requireNumbers && ! preg_match('/[0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 angka.');
        }

        if ($this->requireSpecialChars && ! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 karakter spesial (!@#$%^&*).');
        }
    }
}
