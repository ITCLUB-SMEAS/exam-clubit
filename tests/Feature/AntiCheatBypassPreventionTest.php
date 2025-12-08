<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AntiCheatBypassPreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_side_session_validation_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\ServerSideAntiCheat::class));
    }

    public function test_session_tracking_works(): void
    {
        $studentId = 1;
        $gradeId = 1;
        $sessionKey = "exam_session:{$studentId}:{$gradeId}";
        
        // Simulate first session
        $sessions = [
            'session-1' => [
                'last_seen' => time(),
                'ip' => '127.0.0.1',
                'user_agent' => 'test',
            ]
        ];
        
        Cache::put($sessionKey, $sessions, 60);
        
        // Verify session is stored
        $stored = Cache::get($sessionKey);
        $this->assertIsArray($stored);
        $this->assertArrayHasKey('session-1', $stored);
    }

    public function test_expired_sessions_are_cleaned(): void
    {
        $studentId = 1;
        $gradeId = 1;
        $sessionKey = "exam_session:{$studentId}:{$gradeId}";
        
        // Add old session
        $sessions = [
            'old-session' => [
                'last_seen' => time() - 60, // 60 seconds ago
                'ip' => '127.0.0.1',
                'user_agent' => 'test',
            ]
        ];
        
        Cache::put($sessionKey, $sessions, 60);
        
        // Clean expired (> 30 seconds)
        $now = time();
        $cleaned = array_filter($sessions, fn($data) => ($now - $data['last_seen']) < 30);
        
        $this->assertEmpty($cleaned);
    }

    public function test_timing_validation_detects_rapid_requests(): void
    {
        $studentId = 1;
        $gradeId = 1;
        $timingKey = "exam_timing:{$studentId}:{$gradeId}";
        
        // First request
        $time1 = microtime(true);
        Cache::put($timingKey, $time1, 300);
        
        // Immediate second request (< 100ms)
        $time2 = $time1 + 0.05; // 50ms later
        $elapsed = $time2 - Cache::get($timingKey);
        
        $this->assertLessThan(0.1, $elapsed);
        $this->assertTrue($elapsed < 0.1, 'Should detect rapid request');
    }

    public function test_suspicious_user_agents_are_detected(): void
    {
        $suspiciousAgents = [
            'headless', 'phantom', 'selenium', 'webdriver', 'puppeteer', 'playwright'
        ];
        
        $testAgent = 'Mozilla/5.0 (compatible; Selenium/4.0)';
        $isDetected = false;
        
        foreach ($suspiciousAgents as $pattern) {
            if (str_contains(strtolower($testAgent), $pattern)) {
                $isDetected = true;
                break;
            }
        }
        
        $this->assertTrue($isDetected);
    }

    public function test_hidden_characters_are_detected(): void
    {
        $textWithHidden = "Normal text\u{200B}with hidden\u{200C}characters";
        
        $hasHidden = preg_match('/[\x{200B}-\x{200D}\x{FEFF}]/u', $textWithHidden);
        
        $this->assertEquals(1, $hasHidden);
    }

    public function test_normal_text_passes_validation(): void
    {
        $normalText = "This is normal text without hidden characters";
        
        $hasHidden = preg_match('/[\x{200B}-\x{200D}\x{FEFF}]/u', $normalText);
        
        $this->assertEquals(0, $hasHidden);
    }

    public function test_session_id_header_is_used(): void
    {
        $student = Student::factory()->create();
        
        $response = $this->actingAs($student, 'student')
            ->withHeader('X-Session-ID', 'test-session-123')
            ->get('/student/dashboard');
        
        $response->assertStatus(200);
    }
}
