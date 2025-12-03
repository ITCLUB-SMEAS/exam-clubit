<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use App\Services\BehaviorAnalysisService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BehaviorAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected BehaviorAnalysisService $service;
    protected Student $student;
    protected Exam $exam;
    protected ExamSession $session;
    protected Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BehaviorAnalysisService();

        $classroom = Classroom::create(['title' => 'Test Class']);
        $lesson = Lesson::create(['title' => 'Test Lesson']);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => 'password',
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

        $this->session = ExamSession::create([
            'title' => 'Test Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->subHour(),
            'end_time' => Carbon::now()->addHour(),
        ]);

        ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
        ]);

        $this->grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => Carbon::now()->subMinutes(30),
            'end_time' => Carbon::now(),
            'total_correct' => 0,
            'grade' => 0,
        ]);

        // Create questions
        for ($i = 1; $i <= 10; $i++) {
            Question::create([
                'exam_id' => $this->exam->id,
                'question' => "Question $i",
                'option_1' => 'A',
                'option_2' => 'B',
                'option_3' => 'C',
                'option_4' => 'D',
                'answer' => 1,
            ]);
        }
    }

    /** @test */
    public function it_detects_same_answer_pattern()
    {
        $questions = Question::where('exam_id', $this->exam->id)->get();
        $baseTime = Carbon::now()->subMinutes(20);

        // Create answers with same answer (all answer = 1)
        foreach ($questions as $index => $question) {
            Answer::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'question_id' => $question->id,
                'student_id' => $this->student->id,
                'question_order' => $index + 1,
                'answer_order' => '1,2,3,4',
                'answer' => 1, // All same answer
                'is_correct' => 'Y',
                'updated_at' => $baseTime->copy()->addMinutes($index),
            ]);
        }

        $this->grade->load('exam');
        $flags = $this->service->analyzeExamCompletion($this->grade);

        $this->assertNotEmpty($flags);
        $this->assertTrue(
            collect($flags)->contains('type', 'same_answer_pattern'),
            'Should detect same answer pattern'
        );
    }

    /** @test */
    public function it_detects_fast_completion()
    {
        $questions = Question::where('exam_id', $this->exam->id)->get();
        $baseTime = Carbon::now()->subMinutes(5);

        // Create answers with very fast intervals (1 second apart)
        foreach ($questions as $index => $question) {
            Answer::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'question_id' => $question->id,
                'student_id' => $this->student->id,
                'question_order' => $index + 1,
                'answer_order' => '1,2,3,4',
                'answer' => ($index % 4) + 1, // Varied answers
                'is_correct' => 'N',
                'updated_at' => $baseTime->copy()->addSeconds($index), // 1 second intervals
            ]);
        }

        $this->grade->load('exam');
        $flags = $this->service->analyzeExamCompletion($this->grade);

        $this->assertNotEmpty($flags);
        $this->assertTrue(
            collect($flags)->contains('type', 'fast_completion'),
            'Should detect fast completion'
        );
    }

    /** @test */
    public function it_does_not_flag_normal_behavior()
    {
        // Update grade to reflect normal completion time (45 minutes for 60 min exam)
        $this->grade->update([
            'start_time' => Carbon::now()->subMinutes(45),
            'end_time' => Carbon::now(),
            'grade' => 70, // Not perfect score
        ]);

        $questions = Question::where('exam_id', $this->exam->id)->get();
        $baseTime = Carbon::now()->subMinutes(45);

        // Create answers with normal intervals (4 minutes apart) and varied answers
        foreach ($questions as $index => $question) {
            $answer = Answer::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'question_id' => $question->id,
                'student_id' => $this->student->id,
                'question_order' => $index + 1,
                'answer_order' => '1,2,3,4',
                'answer' => ($index % 4) + 1, // Varied answers: 1,2,3,4,1,2,3,4,1,2
                'is_correct' => $index % 2 == 0 ? 'Y' : 'N',
            ]);
            
            // Manually update timestamps using query builder to bypass Eloquent
            \DB::table('answers')
                ->where('id', $answer->id)
                ->update(['updated_at' => $baseTime->copy()->addMinutes($index * 4)]);
        }

        $this->grade->load('exam');
        $flags = $this->service->analyzeExamCompletion($this->grade);

        $this->assertEmpty($flags, 'Should not flag normal behavior');
    }

    /** @test */
    public function it_flags_grade_on_high_severity()
    {
        $questions = Question::where('exam_id', $this->exam->id)->get();
        $baseTime = Carbon::now()->subMinutes(5);

        // Create answers with very fast intervals to trigger high severity
        foreach ($questions as $index => $question) {
            Answer::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'question_id' => $question->id,
                'student_id' => $this->student->id,
                'question_order' => $index + 1,
                'answer_order' => '1,2,3,4',
                'answer' => 1,
                'is_correct' => 'N',
                'updated_at' => $baseTime->copy()->addSeconds($index),
            ]);
        }

        $this->grade->load('exam');
        $this->service->analyzeExamCompletion($this->grade);

        $this->grade->refresh();
        $this->assertTrue($this->grade->is_flagged, 'Grade should be flagged');
        $this->assertNotNull($this->grade->flag_reason);
    }
}
