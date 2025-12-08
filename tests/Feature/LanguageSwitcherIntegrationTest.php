<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LanguageSwitcherIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_dashboard_has_locale_in_props()
    {
        $user = User::factory()->create(['locale' => 'en']);
        
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('locale')
                ->has('translations')
                ->has('languages')
        );
    }

    /** @test */
    public function student_dashboard_has_locale_in_props()
    {
        $student = Student::factory()->create(['locale' => 'ja']);
        
        $response = $this->actingAs($student, 'student')->get('/student/dashboard');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('locale')
                ->has('translations')
                ->has('languages')
        );
    }

    /** @test */
    public function translations_are_loaded_correctly()
    {
        $user = User::factory()->create(['locale' => 'zh']);
        
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        $response->assertInertia(fn ($page) => 
            $page->where('locale', 'zh')
                ->where('translations.Dashboard', '仪表板')
        );
    }

    /** @test */
    public function language_switch_updates_user_preference()
    {
        $user = User::factory()->create(['locale' => 'id']);
        
        $this->actingAs($user)
            ->withSession(['_token' => 'test'])
            ->post('/language/switch', [
                'locale' => 'en',
                '_token' => 'test'
            ]);
        
        $this->assertEquals('en', $user->fresh()->locale);
    }

    /** @test */
    public function subsequent_requests_use_saved_locale()
    {
        $user = User::factory()->create(['locale' => 'ja']);
        
        $response = $this->actingAs($user)->get('/admin/dashboard');
        
        $response->assertInertia(fn ($page) => 
            $page->where('locale', 'ja')
        );
    }
}
