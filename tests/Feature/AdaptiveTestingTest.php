<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\Student;
use App\Models\Question;
use App\Models\ExamSession;
use App\Services\AdaptiveTestingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdaptiveTestingTest extends TestCase
{
    use RefreshDatabase;

    protected AdaptiveTestingService $service;
    protected Exam $exam;
    protected Student $student;
    protected ExamSession $session;
    protected Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdaptiveTestingService();
        
        $this->exam = Exam::factory()->create(['adaptive_mode' => true]);
        $this->student = Student::factory()->create();
        $this->session = ExamSession::factory()->create(['exam_id' => $this->exam->id]);
        $this->grade = Grade::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'exam_session_id' => $this->session->id,
        ]);
    }

    protected function createAnswer(Question $question, $answer, bool $isCorrect, int $order = 1): Answer
    {
        return Answer::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $question->id,
            'question_order' => $order,
            'answer_order' => 1,
            'answer' => $answer,
            'is_correct' => $isCorrect ? 'Y' : 'N',
        ]);
    }

    public function test_get_next_question_returns_null_when_adaptive_mode_disabled()
    {
        $this->exam->update(['adaptive_mode' => false]);
        Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'easy']);
        
        $this->assertNull($this->service->getNextQuestion($this->grade));
    }

    public function test_get_next_question_returns_null_when_all_questions_answered()
    {
        $question = Question::factory()->create(['exam_id' => $this->exam->id]);
        
        $this->assertNull($this->service->getNextQuestion($this->grade, [$question->id]));
    }

    public function test_get_next_question_returns_medium_difficulty_for_new_student()
    {
        Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'easy']);
        Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'medium']);
        Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'hard']);
        
        $result = $this->service->getNextQuestion($this->grade);
        
        $this->assertNotNull($result);
        $this->assertEquals('medium', $result->difficulty);
    }

    public function test_estimate_ability_returns_zero_for_no_answers()
    {
        $this->assertEquals(0.0, $this->service->estimateAbility($this->grade));
    }

    public function test_estimate_ability_increases_with_correct_answers()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'difficulty' => 'medium',
            'answer' => 1,
        ]);
        
        $this->createAnswer($question, 1, true);
        
        $this->assertGreaterThan(0, $this->service->estimateAbility($this->grade));
    }

    public function test_estimate_ability_decreases_with_wrong_answers()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'difficulty' => 'medium',
            'answer' => 1,
        ]);
        
        $this->createAnswer($question, 2, false);
        
        $this->assertLessThan(0, $this->service->estimateAbility($this->grade));
    }

    public function test_recommended_difficulty_easy_for_low_ability()
    {
        for ($i = 0; $i < 3; $i++) {
            $question = Question::factory()->create([
                'exam_id' => $this->exam->id,
                'difficulty' => 'medium',
                'answer' => 1,
            ]);
            $this->createAnswer($question, 2, false, $i + 1);
        }
        
        $this->assertEquals('easy', $this->service->getRecommendedDifficulty($this->grade));
    }

    public function test_recommended_difficulty_hard_for_high_ability()
    {
        for ($i = 0; $i < 3; $i++) {
            $question = Question::factory()->create([
                'exam_id' => $this->exam->id,
                'difficulty' => 'hard',
                'answer' => 1,
            ]);
            $this->createAnswer($question, 1, true, $i + 1);
        }
        
        $this->assertEquals('hard', $this->service->getRecommendedDifficulty($this->grade));
    }

    public function test_calculate_adaptive_score_returns_correct_structure()
    {
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'difficulty' => 'medium',
            'points' => 10,
            'answer' => 1,
        ]);
        
        $this->createAnswer($question, 1, true);
        
        $result = $this->service->calculateAdaptiveScore($this->grade);
        
        $this->assertArrayHasKey('raw_score', $result);
        $this->assertArrayHasKey('max_score', $result);
        $this->assertArrayHasKey('percentage', $result);
        $this->assertArrayHasKey('ability_estimate', $result);
        $this->assertArrayHasKey('ability_level', $result);
    }

    public function test_adaptive_score_applies_difficulty_multiplier()
    {
        $easyQ = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'difficulty' => 'easy',
            'points' => 10,
            'answer' => 1,
        ]);
        
        $hardQ = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'difficulty' => 'hard',
            'points' => 10,
            'answer' => 1,
        ]);
        
        $this->createAnswer($easyQ, 1, true, 1);
        $this->createAnswer($hardQ, 1, true, 2);
        
        $result = $this->service->calculateAdaptiveScore($this->grade);
        
        // Easy: 10 * 1.0 = 10, Hard: 10 * 1.5 = 15, Total = 25
        $this->assertEquals(25, $result['max_score']);
        $this->assertEquals(25, $result['raw_score']);
        $this->assertEquals(100, $result['percentage']);
    }

    public function test_next_question_selects_easier_after_wrong_answers()
    {
        $easyQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'easy', 'answer' => 1]);
        $mediumQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'medium', 'answer' => 1]);
        $hardQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'hard', 'answer' => 1]);
        
        $this->createAnswer($mediumQ, 2, false);
        
        $nextQuestion = $this->service->getNextQuestion($this->grade, [$mediumQ->id]);
        
        $this->assertEquals('easy', $nextQuestion->difficulty);
    }

    public function test_next_question_selects_harder_after_correct_answers()
    {
        $easyQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'easy', 'answer' => 1]);
        $mediumQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'medium', 'answer' => 1]);
        $hardQ = Question::factory()->create(['exam_id' => $this->exam->id, 'difficulty' => 'hard', 'answer' => 1]);
        
        $this->createAnswer($mediumQ, 1, true);
        
        $nextQuestion = $this->service->getNextQuestion($this->grade, [$mediumQ->id]);
        
        $this->assertEquals('hard', $nextQuestion->difficulty);
    }

    public function test_ability_level_perlu_bimbingan()
    {
        for ($i = 0; $i < 5; $i++) {
            $q = Question::factory()->create([
                'exam_id' => $this->exam->id,
                'difficulty' => 'hard',
                'answer' => 1,
            ]);
            $this->createAnswer($q, 2, false, $i + 1);
        }
        
        $result = $this->service->calculateAdaptiveScore($this->grade);
        
        $this->assertEquals('Perlu Bimbingan', $result['ability_level']);
    }

    public function test_perfect_score_gives_sangat_baik_level()
    {
        for ($i = 0; $i < 5; $i++) {
            $q = Question::factory()->create([
                'exam_id' => $this->exam->id,
                'difficulty' => 'hard',
                'answer' => 1,
            ]);
            $this->createAnswer($q, 1, true, $i + 1);
        }
        
        $result = $this->service->calculateAdaptiveScore($this->grade);
        
        $this->assertEquals('Sangat Baik', $result['ability_level']);
    }
}
