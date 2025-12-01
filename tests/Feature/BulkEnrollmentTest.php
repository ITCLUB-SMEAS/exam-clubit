<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamGroup;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Exam $exam;
    private ExamSession $session;
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Math']);
        $this->classroom = Classroom::create(['title' => 'Class 10A']);
        
        $this->exam = Exam::create([
            'title' => 'Test Exam',
            'lesson_id' => $lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Session 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
        ]);
    }

    public function test_can_bulk_enroll_entire_class(): void
    {
        // Create 5 students in the classroom
        for ($i = 1; $i <= 5; $i++) {
            Student::create([
                'classroom_id' => $this->classroom->id,
                'nisn' => "100000000{$i}",
                'name' => "Student {$i}",
                'password' => bcrypt('password'),
                'gender' => 'L',
            ]);
        }

        $response = $this->actingAs($this->admin)
            ->post("/admin/exam_sessions/{$this->session->id}/bulk-enroll", [
                'classroom_id' => $this->classroom->id,
            ]);

        $response->assertRedirect();
        
        // All 5 students should be enrolled
        $this->assertEquals(5, ExamGroup::where('exam_session_id', $this->session->id)->count());
    }

    public function test_bulk_enroll_skips_already_enrolled_students(): void
    {
        // Create 3 students
        $students = [];
        for ($i = 1; $i <= 3; $i++) {
            $students[] = Student::create([
                'classroom_id' => $this->classroom->id,
                'nisn' => "200000000{$i}",
                'name' => "Student {$i}",
                'password' => bcrypt('password'),
                'gender' => 'L',
            ]);
        }

        // Enroll first student manually
        ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $students[0]->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/exam_sessions/{$this->session->id}/bulk-enroll", [
                'classroom_id' => $this->classroom->id,
            ]);

        $response->assertRedirect();
        
        // Should have 3 total (1 existing + 2 new)
        $this->assertEquals(3, ExamGroup::where('exam_session_id', $this->session->id)->count());
    }

    public function test_can_bulk_unenroll_entire_class(): void
    {
        // Create and enroll 3 students
        for ($i = 1; $i <= 3; $i++) {
            $student = Student::create([
                'classroom_id' => $this->classroom->id,
                'nisn' => "300000000{$i}",
                'name' => "Student {$i}",
                'password' => bcrypt('password'),
                'gender' => 'L',
            ]);

            ExamGroup::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'student_id' => $student->id,
            ]);
        }

        $this->assertEquals(3, ExamGroup::where('exam_session_id', $this->session->id)->count());

        $response = $this->actingAs($this->admin)
            ->delete("/admin/exam_sessions/{$this->session->id}/bulk-unenroll", [
                'classroom_id' => $this->classroom->id,
            ]);

        $response->assertRedirect();
        
        // All should be removed
        $this->assertEquals(0, ExamGroup::where('exam_session_id', $this->session->id)->count());
    }

    public function test_show_page_includes_classrooms_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/exam_sessions/{$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/ExamSessions/Show')
            ->has('classrooms')
            ->has('enrolledByClass')
        );
    }

    public function test_bulk_enroll_requires_valid_classroom(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/exam_sessions/{$this->session->id}/bulk-enroll", [
                'classroom_id' => 99999,
            ]);

        $response->assertSessionHasErrors('classroom_id');
    }
}
