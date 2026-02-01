<?php

namespace Tests\Feature;

use App\Http\Middleware\ApiSecurityHeaders;
use App\Rules\StrongPassword;
use App\Services\SecureZipService;
use App\Services\SecurityAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // SecureZipService Tests
    // ========================================

    /** @test */
    public function secure_zip_service_exists()
    {
        $this->assertTrue(
            class_exists(SecureZipService::class),
            'SecureZipService should exist'
        );
    }

    /** @test */
    public function secure_zip_service_has_required_methods()
    {
        $service = new SecureZipService;

        $this->assertTrue(method_exists($service, 'extract'), 'Should have extract method');
        $this->assertTrue(method_exists($service, 'validate'), 'Should have validate method');
    }

    /** @test */
    public function secure_zip_service_respects_config_limits()
    {
        $service = new SecureZipService;

        // Test that default limits are loaded from config
        $reflection = new \ReflectionClass($service);

        $maxFilesProperty = $reflection->getProperty('maxFiles');
        $maxFilesProperty->setAccessible(true);
        $maxFiles = $maxFilesProperty->getValue($service);

        $this->assertEquals(
            config('security.uploads.zip.max_files', 1000),
            $maxFiles,
            'Should use config value for max files'
        );
    }

    // ========================================
    // StrongPassword Rule Tests
    // ========================================

    /** @test */
    public function strong_password_rule_exists()
    {
        $this->assertTrue(
            class_exists(StrongPassword::class),
            'StrongPassword rule should exist'
        );
    }

    /** @test */
    public function strong_password_rejects_short_passwords()
    {
        $rule = StrongPassword::fromConfig();

        $validator = Validator::make(
            ['password' => 'Ab1'],
            ['password' => $rule]
        );

        $this->assertTrue($validator->fails(), 'Short password should be rejected');
    }

    /** @test */
    public function strong_password_accepts_valid_passwords()
    {
        $rule = StrongPassword::fromConfig();

        $validator = Validator::make(
            ['password' => 'SecurePass123'],
            ['password' => $rule]
        );

        $this->assertFalse($validator->fails(), 'Valid password should be accepted');
    }

    /** @test */
    public function strong_password_rejects_passwords_without_uppercase()
    {
        config(['security.password.require_uppercase' => true]);
        $rule = StrongPassword::fromConfig();

        $validator = Validator::make(
            ['password' => 'securepass123'],
            ['password' => $rule]
        );

        $this->assertTrue($validator->fails(), 'Password without uppercase should be rejected');
    }

    /** @test */
    public function strong_password_rejects_passwords_without_lowercase()
    {
        config(['security.password.require_lowercase' => true]);
        $rule = StrongPassword::fromConfig();

        $validator = Validator::make(
            ['password' => 'SECUREPASS123'],
            ['password' => $rule]
        );

        $this->assertTrue($validator->fails(), 'Password without lowercase should be rejected');
    }

    /** @test */
    public function strong_password_rejects_passwords_without_numbers()
    {
        config(['security.password.require_numbers' => true]);
        $rule = StrongPassword::fromConfig();

        $validator = Validator::make(
            ['password' => 'SecurePassword'],
            ['password' => $rule]
        );

        $this->assertTrue($validator->fails(), 'Password without numbers should be rejected');
    }

    // ========================================
    // ApiSecurityHeaders Middleware Tests
    // ========================================

    /** @test */
    public function api_security_headers_middleware_exists()
    {
        $this->assertTrue(
            class_exists(ApiSecurityHeaders::class),
            'ApiSecurityHeaders middleware should exist'
        );
    }

    /** @test */
    public function api_security_headers_adds_security_headers()
    {
        $middleware = new ApiSecurityHeaders;
        $request = Request::create('/api/v1/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('test', 200, ['Content-Type' => 'text/plain']);
        });

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertEquals('1.0', $response->headers->get('X-API-Version'));
    }

    /** @test */
    public function api_security_headers_removes_sensitive_fields_from_json()
    {
        $middleware = new ApiSecurityHeaders;
        $request = Request::create('/api/v1/test', 'GET');

        $jsonData = json_encode([
            'id' => 1,
            'name' => 'Test User',
            'password' => 'should_be_removed',
            'remember_token' => 'should_be_removed',
            'api_token' => 'should_be_removed',
        ]);

        $response = $middleware->handle($request, function () use ($jsonData) {
            return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
        });

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('name', $responseData);
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('remember_token', $responseData);
        $this->assertArrayNotHasKey('api_token', $responseData);
    }

    /** @test */
    public function api_security_headers_masks_nisn_in_response()
    {
        $middleware = new ApiSecurityHeaders;
        $request = Request::create('/api/v1/test', 'GET');

        $jsonData = json_encode([
            'nisn' => '1234567890',
            'name' => 'Test Student',
        ]);

        $response = $middleware->handle($request, function () use ($jsonData) {
            return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
        });

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('nisn', $responseData);
        $this->assertNotEquals('1234567890', $responseData['nisn']);
        $this->assertStringContainsString('*', $responseData['nisn']);
        $this->assertStringEndsWith('7890', $responseData['nisn']); // Last 4 digits visible
    }

    /** @test */
    public function api_security_headers_masks_email_in_response()
    {
        $middleware = new ApiSecurityHeaders;
        $request = Request::create('/api/v1/test', 'GET');

        $jsonData = json_encode([
            'email' => 'testuser@example.com',
            'name' => 'Test User',
        ]);

        $response = $middleware->handle($request, function () use ($jsonData) {
            return new Response($jsonData, 200, ['Content-Type' => 'application/json']);
        });

        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('email', $responseData);
        $this->assertNotEquals('testuser@example.com', $responseData['email']);
        $this->assertStringContainsString('*', $responseData['email']);
        $this->assertStringContainsString('@example.com', $responseData['email']);
    }

    /** @test */
    public function api_security_headers_adds_deprecation_for_legacy_routes()
    {
        $middleware = new ApiSecurityHeaders;
        // Non-v1 API route (legacy)
        $request = Request::create('/api/legacy-endpoint', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('test', 200);
        });

        $this->assertEquals('true', $response->headers->get('Deprecation'));
        $this->assertNotNull($response->headers->get('Sunset'));
    }

    /** @test */
    public function api_security_headers_no_deprecation_for_v1_routes()
    {
        $middleware = new ApiSecurityHeaders;
        $request = Request::create('/api/v1/endpoint', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('test', 200);
        });

        $this->assertNull($response->headers->get('Deprecation'));
    }

    // ========================================
    // SecurityAuditService Tests
    // ========================================

    /** @test */
    public function security_audit_service_exists()
    {
        $this->assertTrue(
            class_exists(SecurityAuditService::class),
            'SecurityAuditService should exist'
        );
    }

    /** @test */
    public function security_audit_service_has_required_methods()
    {
        $service = new SecurityAuditService;

        $this->assertTrue(method_exists($service, 'log'), 'Should have log method');
        $this->assertTrue(method_exists($service, 'logAuthSuccess'), 'Should have logAuthSuccess method');
        $this->assertTrue(method_exists($service, 'logAuthFailure'), 'Should have logAuthFailure method');
        $this->assertTrue(method_exists($service, 'logPermissionDenied'), 'Should have logPermissionDenied method');
        $this->assertTrue(method_exists($service, 'logAntiCheatViolation'), 'Should have logAntiCheatViolation method');
        $this->assertTrue(method_exists($service, 'logZipBombDetected'), 'Should have logZipBombDetected method');
    }

    /** @test */
    public function security_audit_service_logs_to_database()
    {
        // Ensure the table exists (migration should have run)
        if (! Schema::hasTable('security_audit_logs')) {
            $this->markTestSkipped('security_audit_logs table not yet created');
        }

        $service = new SecurityAuditService;

        $service->log(
            SecurityAuditService::EVENT_AUTH_SUCCESS,
            SecurityAuditService::SEVERITY_INFO,
            '1',
            'admin',
            ['test' => true],
            'Test log entry'
        );

        $this->assertDatabaseHas('security_audit_logs', [
            'event_type' => SecurityAuditService::EVENT_AUTH_SUCCESS,
            'user_id' => '1',
            'user_type' => 'admin',
        ]);
    }

    /** @test */
    public function security_audit_service_has_correct_event_constants()
    {
        $this->assertEquals('auth.success', SecurityAuditService::EVENT_AUTH_SUCCESS);
        $this->assertEquals('auth.failure', SecurityAuditService::EVENT_AUTH_FAILURE);
        $this->assertEquals('permission.denied', SecurityAuditService::EVENT_PERMISSION_DENIED);
        $this->assertEquals('rate_limit.exceeded', SecurityAuditService::EVENT_RATE_LIMIT_EXCEEDED);
        $this->assertEquals('anticheat.violation', SecurityAuditService::EVENT_ANTICHEAT_VIOLATION);
        $this->assertEquals('anticheat.block', SecurityAuditService::EVENT_ANTICHEAT_BLOCK);
        $this->assertEquals('security.zip_bomb', SecurityAuditService::EVENT_ZIP_BOMB_DETECTED);
    }

    /** @test */
    public function security_audit_service_has_correct_severity_constants()
    {
        $this->assertEquals('info', SecurityAuditService::SEVERITY_INFO);
        $this->assertEquals('warning', SecurityAuditService::SEVERITY_WARNING);
        $this->assertEquals('critical', SecurityAuditService::SEVERITY_CRITICAL);
    }

    // ========================================
    // Config Tests
    // ========================================

    /** @test */
    public function security_config_has_audit_section()
    {
        $this->assertNotNull(config('security.audit'));
        $this->assertArrayHasKey('log_to_database', config('security.audit'));
        $this->assertArrayHasKey('log_to_file', config('security.audit'));
        $this->assertArrayHasKey('retention_days', config('security.audit'));
    }

    /** @test */
    public function security_config_has_zip_limits()
    {
        $this->assertNotNull(config('security.uploads.zip'));
        $this->assertArrayHasKey('max_files', config('security.uploads.zip'));
        $this->assertArrayHasKey('max_extracted_size', config('security.uploads.zip'));
        $this->assertArrayHasKey('max_compression_ratio', config('security.uploads.zip'));
    }

    /** @test */
    public function security_config_has_anticheat_settings()
    {
        $this->assertNotNull(config('security.anticheat'));
        $this->assertArrayHasKey('auto_block_threshold', config('security.anticheat'));
        $this->assertArrayHasKey('auto_block_enabled', config('security.anticheat'));
        $this->assertArrayHasKey('critical_violations', config('security.anticheat'));
    }

    /** @test */
    public function security_config_has_password_policy()
    {
        $this->assertNotNull(config('security.password'));
        $this->assertArrayHasKey('min_length', config('security.password'));
        $this->assertArrayHasKey('require_uppercase', config('security.password'));
        $this->assertArrayHasKey('require_lowercase', config('security.password'));
        $this->assertArrayHasKey('require_numbers', config('security.password'));
    }

    /** @test */
    public function logging_config_has_security_channel()
    {
        $this->assertNotNull(config('logging.channels.security'));
        $this->assertEquals('daily', config('logging.channels.security.driver'));
        $this->assertStringContainsString('security.log', config('logging.channels.security.path'));
    }

    // ========================================
    // Middleware Registration Tests
    // ========================================

    /** @test */
    public function api_security_middleware_is_registered()
    {
        // Check that the middleware alias exists
        $aliases = app('router')->getMiddlewareGroups();

        // API group should include ApiSecurityHeaders
        $this->assertTrue(
            in_array(\App\Http\Middleware\ApiSecurityHeaders::class, $aliases['api'] ?? []),
            'ApiSecurityHeaders should be registered in api middleware group'
        );
    }

    // ========================================
    // Rate Limiting Tests
    // ========================================

    /** @test */
    public function rate_limiters_are_defined()
    {
        // Check that rate limiters are configured
        $this->assertNotNull(config('security.rate_limits.login'));
        $this->assertNotNull(config('security.rate_limits.api'));
    }

    // ========================================
    // Integration Tests
    // ========================================

    /** @test */
    public function api_endpoints_return_security_headers()
    {
        $response = $this->getJson('/api/v1/me');

        // Even if unauthorized, security headers should be present
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }
}
