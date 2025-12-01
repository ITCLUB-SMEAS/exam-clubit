<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\Classroom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $student;
    protected $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classroom = Classroom::create(['title' => 'Kelas 10']);
        $this->student = Student::create([
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'classroom_id' => $this->classroom->id,
            'password' => Hash::make('password123'),
            'gender' => 'L',
        ]);
    }

    /** @test */
    public function student_can_view_profile_page()
    {
        $response = $this->actingAs($this->student, 'student')
            ->get('/student/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Student/Profile/Index')
                ->has('student')
        );
    }

    /** @test */
    public function student_can_update_profile()
    {
        $response = $this->actingAs($this->student, 'student')
            ->put('/student/profile', [
                'name' => 'Updated Name',
                'gender' => 'P',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'name' => 'Updated Name',
            'gender' => 'P',
        ]);
    }

    /** @test */
    public function student_can_change_password()
    {
        $response = $this->actingAs($this->student, 'student')
            ->put('/student/profile/password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        
        // Verify new password works
        $this->student->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->student->password));
    }

    /** @test */
    public function student_cannot_change_password_with_wrong_current_password()
    {
        $response = $this->actingAs($this->student, 'student')
            ->put('/student/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    /** @test */
    public function guest_cannot_access_profile()
    {
        $response = $this->get('/student/profile');
        $response->assertRedirect('/');
    }
}
