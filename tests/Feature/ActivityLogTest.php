<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\Classroom;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActivityLogTest extends TestCase
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
     * Test activity log is created on successful login
     */
    public function test_activity_log_created_on_successful_login(): void
    {
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $this->assertDatabaseHas("activity_logs", [
            "user_type" => "student",
            "user_id" => $this->student->id,
            "action" => "login",
            "module" => "auth",
        ]);
    }

    /**
     * Test activity log is created on failed login
     */
    public function test_activity_log_created_on_failed_login(): void
    {
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "wrongpassword",
        ]);

        $this->assertDatabaseHas("activity_logs", [
            "user_type" => "student",
            "user_id" => $this->student->id,
            "action" => "login_failed",
            "module" => "auth",
        ]);
    }

    /**
     * Test activity log is created on logout via service directly
     */
    public function test_activity_log_service_logs_logout(): void
    {
        // Test logging logout directly through the service
        ActivityLogService::logLogout("student", $this->student);

        $this->assertDatabaseHas("activity_logs", [
            "user_type" => "student",
            "user_id" => $this->student->id,
            "action" => "logout",
            "module" => "auth",
        ]);
    }

    /**
     * Test activity log service can log generic activities
     */
    public function test_activity_log_service_can_log_generic_activity(): void
    {
        ActivityLogService::log(
            "test_action",
            "test_module",
            "Test description",
            null,
            null,
            null,
            ["extra" => "data"],
        );

        $this->assertDatabaseHas("activity_logs", [
            "action" => "test_action",
            "module" => "test_module",
            "description" => "Test description",
        ]);

        $log = ActivityLog::where("action", "test_action")->first();
        $this->assertEquals(["extra" => "data"], $log->metadata);
    }

    /**
     * Test activity log stores IP address
     */
    public function test_activity_log_stores_ip_address(): void
    {
        $this->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $log = ActivityLog::where("action", "login")->first();
        $this->assertNotNull($log->ip_address);
    }

    /**
     * Test activity log stores user agent
     */
    public function test_activity_log_stores_user_agent(): void
    {
        $this->withHeaders([
            "User-Agent" => "Test Browser/1.0",
        ])->post("/students/login", [
            "nisn" => "1234567890",
            "password" => "password123",
        ]);

        $log = ActivityLog::where("action", "login")->first();
        $this->assertNotNull($log->user_agent);
        $this->assertStringContainsString("Test Browser", $log->user_agent);
    }

    /**
     * Test activity log model scopes
     */
    public function test_activity_log_model_scopes(): void
    {
        // Create some logs
        ActivityLog::create([
            "user_type" => "student",
            "user_id" => $this->student->id,
            "user_name" => $this->student->name,
            "action" => "login",
            "module" => "auth",
            "description" => "Test login",
        ]);

        ActivityLog::create([
            "user_type" => "admin",
            "user_id" => 1,
            "user_name" => "Admin User",
            "action" => "create",
            "module" => "student",
            "description" => "Test create",
        ]);

        // Test byAction scope
        $loginLogs = ActivityLog::byAction("login")->count();
        $this->assertEquals(1, $loginLogs);

        // Test byModule scope
        $authLogs = ActivityLog::byModule("auth")->count();
        $this->assertEquals(1, $authLogs);

        // Test byUser scope
        $studentLogs = ActivityLog::byUser(
            "student",
            $this->student->id,
        )->count();
        $this->assertEquals(1, $studentLogs);
    }

    /**
     * Test activity log today scope
     */
    public function test_activity_log_today_scope(): void
    {
        // Create a log for today
        $todayLog = ActivityLog::create([
            "action" => "scope_test_today",
            "module" => "test",
            "description" => "Today log",
        ]);

        // Verify today's log is found
        $foundToday = ActivityLog::today()
            ->where("id", $todayLog->id)
            ->exists();
        $this->assertTrue($foundToday);
    }

    /**
     * Test activity log casts JSON fields correctly
     */
    public function test_activity_log_casts_json_fields(): void
    {
        $log = ActivityLog::create([
            "action" => "test",
            "module" => "test",
            "description" => "Test",
            "old_values" => ["key" => "old"],
            "new_values" => ["key" => "new"],
            "metadata" => ["extra" => "info"],
        ]);

        $log->refresh();

        $this->assertIsArray($log->old_values);
        $this->assertIsArray($log->new_values);
        $this->assertIsArray($log->metadata);
        $this->assertEquals("old", $log->old_values["key"]);
        $this->assertEquals("new", $log->new_values["key"]);
        $this->assertEquals("info", $log->metadata["extra"]);
    }

    /**
     * Test time ago attribute returns a string
     */
    public function test_time_ago_attribute_returns_string(): void
    {
        $log = ActivityLog::create([
            "action" => "test",
            "module" => "test",
            "description" => "Test",
            "created_at" => now()->subMinutes(5),
        ]);

        // Just verify it returns a non-empty string (format varies by locale)
        $this->assertNotEmpty($log->time_ago);
        $this->assertIsString($log->time_ago);
    }

    /**
     * Test activity log service log create method
     */
    public function test_activity_log_service_log_create(): void
    {
        $log = ActivityLogService::logCreate(
            $this->student,
            "student",
            "Student created",
        );

        $this->assertDatabaseHas("activity_logs", [
            "action" => "create",
            "module" => "student",
            "subject_type" => Student::class,
            "subject_id" => $this->student->id,
        ]);
    }

    /**
     * Test activity log service log delete method
     */
    public function test_activity_log_service_log_delete(): void
    {
        ActivityLogService::logDelete(
            $this->student,
            "student",
            "Student deleted",
        );

        $this->assertDatabaseHas("activity_logs", [
            "action" => "delete",
            "module" => "student",
            "subject_type" => Student::class,
            "subject_id" => $this->student->id,
        ]);
    }

    /**
     * Test activity log date between scope
     */
    public function test_activity_log_date_between_scope(): void
    {
        // Create a log within range
        $inRangeLog = ActivityLog::create([
            "action" => "date_range_test",
            "module" => "test",
            "description" => "In range",
            "created_at" => now()->subDays(5),
        ]);

        // Verify log is found within the date range
        $found = ActivityLog::dateBetween(now()->subDays(10), now())
            ->where("id", $inRangeLog->id)
            ->exists();

        $this->assertTrue($found);
    }

    /**
     * Test sensitive data is filtered
     */
    public function test_sensitive_data_is_filtered_in_logs(): void
    {
        $data = [
            "name" => "Test",
            "password" => "secret123",
            "remember_token" => "token123",
        ];

        // Use reflection to test the protected method
        $reflection = new \ReflectionClass(ActivityLogService::class);
        $method = $reflection->getMethod("filterSensitiveData");
        $method->setAccessible(true);

        $filtered = $method->invoke(null, $data);

        $this->assertEquals("Test", $filtered["name"]);
        $this->assertEquals("[REDACTED]", $filtered["password"]);
        $this->assertEquals("[REDACTED]", $filtered["remember_token"]);
    }
}
