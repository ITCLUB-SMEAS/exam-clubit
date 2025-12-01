<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);

        $response = $this->getJson('/api/students');
        $response->assertStatus(401);

        $response = $this->getJson('/api/grades');
        $response->assertStatus(401);
    }

    public function test_non_admin_cannot_access_student_management(): void
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $token = $guru->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/students');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_student_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        Classroom::create(['title' => 'Test Class']);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/students');

        $response->assertStatus(200);
    }

    public function test_login_returns_token_with_expiration(): void
    {
        $user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
                'expires_at',
            ]);
    }

    public function test_login_rate_limiting(): void
    {
        // Make 6 requests (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'wrong@email.com',
                'password' => 'wrongpassword',
            ]);
        }

        // 6th request should be rate limited
        $response->assertStatus(429);
    }

    public function test_invalid_credentials_returns_error(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil']);

        // Verify token is deleted from database
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test',
        ]);
    }

    public function test_me_endpoint_does_not_expose_sensitive_data(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'email', 'role'])
            ->assertJsonMissing(['password', 'remember_token']);
    }
}
