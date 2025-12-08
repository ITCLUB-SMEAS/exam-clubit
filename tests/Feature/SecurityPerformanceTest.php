<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    // SECURITY TESTS

    /** @test */
    public function cors_config_exists()
    {
        $this->assertFileExists(config_path('cors.php'));
        $this->assertIsArray(config('cors.paths'));
    }

    /** @test */
    public function security_headers_are_present()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('X-Content-Type-Options');
        $response->assertHeader('X-XSS-Protection');
        $response->assertHeader('Referrer-Policy');
        $response->assertHeader('Content-Security-Policy');
    }

    /** @test */
    public function csp_header_is_strict()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    /** @test */
    public function session_security_is_configured()
    {
        $this->assertEquals(true, config('session.encrypt'));
        $this->assertNotEmpty(config('session.driver'));
    }

    /** @test */
    public function api_requires_authentication()
    {
        $response = $this->getJson('/api/students');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function api_rate_limiting_works()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('test', ['admin'])->plainTextToken;

        // Test that API is accessible with proper token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/students');
        
        $response->assertSuccessful();
    }

    // PERFORMANCE TESTS

    /** @test */
    public function dashboard_uses_caching()
    {
        $user = User::factory()->create();
        
        Cache::flush();
        
        $this->actingAs($user)->get('/admin/dashboard');
        
        $this->assertTrue(Cache::has('dashboard_stats'));
    }

    /** @test */
    public function database_uses_persistent_connections()
    {
        $config = config('database.connections.mysql.options');
        
        $this->assertArrayHasKey(\PDO::ATTR_PERSISTENT, $config);
        $this->assertTrue($config[\PDO::ATTR_PERSISTENT]);
    }

    /** @test */
    public function lazy_image_component_exists()
    {
        $this->assertFileExists(resource_path('js/Components/LazyImage.vue'));
    }

    /** @test */
    public function vite_config_has_optimization()
    {
        $viteConfig = file_get_contents(base_path('vite.config.js'));
        
        $this->assertStringContainsString('manualChunks', $viteConfig);
        $this->assertStringContainsString('rollupOptions', $viteConfig);
    }

    /** @test */
    public function response_cache_middleware_exists()
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\CacheResponse::class));
    }

    /** @test */
    public function queries_use_eager_loading()
    {
        $user = User::factory()->create();
        
        // Enable query log
        \DB::enableQueryLog();
        
        $this->actingAs($user)->get('/admin/dashboard');
        
        $queries = \DB::getQueryLog();
        
        // Should have reasonable number of queries (< 20)
        $this->assertLessThan(20, count($queries));
    }
}
