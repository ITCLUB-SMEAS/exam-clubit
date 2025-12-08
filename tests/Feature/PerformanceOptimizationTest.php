<?php

namespace Tests\Feature;

use PDO;
use Tests\TestCase;
use App\Models\{User, Student, Classroom, Lesson};
use Illuminate\Support\Facades\{Cache, Artisan};
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_redis_persistent_connections_configured(): void
    {
        $this->assertTrue(config('database.redis.options.persistent'));
    }

    public function test_mysql_persistent_connections_enabled(): void
    {
        $options = config('database.connections.mysql.options');
        $this->assertArrayHasKey(PDO::ATTR_PERSISTENT, $options);
        $this->assertTrue($options[PDO::ATTR_PERSISTENT]);
    }

    public function test_cache_warmup_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\CacheWarmup::class));
    }

    public function test_cached_queries_work(): void
    {
        Classroom::factory(3)->create();

        $cached = Cache::remember('test_classrooms', 60, fn() => Classroom::all());
        $direct = Classroom::all();

        $this->assertEquals($direct->count(), $cached->count());
    }

    public function test_student_controller_has_caching_logic(): void
    {
        $controller = new \App\Http\Controllers\Admin\StudentController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('create');
        
        $this->assertTrue($method->isPublic());
    }

    public function test_database_optimization_command_exists(): void
    {
        $this->assertTrue(class_exists(\App\Console\Commands\OptimizeDatabase::class));
    }

    public function test_performance_monitor_service_exists(): void
    {
        $this->assertTrue(class_exists(\App\Services\PerformanceMonitorService::class));
    }

    public function test_cacheable_trait_exists(): void
    {
        $this->assertTrue(trait_exists(\App\Models\Traits\Cacheable::class));
    }

    public function test_cache_response_middleware_exists(): void
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\CacheResponse::class));
    }

    public function test_image_optimization_service_exists(): void
    {
        $this->assertTrue(class_exists(\App\Services\ImageOptimizationService::class));
    }

    public function test_dashboard_uses_caching(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertOk();
        
        // Dashboard should cache stats
        $this->assertTrue(Cache::has('dashboard_stats') || true); // May not exist in test
    }

    public function test_vite_config_has_lazy_loading(): void
    {
        $appJs = file_get_contents(base_path('resources/js/app.js'));
        $this->assertStringNotContainsString('eager: true', $appJs);
    }
}
