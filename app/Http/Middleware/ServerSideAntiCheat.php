<?php

namespace App\Http\Middleware;

use App\Models\Grade;
use App\Services\AntiCheatService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ServerSideAntiCheat
{
    protected array $suspiciousUserAgents = [
        'headless', 'phantom', 'selenium', 'webdriver', 'puppeteer', 'playwright',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $student = auth()->guard('student')->user();
        if (!$student) {
            return $next($request);
        }

        $violations = [];
        $gradeId = $request->input('grade_id') ?? $request->route('id');

        // 1. Validate single session (prevent multiple tabs)
        if ($gradeId) {
            $sessionViolation = $this->validateSingleSession($request, $student->id, $gradeId);
            if ($sessionViolation) {
                $violations[] = $sessionViolation;
            }
        }

        // 2. Check User Agent for automation tools
        $userAgent = strtolower($request->userAgent() ?? '');
        foreach ($this->suspiciousUserAgents as $pattern) {
            if (str_contains($userAgent, $pattern)) {
                $violations[] = [
                    'type' => 'suspicious_user_agent',
                    'description' => 'Terdeteksi penggunaan automation tool',
                    'metadata' => ['user_agent' => $request->userAgent()],
                ];
                break;
            }
        }

        // 3. Check for rapid answer submission
        if ($request->has('time_spent') && $request->time_spent < 2) {
            $violations[] = [
                'type' => 'rapid_submission',
                'description' => 'Jawaban dikirim terlalu cepat (< 2 detik)',
                'metadata' => ['time_spent' => $request->time_spent],
            ];
        }

        // 4. Check for suspicious timing patterns
        if ($request->has('answer_times') && is_array($request->answer_times)) {
            $avgTime = array_sum($request->answer_times) / count($request->answer_times);
            $variance = $this->calculateVariance($request->answer_times, $avgTime);
            
            if ($variance < 0.5 && count($request->answer_times) > 5) {
                $violations[] = [
                    'type' => 'uniform_timing',
                    'description' => 'Pola waktu jawaban terlalu seragam (kemungkinan bot)',
                    'metadata' => ['variance' => $variance, 'avg_time' => $avgTime],
                ];
            }
        }

        // 5. Check IP address changes
        if ($gradeId) {
            $grade = Grade::find($gradeId);
            if ($grade && $grade->student_id === $student->id) {
                $startIp = $grade->metadata['start_ip'] ?? null;
                $currentIp = $request->ip();
                
                if ($startIp && $startIp !== $currentIp) {
                    $violations[] = [
                        'type' => 'ip_change',
                        'description' => 'IP address berubah selama ujian',
                        'metadata' => ['start_ip' => $startIp, 'current_ip' => $currentIp],
                    ];
                }
            }
        }

        // 6. Check for copy-paste patterns
        if ($request->has('answer_text')) {
            $text = $request->answer_text;
            
            if (preg_match('/[\x{200B}-\x{200D}\x{FEFF}]/u', $text)) {
                $violations[] = [
                    'type' => 'hidden_characters',
                    'description' => 'Terdeteksi karakter tersembunyi (kemungkinan copy-paste)',
                    'metadata' => ['text_length' => strlen($text)],
                ];
            }
        }

        // 7. Validate request timing (prevent time manipulation)
        $timingViolation = $this->validateRequestTiming($request, $student->id, $gradeId);
        if ($timingViolation) {
            $violations[] = $timingViolation;
        }

        // Log violations
        if (!empty($violations) && $gradeId) {
            $this->logViolations($request, $violations, $gradeId);
        }

        // Block request if critical violations
        $criticalTypes = ['multiple_sessions', 'suspicious_user_agent', 'time_manipulation'];
        $hasCritical = collect($violations)->pluck('type')->intersect($criticalTypes)->isNotEmpty();
        
        if ($hasCritical) {
            return response()->json([
                'error' => 'Anti-cheat violation detected',
                'violations' => $violations
            ], 403);
        }

        return $next($request);
    }

    protected function validateSingleSession(Request $request, int $studentId, $gradeId): ?array
    {
        $sessionKey = "exam_session:{$studentId}:{$gradeId}";
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        $activeSessions = Cache::get($sessionKey, []);
        $now = time();
        
        // Clean expired sessions
        $activeSessions = array_filter($activeSessions, fn($data) => ($now - $data['last_seen']) < 30);
        
        // Check for multiple sessions
        if (!isset($activeSessions[$sessionId]) && count($activeSessions) > 0) {
            return [
                'type' => 'multiple_sessions',
                'description' => 'Terdeteksi multiple tab/window aktif',
                'metadata' => ['active_count' => count($activeSessions) + 1],
            ];
        }
        
        // Update session
        $activeSessions[$sessionId] = [
            'last_seen' => $now,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
        
        Cache::put($sessionKey, $activeSessions, 60);
        
        return null;
    }

    protected function validateRequestTiming(Request $request, int $studentId, $gradeId): ?array
    {
        $timingKey = "exam_timing:{$studentId}:{$gradeId}";
        $lastRequest = Cache::get($timingKey);
        $now = microtime(true);
        
        if ($lastRequest) {
            $elapsed = $now - $lastRequest;
            
            // Too fast (< 100ms between requests = likely bot)
            if ($elapsed < 0.1) {
                Cache::put($timingKey, $now, 300);
                return [
                    'type' => 'rapid_requests',
                    'description' => 'Request terlalu cepat (kemungkinan bot)',
                    'metadata' => ['elapsed_ms' => round($elapsed * 1000, 2)],
                ];
            }
            
            // Time went backwards (system time manipulation)
            if ($elapsed < 0) {
                Cache::put($timingKey, $now, 300);
                return [
                    'type' => 'time_manipulation',
                    'description' => 'Terdeteksi manipulasi waktu sistem',
                    'metadata' => ['time_diff' => $elapsed],
                ];
            }
        }
        
        Cache::put($timingKey, $now, 300);
        return null;
    }

    protected function calculateVariance(array $values, float $mean): float
    {
        if (count($values) < 2) return 0;
        
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        return $variance / count($values);
    }

    protected function logViolations(Request $request, array $violations, $gradeId): void
    {
        $student = auth()->guard('student')->user();
        if (!$student) return;

        $grade = Grade::find($gradeId);
        if (!$grade || $grade->student_id !== $student->id) return;

        foreach ($violations as $violation) {
            AntiCheatService::recordViolation(
                $student,
                $grade->exam,
                $grade->exam_session_id,
                $grade,
                $violation['type'],
                $violation['description'],
                $violation['metadata']
            );
        }
    }
}
