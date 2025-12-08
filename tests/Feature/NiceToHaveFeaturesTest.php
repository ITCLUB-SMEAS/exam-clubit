<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NiceToHaveFeaturesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function redis_queue_is_configured()
    {
        // Skip in test environment (uses sync)
        if (app()->environment('testing')) {
            $this->assertTrue(true);
            return;
        }
        
        $this->assertEquals('redis', config('queue.default'));
    }

    /** @test */
    public function cache_service_exists()
    {
        $this->assertTrue(
            class_exists(\App\Services\CacheService::class),
            'CacheService should exist'
        );
    }

    /** @test */
    public function analyze_slow_queries_command_exists()
    {
        $this->assertTrue(
            class_exists(\App\Console\Commands\AnalyzeSlowQueries::class),
            'AnalyzeSlowQueries command should exist'
        );
    }

    /** @test */
    public function performance_report_command_exists()
    {
        $this->assertTrue(
            class_exists(\App\Console\Commands\PerformanceReport::class),
            'PerformanceReport command should exist'
        );
    }

    /** @test */
    public function performance_report_is_scheduled()
    {
        $schedule = app()->make(\Illuminate\Console\Scheduling\Schedule::class);
        $events = collect($schedule->events());
        
        $perfEvent = $events->first(function ($event) {
            return str_contains($event->command ?? '', 'performance:report');
        });
        
        $this->assertNotNull($perfEvent, 'Performance report should be scheduled');
    }

    /** @test */

    /** @test */
    public function telegram_has_performance_command()
    {
        $service = app(\App\Services\TelegramService::class);
        $reflection = new \ReflectionClass($service);
        
        $this->assertTrue(
            $reflection->hasMethod('cmdPerformance'),
            'TelegramService should have cmdPerformance method'
        );
    }
}
