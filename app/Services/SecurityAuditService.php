<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

/**
 * Security Audit Service
 *
 * Provides comprehensive security event logging for audit purposes.
 * Logs authentication events, authorization failures, suspicious activities,
 * and data access patterns.
 */
class SecurityAuditService
{
    /**
     * Event severity levels
     */
    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Event types
     */
    public const EVENT_AUTH_SUCCESS = 'auth.success';

    public const EVENT_AUTH_FAILURE = 'auth.failure';

    public const EVENT_AUTH_LOGOUT = 'auth.logout';

    public const EVENT_AUTH_2FA_SUCCESS = 'auth.2fa.success';

    public const EVENT_AUTH_2FA_FAILURE = 'auth.2fa.failure';

    public const EVENT_PERMISSION_DENIED = 'permission.denied';

    public const EVENT_RATE_LIMIT_EXCEEDED = 'rate_limit.exceeded';

    public const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious.activity';

    public const EVENT_DATA_EXPORT = 'data.export';

    public const EVENT_DATA_IMPORT = 'data.import';

    public const EVENT_ANTICHEAT_VIOLATION = 'anticheat.violation';

    public const EVENT_ANTICHEAT_BLOCK = 'anticheat.block';

    public const EVENT_API_ACCESS = 'api.access';

    public const EVENT_IP_BLOCKED = 'ip.blocked';

    public const EVENT_FILE_UPLOAD = 'file.upload';

    public const EVENT_ZIP_BOMB_DETECTED = 'security.zip_bomb';

    public const EVENT_CSRF_FAILURE = 'security.csrf_failure';

    public const EVENT_SESSION_HIJACK = 'security.session_hijack';

    public const EVENT_SQL_INJECTION_ATTEMPT = 'security.sql_injection';

    public const EVENT_XSS_ATTEMPT = 'security.xss_attempt';

    /**
     * Whether to log to database (can be disabled for performance)
     */
    protected bool $logToDatabase = true;

    /**
     * Whether to also log to file
     */
    protected bool $logToFile = true;

    public function __construct()
    {
        $this->logToDatabase = config('security.audit.log_to_database', true);
        $this->logToFile = config('security.audit.log_to_file', true);
    }

    /**
     * Log a security event
     */
    public function log(
        string $eventType,
        string $severity = self::SEVERITY_INFO,
        ?string $userId = null,
        ?string $userType = null,
        array $context = [],
        ?string $description = null
    ): void {
        $event = [
            'id' => (string) Str::uuid(),
            'event_type' => $eventType,
            'severity' => $severity,
            'user_id' => $userId,
            'user_type' => $userType,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'url' => $this->getCurrentUrl(),
            'method' => $this->getRequestMethod(),
            'context' => $context,
            'description' => $description,
            'session_id' => $this->getSessionId(),
            'created_at' => now(),
        ];

        if ($this->logToDatabase) {
            $this->logToDatabase($event);
        }

        if ($this->logToFile) {
            $this->logToFile($event);
        }
    }

    /**
     * Log authentication success
     */
    public function logAuthSuccess(string $userId, string $userType, array $context = []): void
    {
        $this->log(
            self::EVENT_AUTH_SUCCESS,
            self::SEVERITY_INFO,
            $userId,
            $userType,
            $context,
            "User {$userType}:{$userId} logged in successfully"
        );
    }

    /**
     * Log authentication failure
     */
    public function logAuthFailure(?string $identifier, string $userType, string $reason, array $context = []): void
    {
        $this->log(
            self::EVENT_AUTH_FAILURE,
            self::SEVERITY_WARNING,
            null,
            $userType,
            array_merge(['identifier' => $this->maskIdentifier($identifier), 'reason' => $reason], $context),
            "Failed login attempt for {$userType}: {$reason}"
        );
    }

    /**
     * Log logout event
     */
    public function logLogout(string $userId, string $userType): void
    {
        $this->log(
            self::EVENT_AUTH_LOGOUT,
            self::SEVERITY_INFO,
            $userId,
            $userType,
            [],
            "User {$userType}:{$userId} logged out"
        );
    }

    /**
     * Log 2FA success
     */
    public function log2FASuccess(string $userId, string $userType): void
    {
        $this->log(
            self::EVENT_AUTH_2FA_SUCCESS,
            self::SEVERITY_INFO,
            $userId,
            $userType,
            [],
            "2FA verification successful for {$userType}:{$userId}"
        );
    }

    /**
     * Log 2FA failure
     */
    public function log2FAFailure(string $userId, string $userType, int $attemptCount): void
    {
        $severity = $attemptCount >= 3 ? self::SEVERITY_CRITICAL : self::SEVERITY_WARNING;

        $this->log(
            self::EVENT_AUTH_2FA_FAILURE,
            $severity,
            $userId,
            $userType,
            ['attempt_count' => $attemptCount],
            "2FA verification failed for {$userType}:{$userId} (attempt {$attemptCount})"
        );
    }

    /**
     * Log permission denied
     */
    public function logPermissionDenied(
        ?string $userId,
        ?string $userType,
        string $resource,
        string $action
    ): void {
        $this->log(
            self::EVENT_PERMISSION_DENIED,
            self::SEVERITY_WARNING,
            $userId,
            $userType,
            ['resource' => $resource, 'action' => $action],
            "Permission denied: {$action} on {$resource}"
        );
    }

    /**
     * Log rate limit exceeded
     */
    public function logRateLimitExceeded(?string $userId, string $endpoint, int $limit): void
    {
        $this->log(
            self::EVENT_RATE_LIMIT_EXCEEDED,
            self::SEVERITY_WARNING,
            $userId,
            null,
            ['endpoint' => $endpoint, 'limit' => $limit],
            "Rate limit exceeded for endpoint: {$endpoint}"
        );
    }

    /**
     * Log suspicious activity
     */
    public function logSuspiciousActivity(
        ?string $userId,
        ?string $userType,
        string $activityType,
        array $details = []
    ): void {
        $this->log(
            self::EVENT_SUSPICIOUS_ACTIVITY,
            self::SEVERITY_CRITICAL,
            $userId,
            $userType,
            array_merge(['activity_type' => $activityType], $details),
            "Suspicious activity detected: {$activityType}"
        );
    }

    /**
     * Log anti-cheat violation
     */
    public function logAntiCheatViolation(
        string $studentId,
        string $examSessionId,
        string $violationType,
        array $details = []
    ): void {
        $this->log(
            self::EVENT_ANTICHEAT_VIOLATION,
            self::SEVERITY_WARNING,
            $studentId,
            'student',
            array_merge([
                'exam_session_id' => $examSessionId,
                'violation_type' => $violationType,
            ], $details),
            "Anti-cheat violation: {$violationType}"
        );
    }

    /**
     * Log anti-cheat auto-block
     */
    public function logAntiCheatBlock(
        string $studentId,
        string $examSessionId,
        int $violationCount
    ): void {
        $this->log(
            self::EVENT_ANTICHEAT_BLOCK,
            self::SEVERITY_CRITICAL,
            $studentId,
            'student',
            [
                'exam_session_id' => $examSessionId,
                'violation_count' => $violationCount,
            ],
            "Student auto-blocked due to {$violationCount} violations"
        );
    }

    /**
     * Log data export
     */
    public function logDataExport(
        string $userId,
        string $userType,
        string $exportType,
        int $recordCount
    ): void {
        $this->log(
            self::EVENT_DATA_EXPORT,
            self::SEVERITY_INFO,
            $userId,
            $userType,
            ['export_type' => $exportType, 'record_count' => $recordCount],
            "Data export: {$exportType} ({$recordCount} records)"
        );
    }

    /**
     * Log data import
     */
    public function logDataImport(
        string $userId,
        string $userType,
        string $importType,
        int $recordCount,
        bool $success
    ): void {
        $severity = $success ? self::SEVERITY_INFO : self::SEVERITY_WARNING;

        $this->log(
            self::EVENT_DATA_IMPORT,
            $severity,
            $userId,
            $userType,
            [
                'import_type' => $importType,
                'record_count' => $recordCount,
                'success' => $success,
            ],
            "Data import: {$importType} ({$recordCount} records) - ".($success ? 'success' : 'failed')
        );
    }

    /**
     * Log ZIP bomb detection
     */
    public function logZipBombDetected(
        ?string $userId,
        ?string $userType,
        string $filename,
        array $details = []
    ): void {
        $this->log(
            self::EVENT_ZIP_BOMB_DETECTED,
            self::SEVERITY_CRITICAL,
            $userId,
            $userType,
            array_merge(['filename' => $filename], $details),
            "ZIP bomb detected: {$filename}"
        );
    }

    /**
     * Log IP blocked
     */
    public function logIpBlocked(string $ip, string $reason): void
    {
        $this->log(
            self::EVENT_IP_BLOCKED,
            self::SEVERITY_WARNING,
            null,
            null,
            ['blocked_ip' => $ip, 'reason' => $reason],
            "IP blocked: {$ip} - {$reason}"
        );
    }

    /**
     * Log API access
     */
    public function logApiAccess(
        ?string $userId,
        string $endpoint,
        string $method,
        int $statusCode
    ): void {
        // Only log non-200 responses or sensitive endpoints
        if ($statusCode >= 400 || $this->isSensitiveEndpoint($endpoint)) {
            $severity = $statusCode >= 500 ? self::SEVERITY_CRITICAL
                : ($statusCode >= 400 ? self::SEVERITY_WARNING : self::SEVERITY_INFO);

            $this->log(
                self::EVENT_API_ACCESS,
                $severity,
                $userId,
                null,
                [
                    'endpoint' => $endpoint,
                    'method' => $method,
                    'status_code' => $statusCode,
                ],
                "API access: {$method} {$endpoint} - {$statusCode}"
            );
        }
    }

    /**
     * Log file upload
     */
    public function logFileUpload(
        string $userId,
        string $userType,
        string $filename,
        int $fileSize,
        bool $success
    ): void {
        $this->log(
            self::EVENT_FILE_UPLOAD,
            $success ? self::SEVERITY_INFO : self::SEVERITY_WARNING,
            $userId,
            $userType,
            [
                'filename' => $filename,
                'file_size' => $fileSize,
                'success' => $success,
            ],
            "File upload: {$filename} (".$this->formatBytes($fileSize).') - '.($success ? 'success' : 'failed')
        );
    }

    /**
     * Get security events for a user
     */
    public function getEventsForUser(string $userId, int $limit = 100): array
    {
        return DB::table('security_audit_logs')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get security events by type
     */
    public function getEventsByType(string $eventType, int $limit = 100): array
    {
        return DB::table('security_audit_logs')
            ->where('event_type', $eventType)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get critical events in the last N hours
     */
    public function getRecentCriticalEvents(int $hours = 24): array
    {
        return DB::table('security_audit_logs')
            ->where('severity', self::SEVERITY_CRITICAL)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get failed login attempts for an IP
     */
    public function getFailedLoginAttemptsForIp(string $ip, int $minutes = 60): int
    {
        return DB::table('security_audit_logs')
            ->where('event_type', self::EVENT_AUTH_FAILURE)
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        return DB::table('security_audit_logs')
            ->where('created_at', '<', now()->subDays($daysToKeep))
            ->delete();
    }

    /**
     * Log event to database
     */
    protected function logToDatabase(array $event): void
    {
        try {
            DB::table('security_audit_logs')->insert([
                'id' => $event['id'],
                'event_type' => $event['event_type'],
                'severity' => $event['severity'],
                'user_id' => $event['user_id'],
                'user_type' => $event['user_type'],
                'ip_address' => $event['ip_address'],
                'user_agent' => Str::limit($event['user_agent'], 500),
                'url' => Str::limit($event['url'], 2000),
                'method' => $event['method'],
                'context' => json_encode($event['context']),
                'description' => $event['description'],
                'session_id' => $event['session_id'],
                'created_at' => $event['created_at'],
            ]);
        } catch (\Exception $e) {
            // Fallback to file logging if database fails
            Log::error('Failed to log security event to database', [
                'error' => $e->getMessage(),
                'event' => $event,
            ]);
        }
    }

    /**
     * Log event to file
     */
    protected function logToFile(array $event): void
    {
        $logLevel = match ($event['severity']) {
            self::SEVERITY_CRITICAL => 'critical',
            self::SEVERITY_WARNING => 'warning',
            default => 'info',
        };

        Log::channel('security')->$logLevel(
            $event['description'] ?? $event['event_type'],
            [
                'event_type' => $event['event_type'],
                'user_id' => $event['user_id'],
                'user_type' => $event['user_type'],
                'ip_address' => $event['ip_address'],
                'context' => $event['context'],
            ]
        );
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(): ?string
    {
        return Request::ip();
    }

    /**
     * Get user agent
     */
    protected function getUserAgent(): ?string
    {
        return Request::userAgent();
    }

    /**
     * Get current URL
     */
    protected function getCurrentUrl(): ?string
    {
        return Request::fullUrl();
    }

    /**
     * Get request method
     */
    protected function getRequestMethod(): ?string
    {
        return Request::method();
    }

    /**
     * Get session ID (hashed for privacy)
     */
    protected function getSessionId(): ?string
    {
        $sessionId = session()->getId();

        return $sessionId ? hash('sha256', $sessionId) : null;
    }

    /**
     * Mask identifier for logging
     */
    protected function maskIdentifier(?string $identifier): ?string
    {
        if (empty($identifier)) {
            return null;
        }

        $length = strlen($identifier);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($identifier, 0, 2).str_repeat('*', $length - 4).substr($identifier, -2);
    }

    /**
     * Check if endpoint is sensitive
     */
    protected function isSensitiveEndpoint(string $endpoint): bool
    {
        $sensitivePatterns = [
            '/login',
            '/logout',
            '/students',
            '/grades',
            '/export',
            '/import',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (str_contains($endpoint, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return number_format($bytes / pow(1024, $power), 2).' '.$units[$power];
    }
}
