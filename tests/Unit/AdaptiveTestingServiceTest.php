<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AdaptiveTestingService;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Student;
use App\Models\ExamSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdaptiveTestingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdaptiveTestingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdaptiveTestingService();
    }

    public function test_initial_ability_estimate_is_zero()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['adaptive_mode' => true]);
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);
        $grade = Grade::factory()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
        ]);

        $ability = $this->service->estimateAbility($grade);
        
        $this->assertEquals(0.0, $ability);
    }

    public function test_ability_increases_with_correct_answers()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['adaptive_mode' => true]);
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);
        $grade = Grade::factory()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
        ]);

        // Create questions with correct answers
        for ($i = 0; $i < 5; $i++) {
            $question = Question::factory()->create([
                'exam_id' => $exam->id,
                'difficulty' => 'medium',
                'answer' => 1,
            ]);
            
            Answer::create([
                'student_id' => $student->id,
                'exam_id' => $exam->id,
                'exam_session_id' => $session->id,
                'question_id' => $question->id,
                'answer' => 1, // Correct
                'is_correct' => true,
            ]);
        }

        $ability = $this->service->estimateAbility($grade);
        
        $this->assertGreaterThan(0, $ability);
    }

    public function test_recommended_difficulty_based_on_ability()
    {
        $student = Student::factory()->create();
        $exam = Exam::factory()->create(['adaptive_mode' => true]);
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);
        $grade = Grade::factory()->create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
        ]);

        // Initial should be medium
        $difficulty = $this->service->getRecommendedDifficulty($grade);
        $this->assertEquals('medium', $difficulty);
    }

    public function test_get_next_question_returns_null_for_non_adaptive()
    {
        $exam = Exam::factory()->create(['adaptive_mode' => false]);
        $session = ExamSession::factory()->create(['exam_id' => $exam->id]);
        $grade = Grade::factory()->create([
            'exam_id' => $exam->id,
            'exam_session_id' => $session->id,
        ]);

        $question = $this->service->getNextQuestion($grade);
        
        $this->assertNull($question);
    }
}
