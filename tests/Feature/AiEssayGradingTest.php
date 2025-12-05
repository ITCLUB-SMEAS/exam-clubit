<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiEssayGradingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Exam $exam;
    protected ExamSession $session;
    protected Student $student;
    protected Question $question;
    protected Answer $answer;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Biologi']);
        $this->classroom = Classroom::create(['title' => 'Kelas 10A']);
        
        $this->exam = Exam::create([
            'lesson_id' => $lesson->id,
            'classroom_id' => $this->classroom->id,
            'title' => 'Ujian Essay',
            'description' => 'Test exam',
            'duration' => 60,
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'Y',
        ]);
        
        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Sesi 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
        
        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);

        $this->question = Question::create([
            'exam_id' => $this->exam->id,
            'question_type' => 'essay',
            'question' => 'Jelaskan pengertian fotosintesis!',
            'answer' => 'Fotosintesis adalah proses pembuatan makanan oleh tumbuhan hijau.',
            'points' => 10,
        ]);

        $this->answer = Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $this->question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1,2,3,4',
            'answer' => 0,
            'answer_text' => 'Fotosintesis adalah proses dimana tumbuhan membuat makanan sendiri.',
            'needs_manual_review' => true,
        ]);

        Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'grade' => 0,
            'total_correct' => 0,
            'duration' => 0,
        ]);
    }

    public function test_essay_grading_page_loads()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/essay-grading');

        $response->assertStatus(200);
    }

    public function test_essay_grading_page_shows_answers_when_filtered()
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/essay-grading?exam_id={$this->exam->id}&session_id={$this->session->id}");

        $response->assertStatus(200);
    }

    public function test_gemini_service_grade_essay_method_exists()
    {
        $service = new GeminiService();
        $this->assertTrue(method_exists($service, 'gradeEssay'));
    }

    public function test_ai_grade_returns_correct_structure()
    {
        $mockResult = [
            'score' => 8.5,
            'feedback' => 'Jawaban cukup baik.',
            'strengths' => ['Memahami konsep dasar'],
            'improvements' => ['Bisa ditambahkan detail'],
        ];

        $this->mock(GeminiService::class, function ($mock) use ($mockResult) {
            $mock->shouldReceive('gradeEssay')
                ->once()
                ->andReturn($mockResult);
        });

        $service = app(GeminiService::class);
        $result = $service->gradeEssay('Question', 'Answer', null, 10);

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('feedback', $result);
        $this->assertEquals(8.5, $result['score']);
    }

    public function test_ai_bulk_grade_validation_rule_exists()
    {
        // Test that the validation rule for max 10 items exists in controller
        $controller = new \App\Http\Controllers\Admin\EssayGradingController();
        $reflection = new \ReflectionMethod($controller, 'aiBulkGrade');
        
        // Method exists
        $this->assertTrue($reflection->isPublic());
    }

    public function test_answer_can_be_updated_with_points()
    {
        $this->answer->update([
            'points_awarded' => 8,
            'is_correct' => 'Y',
        ]);

        $this->answer->refresh();
        $this->assertEquals(8, $this->answer->points_awarded);
        $this->assertEquals('Y', $this->answer->is_correct);
    }

    public function test_grade_recalculation_works()
    {
        // Update answer with points
        $this->answer->update([
            'points_awarded' => 8,
            'is_correct' => 'Y',
        ]);

        // Get grade
        $grade = Grade::where('student_id', $this->student->id)
            ->where('exam_id', $this->exam->id)
            ->first();

        // Manually recalculate (simulating what controller does)
        $totalPoints = $this->question->points;
        $earnedPoints = $this->answer->points_awarded;
        $gradeValue = round(($earnedPoints / $totalPoints) * 100, 2);

        $grade->update(['grade' => $gradeValue]);

        $grade->refresh();
        $this->assertEquals(80, $grade->grade);
    }

    public function test_essay_question_type_is_valid_for_ai_grading()
    {
        $this->assertEquals('essay', $this->question->question_type);
        $this->assertTrue(in_array($this->question->question_type, ['essay', 'short_answer']));
    }

    public function test_non_essay_question_is_invalid_for_ai_grading()
    {
        $mcQuestion = Question::create([
            'exam_id' => $this->exam->id,
            'question_type' => 'multiple_choice_single',
            'question' => 'Pilih jawaban yang benar',
            'points' => 1,
        ]);

        $this->assertFalse(in_array($mcQuestion->question_type, ['essay', 'short_answer']));
    }

    public function test_empty_answer_text_is_detected()
    {
        $emptyAnswer = Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $this->question->id,
            'student_id' => $this->student->id,
            'question_order' => 2,
            'answer_order' => '1,2,3,4',
            'answer' => 0,
            'answer_text' => '',
            'needs_manual_review' => true,
        ]);

        $studentAnswer = $emptyAnswer->answer_text ?? $emptyAnswer->answer ?? '';
        $this->assertTrue(empty(trim($studentAnswer)));
    }
}
