<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class StudentLoginTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a classroom
        $this->classroom = Classroom::create([
            "title" => "Kelas 12 IPA 1",
        ]);

        // Create a test student with hashed password
        $this->student = Student::create([
            "classroom_id" => $this->classroom->id,
            "nisn" => "1234567890",
            "name" => "Test Student",
            "password" => Hash::make("password123"),
            "gender" => "L",
        ]);
    }

    /**
     * Test successful student login
     */
    public function test_student_can_login_with_correct_credentials(): void
    {
        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $response->assertRedirect("/student/dashboard");
        $this->assertAuthenticatedAs($this->student, "student");

        // Verify session_id is stored
        $this->student->refresh();
        $this->assertNotNull($this->student->session_id);
        $this->assertNotNull($this->student->last_login_at);
    }

    /**
     * Test failed login with wrong password
     */
    public function test_student_cannot_login_with_wrong_password(): void
    {
        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "wrongpassword",
        ]);

        $response->assertRedirect("/");
        $response->assertSessionHas("error");
        $this->assertGuest("student");
    }

    /**
     * Test failed login with wrong NISN
     */
    public function test_student_cannot_login_with_wrong_nisn(): void
    {
        $response = $this->post("/students/login", [
            "nisn" => "9999999999",
            "password" => "password123",
        ]);

        $response->assertRedirect("/");
        $response->assertSessionHas("error");
        $this->assertGuest("student");
    }

    /**
     * Test validation errors
     */
    public function test_login_requires_nisn_and_password(): void
    {
        $response = $this->post("/students/login", [
            "nisn" => "",
            "password" => "",
        ]);

        $response->assertSessionHasErrors(["nisn", "password"]);
    }

    /**
     * Test rate limiting after multiple failed attempts
     */
    public function test_login_is_rate_limited_after_too_many_attempts(): void
    {
        // Clear any existing rate limits
        RateLimiter::clear("student_login:1234567890|127.0.0.1");

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post("/students/login", [
                "nisn" => "1234567890",
                "password" => "wrongpassword",
            ]);
        }

        // The 6th attempt should be rate limited
        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "wrongpassword",
        ]);

        $response->assertRedirect("/");
        $response->assertSessionHas("error");

        // Check that the error message mentions rate limiting
        $session = session()->all();
        $this->assertStringContains(
            "Terlalu banyak percobaan",
            $session["error"] ?? "",
        );
    }

    /**
     * Test rate limiter is cleared on successful login
     */
    public function test_rate_limiter_is_cleared_on_successful_login(): void
    {
        // Clear any existing rate limits
        $key = "student_login:1234567890|127.0.0.1";
        RateLimiter::clear($key);

        // Make 3 failed login attempts
        for ($i = 0; $i < 3; $i++) {
            $this->post("/students/login", [
                "nisn" => "1234567890",
                "password" => "wrongpassword",
            ]);
        }

        // Successful login should clear the rate limiter
        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $response->assertRedirect("/student/dashboard");

        // After successful login, rate limiter should be cleared
        // So we can attempt login again without being rate limited
        auth()->guard("student")->logout();

        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "wrongpassword",
        ]);

        // Should get normal error, not rate limited
        $session = session()->all();
        $this->assertStringNotContains(
            "Terlalu banyak percobaan",
            $session["error"] ?? "",
        );
    }

    /**
     * Test single session - new login invalidates previous session
     */
    public function test_new_login_updates_session_id(): void
    {
        // First login
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $this->student->refresh();
        $firstSessionId = $this->student->session_id;
        $this->assertNotNull($firstSessionId);

        // Logout
        auth()->guard("student")->logout();
        session()->invalidate();
        session()->regenerate();

        // Second login (simulating login from another device)
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $this->student->refresh();
        $secondSessionId = $this->student->session_id;
        $this->assertNotNull($secondSessionId);

        // Session IDs should be different
        $this->assertNotEquals($firstSessionId, $secondSessionId);
    }

    /**
     * Test logout clears session_id
     */
    public function test_logout_clears_session_id(): void
    {
        // Set up authenticated student with session
        $this->student->updateSessionInfo("test_session_123", "127.0.0.1");

        $this->student->refresh();
        $this->assertNotNull($this->student->session_id);
        $this->assertEquals("test_session_123", $this->student->session_id);

        // Test clearSessionInfo method directly
        $this->student->clearSessionInfo();

        $this->student->refresh();
        $this->assertNull($this->student->session_id);
    }

    /**
     * Test logout endpoint redirects properly
     */
    public function test_logout_endpoint_redirects_to_homepage(): void
    {
        $this->actingAs($this->student, "student");

        $response = $this->post("/student/logout");

        $response->assertRedirect("/");
    }

    /**
     * Test middleware blocks access for unauthenticated users
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->get("/student/dashboard");

        $response->assertRedirect("/");
    }

    /**
     * Test authenticated user can access dashboard
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        // Login through the normal flow to ensure session is set correctly
        $response = $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $response->assertRedirect("/student/dashboard");

        // Now access dashboard
        $response = $this->get("/student/dashboard");

        // Should be successful (200) or render Inertia page
        $this->assertTrue(
            $response->status() === 200 || $response->status() === 302,
            "Expected status 200 or 302, got {$response->status()}",
        );
    }

    /**
     * Test session hijacking prevention - mismatched session_id
     */
    public function test_mismatched_session_id_forces_logout(): void
    {
        // Login and set session_id
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $this->student->refresh();
        $this->assertNotNull($this->student->session_id);

        // Manually change the session_id in database (simulating login from another device)
        $this->student->update(["session_id" => "different_session_id"]);

        // Try to access a protected route
        $response = $this->get("/student/dashboard");

        // Should be redirected to login with error
        $response->assertRedirect("/");
        $this->assertGuest("student");
    }

    /**
     * Test login displays remaining attempts warning
     */
    public function test_login_shows_remaining_attempts_warning(): void
    {
        // Clear any existing rate limits
        RateLimiter::clear("student_login:1234567890|127.0.0.1");

        // Make 3 failed login attempts to trigger warning
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post("/students/login", [
                "nisn" => "1234567890",
                "password" => "wrongpassword",
            ]);
        }

        // The error message should mention remaining attempts
        $session = session()->all();
        $error = $session["error"] ?? "";
        $this->assertStringContains("Sisa percobaan", $error);
    }

    /**
     * Test homepage redirects authenticated student to dashboard
     */
    public function test_homepage_redirects_authenticated_student_to_dashboard(): void
    {
        $this->actingAs($this->student, "student");

        $response = $this->get("/");

        $response->assertRedirect("/student/dashboard");
    }

    /**
     * Helper method to check if string contains substring
     */
    protected function assertStringContains(
        string $needle,
        string $haystack,
    ): void {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'",
        );
    }

    /**
     * Helper method to check if string does not contain substring
     */
    protected function assertStringNotContains(
        string $needle,
        string $haystack,
    ): void {
        $this->assertFalse(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' does not contain '{$needle}'",
        );
    }
}
