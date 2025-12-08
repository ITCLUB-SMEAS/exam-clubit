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
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = false
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < $this->minLength) {
            $fail("Password minimal {$this->minLength} karakter.");
        }

        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf besar.');
        }

        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            $fail('Password harus mengandung minimal 1 huruf kecil.');
        }

        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 angka.');
        }

        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('Password harus mengandung minimal 1 karakter spesial (!@#$%^&*).');
        }
    }
}
