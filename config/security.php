<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IP Whitelist Configuration
    |--------------------------------------------------------------------------
    |
    | Configure IP whitelisting for admin panel access.
    | When enabled, only IPs in the whitelist can access admin routes.
    |
    | Supports:
    | - Single IPs: 192.168.1.100
    | - CIDR notation: 192.168.1.0/24, 10.0.0.0/8
    | - Localhost: 127.0.0.1, ::1
    |
    */

    'ip_whitelist' => [
        'enabled' => env('ADMIN_IP_WHITELIST_ENABLED', false),
        
        'allowed_ips' => array_filter(
            array_map(
                'trim',
                explode(',', env('ADMIN_IP_WHITELIST', '127.0.0.1,::1'))
            )
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Fine-tuned rate limits for different endpoints.
    |
    */

    'rate_limits' => [
        // Login attempts
        'login' => [
            'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 5),
        ],
        
        // API requests (per minute)
        'api' => [
            'general' => (int) env('API_RATE_LIMIT_GENERAL', 60),
            'write' => (int) env('API_RATE_LIMIT_WRITE', 30),
            'sensitive' => (int) env('API_RATE_LIMIT_SENSITIVE', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy
    |--------------------------------------------------------------------------
    |
    | Password requirements for users and students.
    |
    */

    'password' => [
        'min_length' => (int) env('PASSWORD_MIN_LENGTH', 8),
        'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
        'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
        'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
        'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', false),
        'bcrypt_rounds' => (int) env('BCRYPT_ROUNDS', 12),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Additional session security settings.
    |
    */

    'session' => [
        // Regenerate session ID after login
        'regenerate_on_login' => true,
        
        // Session activity timeout (minutes of inactivity)
        'activity_timeout' => (int) env('SESSION_ACTIVITY_TIMEOUT', 30),
        
        // Force logout after N hours regardless of activity
        'absolute_timeout' => (int) env('SESSION_ABSOLUTE_TIMEOUT', 8),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security
    |--------------------------------------------------------------------------
    |
    | Allowed file types and size limits for uploads.
    |
    */

    'uploads' => [
        'max_file_size' => (int) env('MAX_UPLOAD_SIZE', 10240), // KB
        
        'allowed_mimes' => [
            'images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'documents' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
            'media' => ['mp3', 'mp4', 'webm', 'ogg'],
        ],
        
        // Scan uploads for malware (requires ClamAV)
        'scan_enabled' => env('UPLOAD_SCAN_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security
    |--------------------------------------------------------------------------
    |
    | Content validation and sanitization settings.
    |
    */

    'content' => [
        // Allow raw code in questions (for programming/Informatics)
        'allow_code_questions' => true,
        
        // Maximum question content length (chars)
        'max_question_length' => (int) env('MAX_QUESTION_LENGTH', 50000),
        
        // Enable input logging for audit
        'log_suspicious_input' => env('LOG_SUSPICIOUS_INPUT', true),
    ],
];
