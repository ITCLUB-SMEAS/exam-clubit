<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ValidateAntiCheat
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = auth()->guard('student')->user();
        
        if (!$student) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $gradeId = $request->input('grade_id') ?? $request->route('grade_id');
        
        if (!$gradeId) {
            return $next($request);
        }

        // Check active session count
        $sessionKey = "exam_session:{$student->id}:{$gradeId}";
        $sessionId = $request->header('X-Session-ID') ?? session()->getId();
        
        // Get all active sessions for this student+exam
        $activeSessions = Cache::get($sessionKey, []);
        
        // Clean expired sessions (older than 30 seconds)
        $now = time();
        $activeSessions = array_filter($activeSessions, fn($data) => ($now - $data['last_seen']) < 30);
        
        // Check if this is a new session
        if (!isset($activeSessions[$sessionId])) {
            // If there are already active sessions, this is a duplicate tab
            if (count($activeSessions) > 0) {
                return response()->json([
                    'error' => 'Multiple tabs detected',
                    'message' => 'Ujian hanya boleh dibuka di satu tab'
                ], 403);
            }
        }
        
        // Update this session's last seen
        $activeSessions[$sessionId] = [
            'last_seen' => $now,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
        
        Cache::put($sessionKey, $activeSessions, 60);
        
        return $next($request);
    }
}
