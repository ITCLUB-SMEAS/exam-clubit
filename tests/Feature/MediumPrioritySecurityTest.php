<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AntiCheatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediumPrioritySecurityTest extends TestCase
{
    use RefreshDatabase;

    // ==================== Snapshot Path Validation ====================

    public function test_valid_snapshot_path_is_accepted(): void
    {
        $this->assertTrue(AntiCheatService::isValidSnapshotPath('snapshots/123/violation_abc123.jpg'));
        $this->assertTrue(AntiCheatService::isValidSnapshotPath('snapshots/456/face_detect_xyz.png'));
        $this->assertTrue(AntiCheatService::isValidSnapshotPath('snapshots/1/test-image.jpeg'));
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('../../../etc/passwd'));
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('snapshots/../../../etc/passwd'));
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('snapshots/123/../../../etc/passwd'));
    }

    public function test_invalid_snapshot_paths_are_rejected(): void
    {
        // Wrong directory
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('uploads/123/test.jpg'));
        
        // Invalid extension
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('snapshots/123/test.php'));
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('snapshots/123/test.exe'));
        
        // Missing student ID
        $this->assertFalse(AntiCheatService::isValidSnapshotPath('snapshots/test.jpg'));
        
        // Null byte injection
        $this->assertFalse(AntiCheatService::isValidSnapshotPath("snapshots/123/test.jpg\0.php"));
    }

    // ==================== Test Route Protection ====================

    public function test_test_math_editor_route_is_environment_protected(): void
    {
        // In testing environment, route should exist
        // The protection is via environment check in routes/web.php
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.test.math');
        
        // Route exists in local/testing but wrapped in env check
        // We verify the route file has the protection
        $routeFile = file_get_contents(base_path('routes/web.php'));
        
        $this->assertStringContainsString(
            "app()->environment('local', 'testing')",
            $routeFile,
            'Test math editor route should be wrapped in environment check'
        );
    }

    // ==================== Rate Limiting on Destructive Operations ====================

    public function test_bulk_delete_questions_has_rate_limit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Check route has throttle middleware
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.exams.bulkDeleteQuestions');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Bulk delete questions should have throttle middleware');
    }

    public function test_activity_logs_cleanup_has_rate_limit(): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.activity-logs.cleanup');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Activity logs cleanup should have throttle middleware');
    }

    public function test_bulk_password_reset_has_rate_limit(): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.students.executeBulkPasswordReset');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Bulk password reset should have throttle middleware');
    }

    public function test_cleanup_old_data_has_rate_limit(): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.cleanup.run');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Cleanup old data should have throttle middleware');
    }

    public function test_question_bank_bulk_delete_has_rate_limit(): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.question-bank.bulkDelete');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Question bank bulk delete should have throttle middleware');
    }

    public function test_delete_all_notifications_has_rate_limit(): void
    {
        $routes = app('router')->getRoutes();
        $route = $routes->getByName('admin.notifications.destroyAll');
        
        $this->assertNotNull($route);
        $middlewares = $route->middleware();
        
        $hasThrottle = collect($middlewares)->contains(fn($m) => str_starts_with($m, 'throttle:'));
        $this->assertTrue($hasThrottle, 'Delete all notifications should have throttle middleware');
    }
}
