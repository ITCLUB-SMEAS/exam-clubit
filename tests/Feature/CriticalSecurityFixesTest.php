<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CriticalSecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_mode_is_disabled_in_production(): void
    {
        $this->assertFalse(config('app.debug'), 'APP_DEBUG should be false in production');
    }

    public function test_student_login_has_rate_limiting(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyTurnstileToken::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $student = Student::factory()->create(['password' => bcrypt('password')]);

        // Attempt 6 logins (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('student.login'), [
                'nisn' => $student->nisn,
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be rate limited
        $response->assertStatus(429);
    }

    public function test_admin_login_has_rate_limiting(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyTurnstileToken::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Attempt 6 logins (limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/admin/login', [
                'email' => 'test@test.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th attempt should be rate limited
        $response->assertStatus(429);
    }

    public function test_env_file_has_secure_permissions(): void
    {
        $envPath = base_path('.env');
        
        if (file_exists($envPath)) {
            $perms = substr(sprintf('%o', fileperms($envPath)), -3);
            $this->assertEquals('600', $perms, '.env file should have 600 permissions');
        } else {
            $this->markTestSkipped('.env file not found');
        }
    }

    public function test_sensitive_data_not_exposed_when_debug_off(): void
    {
        config(['app.debug' => false]);
        
        $response = $this->get('/non-existent-route');
        
        $response->assertStatus(404);
        $response->assertDontSee('APP_KEY');
        $response->assertDontSee('DB_PASSWORD');
    }
}
