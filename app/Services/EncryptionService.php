<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class EncryptionService
{
    /**
     * Encrypt sensitive data
     */
    public static function encrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Decrypt sensitive data
     */
    public static function decrypt(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }
}
