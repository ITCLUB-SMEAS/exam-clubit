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

class DashboardChartTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_dashboard_returns_all_required_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard/Index')
            ->has('students')
            ->has('exams')
            ->has('exam_sessions')
            ->has('classrooms')
            ->has('activeSessions')
            ->has('gradeDistribution')
            ->has('examTrend')
            ->has('passFailRatio')
            ->has('topExams')
            ->has('recentGrades')
        );
    }

    public function test_dashboard_shows_correct_counts(): void
    {
        Lesson::create(['title' => 'Math']);
        $classroom = Classroom::create(['title' => 'Class A']);
        
        Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '123',
            'name' => 'Student 1',
            'password' => bcrypt('pass'),
            'gender' => 'L',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('students', 1)
            ->where('classrooms', 1)
        );
    }

    public function test_dashboard_shows_grade_distribution(): void
    {
        $lesson = Lesson::create(['title' => 'Test']);
        $classroom = Classroom::create(['title' => 'Class']);
        
        $exam = Exam::create([
            'title' => 'Exam',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'title' => 'Session',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '456',
            'name' => 'Student',
            'password' => bcrypt('pass'),
            'gender' => 'L',
        ]);

        // Create grade with A (90+)
        Grade::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $student->id,
            'duration' => 0,
            'start_time' => now()->subMinutes(30),
            'end_time' => now(),
            'total_correct' => 9,
            'grade' => 95,
            'status' => 'passed',
        ]);

        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('gradeDistribution')
            ->where('passFailRatio.passed', 1)
            ->where('passFailRatio.failed', 0)
        );
    }

    public function test_exam_trend_returns_7_days_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('examTrend', 7)
        );
    }
}
