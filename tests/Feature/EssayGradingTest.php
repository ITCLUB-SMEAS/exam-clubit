<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Grade;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EssayGradingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Exam $exam;
    private ExamSession $session;
    private Student $student;
    private Question $essayQuestion;
    private Answer $answer;
    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Bahasa Indonesia']);
        $classroom = Classroom::create(['title' => 'Kelas 10A']);
        
        $this->exam = Exam::create([
            'title' => 'UTS Essay',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
            'passing_grade' => 70,
        ]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Sesi 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '1234567890',
            'name' => 'Budi',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);

        $this->essayQuestion = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Jelaskan pengertian puisi!',
            'question_type' => 'essay',
            'points' => 10,
        ]);

        $this->answer = Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $this->essayQuestion->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'answer_text' => 'Puisi adalah karya sastra yang menggunakan bahasa indah.',
            'needs_manual_review' => true,
        ]);

        $this->grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'duration' => 0,
            'start_time' => now()->subMinutes(30),
            'end_time' => now(),
            'total_correct' => 0,
            'grade' => 0,
        ]);
    }

    public function test_admin_can_access_essay_grading_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/essay-grading');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/EssayGrading/Index')
            ->has('exams')
            ->has('pendingCount')
        );
    }

    public function test_admin_can_grade_single_essay(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/essay-grading/{$this->answer->id}", [
                'points' => 8,
            ]);

        $response->assertRedirect();

        $this->answer->refresh();
        $this->assertEquals(8, $this->answer->points_awarded);
        $this->assertEquals('Y', $this->answer->is_correct);
    }

    public function test_grade_recalculates_student_total(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/essay-grading/{$this->answer->id}", [
                'points' => 7,
            ]);

        $this->grade->refresh();
        $this->assertEquals(70, $this->grade->grade); // 7/10 * 100
        $this->assertEquals('passed', $this->grade->status);
    }

    public function test_admin_can_bulk_grade_essays(): void
    {
        // Create another answer
        $answer2 = Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $this->essayQuestion->id,
            'student_id' => $this->student->id,
            'question_order' => 2,
            'answer_order' => '1',
            'answer' => 0,
            'answer_text' => 'Jawaban kedua',
            'needs_manual_review' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post('/admin/essay-grading-bulk', [
                'grades' => [
                    ['answer_id' => $this->answer->id, 'points' => 8],
                    ['answer_id' => $answer2->id, 'points' => 6],
                ],
            ]);

        $response->assertRedirect();

        $this->answer->refresh();
        $answer2->refresh();

        $this->assertEquals(8, $this->answer->points_awarded);
        $this->assertEquals(6, $answer2->points_awarded);
    }

    public function test_points_cannot_exceed_max(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/essay-grading/{$this->answer->id}", [
                'points' => 100, // Max is 10
            ]);

        $this->answer->refresh();
        $this->assertEquals(10, $this->answer->points_awarded); // Capped at max
    }

    public function test_filter_by_exam_and_session(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/essay-grading?exam_id={$this->exam->id}&session_id={$this->session->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('answers.data', 1)
        );
    }
}
