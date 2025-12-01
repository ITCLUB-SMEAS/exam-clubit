<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\Question;
use App\Models\Lesson;
use App\Models\Classroom;
use App\Services\DuplicateQuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateQuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    private DuplicateQuestionService $service;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DuplicateQuestionService();
        
        $lesson = Lesson::create(['title' => 'Test Lesson']);
        $classroom = Classroom::create(['title' => 'Test Class']);
        $this->exam = Exam::create([
            'title' => 'Test Exam',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);
    }

    public function test_no_duplicate_when_exam_has_no_questions(): void
    {
        $result = $this->service->checkDuplicate('Test question?', $this->exam->id);
        
        $this->assertFalse($result['is_duplicate']);
        $this->assertEquals(0, $result['similarity']);
    }

    public function test_detects_duplicate_question(): void
    {
        Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Siapa presiden pertama Indonesia?',
            'question_type' => 'multiple_choice_single',
            'answer' => 1,
        ]);

        $result = $this->service->checkDuplicate('Siapa presiden pertama Indonesia?', $this->exam->id);
        
        $this->assertTrue($result['is_duplicate']);
        $this->assertGreaterThanOrEqual(85, $result['similarity']);
    }

    public function test_bulk_check_detects_duplicates_in_batch(): void
    {
        $questions = [
            ['question' => 'Siapa presiden pertama Indonesia?'],
            ['question' => 'Siapa presiden pertama indonesia?'],
            ['question' => 'Apa warna bendera Indonesia?'],
        ];

        $result = $this->service->checkBulkDuplicates($questions, $this->exam->id);

        $this->assertEquals('ok', $result[0]['status']);
        $this->assertEquals('duplicate_batch', $result[1]['status']);
        $this->assertEquals('ok', $result[2]['status']);
    }

    public function test_excludes_current_question_when_updating(): void
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Siapa presiden pertama Indonesia?',
            'question_type' => 'multiple_choice_single',
            'answer' => 1,
        ]);

        // Should not detect itself as duplicate
        $result = $this->service->checkDuplicate(
            'Siapa presiden pertama Indonesia?',
            $this->exam->id,
            $question->id
        );
        
        $this->assertFalse($result['is_duplicate']);
    }
}
