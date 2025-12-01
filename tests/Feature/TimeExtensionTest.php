<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Classroom;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeExtensionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Exam $exam;
    private ExamSession $session;
    private Student $student;
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Test Lesson']);
        $this->classroom = Classroom::create(['title' => 'Test Class']);
        
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
            'title' => 'Test Session',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
        ]);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);
    }

    public function test_admin_can_access_time_extension_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/time-extension');

        $response->assertStatus(200);
    }

    public function test_admin_can_extend_time_for_active_exam(): void
    {
        $grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => now(),
            'time_extension' => 0,
            'total_correct' => 0,
            'grade' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/time-extension/{$grade->id}", [
                'minutes' => 15,
                'reason' => 'Kendala teknis',
            ]);

        $response->assertRedirect();

        $grade->refresh();
        $this->assertEquals(15, $grade->time_extension);
        $this->assertEquals('Kendala teknis', $grade->extension_reason);
    }

    public function test_cannot_extend_time_for_completed_exam(): void
    {
        $grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'duration' => 0,
            'start_time' => now()->subHour(),
            'end_time' => now(),
            'time_extension' => 0,
            'total_correct' => 5,
            'grade' => 80,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/time-extension/{$grade->id}", [
                'minutes' => 15,
                'reason' => 'Test',
            ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_time_extension_validation(): void
    {
        $grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => now(),
            'total_correct' => 0,
            'grade' => 0,
        ]);

        $response = $this->actingAs($this->admin)
            ->post("/admin/time-extension/{$grade->id}", [
                'minutes' => 150,
                'reason' => 'Test',
            ]);

        $response->assertSessionHasErrors('minutes');
    }
}
