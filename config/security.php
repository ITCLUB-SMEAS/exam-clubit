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

        // ZIP extraction limits (for import features)
        'zip' => [
            'max_files' => (int) env('ZIP_MAX_FILES', 1000),
            'max_extracted_size' => (int) env('ZIP_MAX_EXTRACTED_SIZE', 524288000), // 500MB
            'max_compression_ratio' => (float) env('ZIP_MAX_COMPRESSION_RATIO', 100.0),
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Anti-Cheat Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the anti-cheat system.
    |
    */

    'anticheat' => [
        // Number of violations before auto-blocking student
        // Set to 0 to disable auto-blocking
        'auto_block_threshold' => (int) env('ANTICHEAT_AUTO_BLOCK_THRESHOLD', 3),

        // Enable auto-blocking feature
        'auto_block_enabled' => env('ANTICHEAT_AUTO_BLOCK_ENABLED', true),

        // Violation types that count towards auto-block
        'critical_violations' => [
            'multiple_sessions',
            'suspicious_user_agent',
            'time_manipulation',
            'multiple_faces',
            'devtools',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for the security audit logging system.
    |
    */

    'audit' => [
        // Enable database logging (for querying and reporting)
        'log_to_database' => env('SECURITY_AUDIT_DB_ENABLED', true),

        // Enable file logging (for backup and external SIEM integration)
        'log_to_file' => env('SECURITY_AUDIT_FILE_ENABLED', true),

        // Days to keep audit logs before cleanup
        'retention_days' => (int) env('SECURITY_AUDIT_RETENTION_DAYS', 90),

        // Events to always log (regardless of severity)
        'always_log' => [
            'auth.failure',
            'auth.2fa.failure',
            'permission.denied',
            'rate_limit.exceeded',
            'anticheat.block',
            'security.zip_bomb',
            'security.csrf_failure',
        ],

        // Events to skip logging (for performance in high-traffic scenarios)
        'skip_events' => [],
    ],
];
