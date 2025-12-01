<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\Question;
use App\Models\Classroom;
use App\Models\ExamSession;
use App\Models\ExamGroup;
use App\Models\Grade;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuestionTypesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $student;
    protected $exam;
    protected $examSession;
    protected $examGroup;
    protected $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        $lesson = Lesson::create(['title' => 'Test Lesson']);
        $classroom = Classroom::create(['title' => 'Test Class']);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);

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

        $this->examSession = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Session 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHours(2),
        ]);

        $this->examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'student_id' => $this->student->id,
        ]);

        $this->grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => now(),
            'total_correct' => 0,
            'grade' => 0,
        ]);
    }

    /** @test */
    public function admin_can_create_multiple_choice_single_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'What is 2+2?',
                'question_type' => 'multiple_choice_single',
                'points' => 1,
                'option_1' => '3',
                'option_2' => '4',
                'option_3' => '5',
                'answer' => 2,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'exam_id' => $this->exam->id,
            'question_type' => 'multiple_choice_single',
            'answer' => 2,
        ]);
    }

    /** @test */
    public function admin_can_create_multiple_choice_multiple_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'Select all prime numbers',
                'question_type' => 'multiple_choice_multiple',
                'points' => 2,
                'option_1' => '2',
                'option_2' => '3',
                'option_3' => '4',
                'option_4' => '5',
                'correct_answers' => [1, 2, 4],
            ]
        );

        $response->assertRedirect();
        $question = Question::where('exam_id', $this->exam->id)->first();
        $this->assertEquals('multiple_choice_multiple', $question->question_type);
        $this->assertEquals([1, 2, 4], $question->correct_answers);
    }

    /** @test */
    public function admin_can_create_true_false_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'The earth is flat.',
                'question_type' => 'true_false',
                'points' => 1,
                'answer' => 2, // False
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'exam_id' => $this->exam->id,
            'question_type' => 'true_false',
            'answer' => 2,
        ]);
    }

    /** @test */
    public function admin_can_create_short_answer_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'What is the capital of Indonesia?',
                'question_type' => 'short_answer',
                'points' => 1,
                'correct_answers' => ['jakarta', 'Jakarta', 'JAKARTA'],
            ]
        );

        $response->assertRedirect();
        $question = Question::where('exam_id', $this->exam->id)->first();
        $this->assertEquals('short_answer', $question->question_type);
        $this->assertContains('jakarta', $question->correct_answers);
    }

    /** @test */
    public function admin_can_create_essay_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'Explain the water cycle.',
                'question_type' => 'essay',
                'points' => 10,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('questions', [
            'exam_id' => $this->exam->id,
            'question_type' => 'essay',
            'points' => 10,
        ]);
    }

    /** @test */
    public function admin_can_create_matching_question()
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.exams.storeQuestion', $this->exam->id),
            [
                'question' => 'Match the countries with their capitals',
                'question_type' => 'matching',
                'points' => 4,
                'matching_pairs' => [
                    ['left' => 'Indonesia', 'right' => 'Jakarta'],
                    ['left' => 'Japan', 'right' => 'Tokyo'],
                    ['left' => 'France', 'right' => 'Paris'],
                    ['left' => 'Germany', 'right' => 'Berlin'],
                ],
            ]
        );

        $response->assertRedirect();
        $question = Question::where('exam_id', $this->exam->id)->first();
        $this->assertEquals('matching', $question->question_type);
        $this->assertCount(4, $question->matching_pairs);
    }

    /** @test */
    public function scoring_multiple_choice_single_correct()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'What is 2+2?',
            'question_type' => 'multiple_choice_single',
            'points' => 1,
            'option_1' => '3',
            'option_2' => '4',
            'answer' => 2,
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1,2',
            'answer' => 0,
            'is_correct' => 'N',
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'answer' => 2,
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertEquals('Y', $answer->is_correct);
        $this->assertEquals(1, $answer->points_awarded);
    }

    /** @test */
    public function scoring_true_false_correct()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'The earth is round.',
            'question_type' => 'true_false',
            'points' => 1,
            'answer' => 1, // True
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'is_correct' => 'N',
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'answer' => 1,
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertEquals('Y', $answer->is_correct);
        $this->assertEquals(1, $answer->points_awarded);
    }

    /** @test */
    public function scoring_short_answer_correct()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Capital of Indonesia?',
            'question_type' => 'short_answer',
            'points' => 1,
            'correct_answers' => ['jakarta', 'Jakarta'],
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'is_correct' => 'N',
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'answer_text' => 'JAKARTA',
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertEquals('Y', $answer->is_correct);
        $this->assertEquals(1, $answer->points_awarded);
    }

    /** @test */
    public function scoring_essay_needs_manual_review()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Explain photosynthesis.',
            'question_type' => 'essay',
            'points' => 10,
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'is_correct' => 'N',
            'needs_manual_review' => true,
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'answer_text' => 'Photosynthesis is the process...',
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertTrue($answer->needs_manual_review);
        $this->assertEquals(0, $answer->points_awarded);
    }

    /** @test */
    public function scoring_matching_all_correct()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Match countries with capitals',
            'question_type' => 'matching',
            'points' => 4,
            'matching_pairs' => [
                ['left' => 'Indonesia', 'right' => 'Jakarta'],
                ['left' => 'Japan', 'right' => 'Tokyo'],
            ],
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'is_correct' => 'N',
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'matching_answers' => [
                'Indonesia' => 'Jakarta',
                'Japan' => 'Tokyo',
            ],
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertEquals('Y', $answer->is_correct);
        $this->assertEquals(4, $answer->points_awarded);
    }

    /** @test */
    public function scoring_matching_partial_correct()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Match countries with capitals',
            'question_type' => 'matching',
            'points' => 4,
            'matching_pairs' => [
                ['left' => 'Indonesia', 'right' => 'Jakarta'],
                ['left' => 'Japan', 'right' => 'Tokyo'],
            ],
        ]);

        Answer::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'student_id' => $this->student->id,
            'question_order' => 1,
            'answer_order' => '1',
            'answer' => 0,
            'is_correct' => 'N',
        ]);

        $this->actingAs($this->student, 'student');

        $response = $this->post(route('student.exams.answerQuestion'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->examSession->id,
            'question_id' => $question->id,
            'matching_answers' => [
                'Indonesia' => 'Jakarta',
                'Japan' => 'Paris', // Wrong
            ],
        ]);

        $answer = Answer::where('question_id', $question->id)->first();
        $this->assertEquals('N', $answer->is_correct);
        $this->assertEquals(2, $answer->points_awarded); // 50% correct
    }

    /** @test */
    public function admin_can_update_question_type()
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Original question',
            'question_type' => 'multiple_choice_single',
            'points' => 1,
            'option_1' => 'A',
            'option_2' => 'B',
            'answer' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.exams.updateQuestion', [$this->exam->id, $question->id]),
            [
                'question' => 'Updated to true/false',
                'question_type' => 'true_false',
                'points' => 2,
                'answer' => 1,
            ]
        );

        $response->assertRedirect();
        $question->refresh();
        $this->assertEquals('true_false', $question->question_type);
        $this->assertEquals(2, $question->points);
    }
}
