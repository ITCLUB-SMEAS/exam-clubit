<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiLanguageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_all_language_files()
    {
        $this->assertFileExists(lang_path('id.json'));
        $this->assertFileExists(lang_path('en.json'));
        $this->assertFileExists(lang_path('zh.json'));
        $this->assertFileExists(lang_path('ja.json'));
    }

    /** @test */
    public function it_can_switch_language()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test'])
            ->post('/language/switch', [
                'locale' => 'en',
                '_token' => 'test'
            ]);

        $response->assertRedirect();
        $this->assertEquals('en', $user->fresh()->locale);
    }

    /** @test */
    public function it_rejects_invalid_language()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test'])
            ->post('/language/switch', [
                'locale' => 'invalid',
                '_token' => 'test'
            ]);

        $response->assertRedirect();
    }

    /** @test */
    public function it_persists_language_for_student()
    {
        $student = Student::factory()->create();
        
        $response = $this->actingAs($student, 'student')
            ->withSession(['_token' => 'test'])
            ->post('/language/switch', [
                'locale' => 'ja',
                '_token' => 'test'
            ]);

        $response->assertRedirect();
        $this->assertEquals('ja', $student->fresh()->locale);
    }

    /** @test */
    public function it_loads_correct_translations()
    {
        $translations = json_decode(file_get_contents(lang_path('en.json')), true);
        
        $this->assertArrayHasKey('Dashboard', $translations);
        $this->assertEquals('Dashboard', $translations['Dashboard']);
    }

    /** @test */
    public function chinese_translations_exist()
    {
        $translations = json_decode(file_get_contents(lang_path('zh.json')), true);
        
        $this->assertArrayHasKey('Dashboard', $translations);
        $this->assertEquals('仪表板', $translations['Dashboard']);
    }

    /** @test */
    public function japanese_translations_exist()
    {
        $translations = json_decode(file_get_contents(lang_path('ja.json')), true);
        
        $this->assertArrayHasKey('Dashboard', $translations);
        $this->assertEquals('ダッシュボード', $translations['Dashboard']);
    }

    /** @test */
    public function indonesian_translations_exist()
    {
        $translations = json_decode(file_get_contents(lang_path('id.json')), true);
        
        $this->assertArrayHasKey('Dashboard', $translations);
        $this->assertEquals('Dasbor', $translations['Dashboard']);
    }

    /** @test */
    public function all_languages_have_same_keys()
    {
        $id = json_decode(file_get_contents(lang_path('id.json')), true);
        $en = json_decode(file_get_contents(lang_path('en.json')), true);
        $zh = json_decode(file_get_contents(lang_path('zh.json')), true);
        $ja = json_decode(file_get_contents(lang_path('ja.json')), true);

        $this->assertEquals(array_keys($id), array_keys($en));
        $this->assertEquals(array_keys($id), array_keys($zh));
        $this->assertEquals(array_keys($id), array_keys($ja));
    }

    /** @test */
    public function locale_middleware_sets_app_locale()
    {
        $user = User::factory()->create(['locale' => 'zh']);
        
        $this->actingAs($user)->get('/admin/dashboard');
        
        $this->assertEquals('zh', app()->getLocale());
    }
}
