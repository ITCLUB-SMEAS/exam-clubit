<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AdaptiveTestingService;

class AdaptiveTestingServiceTest extends TestCase
{
    protected AdaptiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdaptiveTestingService();
    }

    public function test_difficulty_theta_values_are_correct()
    {
        $this->assertEquals(-1.0, AdaptiveTestingService::DIFFICULTY_THETA['easy']);
        $this->assertEquals(0.0, AdaptiveTestingService::DIFFICULTY_THETA['medium']);
        $this->assertEquals(1.0, AdaptiveTestingService::DIFFICULTY_THETA['hard']);
    }

    public function test_ability_level_labels_are_correct()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbilityLevel');
        $method->setAccessible(true);

        $this->assertEquals('Perlu Bimbingan', $method->invoke($this->service, -0.6));
        $this->assertEquals('Cukup', $method->invoke($this->service, -0.3));
        $this->assertEquals('Baik', $method->invoke($this->service, 0.3));
        $this->assertEquals('Sangat Baik', $method->invoke($this->service, 0.6));
    }

    public function test_difficulty_multiplier_values()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getDifficultyMultiplier');
        $method->setAccessible(true);

        $this->assertEquals(1.0, $method->invoke($this->service, 'easy'));
        $this->assertEquals(1.25, $method->invoke($this->service, 'medium'));
        $this->assertEquals(1.5, $method->invoke($this->service, 'hard'));
        $this->assertEquals(1.0, $method->invoke($this->service, 'unknown'));
    }

    public function test_theta_values_cover_full_range()
    {
        $thetas = AdaptiveTestingService::DIFFICULTY_THETA;
        
        $this->assertLessThan(0, $thetas['easy']);
        $this->assertEquals(0, $thetas['medium']);
        $this->assertGreaterThan(0, $thetas['hard']);
    }

    public function test_ability_level_boundary_values()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getAbilityLevel');
        $method->setAccessible(true);

        // Boundary at -0.5
        $this->assertEquals('Perlu Bimbingan', $method->invoke($this->service, -0.51));
        $this->assertEquals('Cukup', $method->invoke($this->service, -0.5));
        
        // Boundary at 0
        $this->assertEquals('Cukup', $method->invoke($this->service, -0.01));
        $this->assertEquals('Baik', $method->invoke($this->service, 0));
        
        // Boundary at 0.5
        $this->assertEquals('Baik', $method->invoke($this->service, 0.49));
        $this->assertEquals('Sangat Baik', $method->invoke($this->service, 0.5));
    }

    public function test_theta_spread_is_symmetric()
    {
        $thetas = AdaptiveTestingService::DIFFICULTY_THETA;
        
        // Easy and hard should be equidistant from medium
        $this->assertEquals(
            abs($thetas['easy'] - $thetas['medium']),
            abs($thetas['hard'] - $thetas['medium'])
        );
    }

    public function test_difficulty_multiplier_increases_with_difficulty()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getDifficultyMultiplier');
        $method->setAccessible(true);

        $easy = $method->invoke($this->service, 'easy');
        $medium = $method->invoke($this->service, 'medium');
        $hard = $method->invoke($this->service, 'hard');

        $this->assertLessThan($medium, $easy);
        $this->assertLessThan($hard, $medium);
    }

    public function test_all_difficulty_levels_have_theta_values()
    {
        $difficulties = ['easy', 'medium', 'hard'];
        
        foreach ($difficulties as $difficulty) {
            $this->assertArrayHasKey($difficulty, AdaptiveTestingService::DIFFICULTY_THETA);
        }
    }
}
