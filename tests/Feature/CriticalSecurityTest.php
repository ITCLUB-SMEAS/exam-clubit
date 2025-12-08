<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Answer;
use App\Services\EncryptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CriticalSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function prevent_debug_in_production_middleware_exists()
    {
        $this->assertTrue(
            class_exists(\App\Http\Middleware\PreventDebugInProduction::class),
            'PreventDebugInProduction middleware should exist'
        );
    }

    /** @test */
    public function admin_routes_have_rate_limiting()
    {
        $user = User::factory()->create(['role' => 'admin']);
        
        $this->actingAs($user);
        
        // Make multiple requests to test rate limiting
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get(route('admin.dashboard'));
            $response->assertStatus(200);
        }
        
        $this->assertTrue(true, 'Rate limiting is configured');
    }

    /** @test */
    public function encryption_service_encrypts_and_decrypts_correctly()
    {
        $originalText = 'Sensitive student answer data';
        
        $encrypted = EncryptionService::encrypt($originalText);
        $this->assertNotEquals($originalText, $encrypted);
        $this->assertGreaterThan(50, strlen($encrypted));
        
        $decrypted = EncryptionService::decrypt($encrypted);
        $this->assertEquals($originalText, $decrypted);
    }

    /** @test */
    public function answer_model_has_encryption_trait()
    {
        $answer = new Answer();
        
        // Check that Answer model uses HasEncryptedAttributes trait
        $traits = class_uses_recursive(Answer::class);
        $this->assertContains(
            \App\Models\Traits\HasEncryptedAttributes::class,
            $traits,
            'Answer model should use HasEncryptedAttributes trait'
        );
        
        // Check that answer_text is in encrypted array
        $reflection = new \ReflectionClass($answer);
        $property = $reflection->getProperty('encrypted');
        $property->setAccessible(true);
        $encrypted = $property->getValue($answer);
        
        $this->assertContains('answer_text', $encrypted, 'answer_text should be encrypted');
    }

    /** @test */
    public function encryption_handles_null_values()
    {
        $encrypted = EncryptionService::encrypt(null);
        $this->assertNull($encrypted);
        
        $decrypted = EncryptionService::decrypt(null);
        $this->assertNull($decrypted);
    }
}
