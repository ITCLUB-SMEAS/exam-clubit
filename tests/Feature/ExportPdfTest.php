<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Exam $exam;
    private Student $student;
    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Matematika']);
        $classroom = Classroom::create(['title' => 'Kelas 10A']);
        
        $this->exam = Exam::create([
            'title' => 'UTS Matematika',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Ujian Tengah Semester',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
            'passing_grade' => 70,
        ]);

        $session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Sesi 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '1234567890',
            'name' => 'Budi Santoso',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);

        $this->grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
            'duration' => 0,
            'start_time' => now()->subMinutes(30),
            'end_time' => now(),
            'total_correct' => 8,
            'grade' => 80,
            'status' => 'passed',
        ]);
    }

    public function test_can_export_single_grade_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/export/grade/{$this->grade->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_export_exam_results_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/export/exam/{$this->exam->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_export_student_report_pdf(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/export/student/{$this->student->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_unauthenticated_cannot_export(): void
    {
        $response = $this->get("/admin/export/grade/{$this->grade->id}/pdf");
        $response->assertRedirect('/admin/login');
    }

    public function test_export_exam_with_session_filter(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/export/exam/{$this->exam->id}/pdf?session_id={$this->grade->exam_session_id}");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
