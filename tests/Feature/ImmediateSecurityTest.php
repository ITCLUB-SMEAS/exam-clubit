<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImmediateSecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function password_must_meet_complexity_requirements()
    {
        $rule = new StrongPassword();
        
        // Test weak passwords
        $weakPasswords = ['12345678', 'password', 'abcdefgh', 'ABCDEFGH'];
        
        foreach ($weakPasswords as $password) {
            $fails = false;
            $rule->validate('password', $password, function() use (&$fails) {
                $fails = true;
            });
            
            $this->assertTrue($fails, "Password '{$password}' should fail validation");
        }
        
        // Test strong password
        $strongPassword = 'Password123';
        $fails = false;
        $rule->validate('password', $strongPassword, function() use (&$fails) {
            $fails = true;
        });
        
        $this->assertFalse($fails, "Password '{$strongPassword}' should pass validation");
    }

    /** @test */
    public function file_upload_validates_mime_type()
    {
        Storage::fake('public');
        
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        // Test valid image
        $validImage = UploadedFile::fake()->image('photo.jpg');
        $response = $this->post(route('admin.profile.photo'), [
            'photo' => $validImage
        ]);
        
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function api_validates_student_input_strictly()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $token = $user->createToken('test', ['admin'])->plainTextToken;

        // Test invalid NISN (contains letters)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/students', [
            'nisn' => 'ABC123',
            'name' => 'Test Student',
            'classroom_id' => 1,
            'password' => 'Password123',
            'gender' => 'L',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nisn']);
    }

    /** @test */
    public function student_model_has_guarded_attributes()
    {
        $student = new Student();
        $this->assertNotEmpty($student->getGuarded());
    }

    /** @test */
    public function encryption_service_encrypts_and_decrypts()
    {
        $original = 'Sensitive Data';
        $encrypted = \App\Services\EncryptionService::encrypt($original);
        
        $this->assertNotEquals($original, $encrypted);
        
        $decrypted = \App\Services\EncryptionService::decrypt($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /** @test */
    public function server_side_anticheat_middleware_exists()
    {
        $this->assertTrue(class_exists(\App\Http\Middleware\ServerSideAntiCheat::class));
    }
}
