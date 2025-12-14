<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamGroup;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OfflineExamSyncTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Exam $exam;
    protected ExamSession $session;
    protected ExamGroup $examGroup;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->student = Student::factory()->create();
        $this->exam = Exam::factory()->create();
        $this->session = ExamSession::factory()->create(['exam_id' => $this->exam->id]);
        $this->examGroup = ExamGroup::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_sync()
    {
        $response = $this->postJson('/student/exam-sync', [
            'answers' => []
        ]);

        $response->assertStatus(401);
    }

    public function test_student_can_sync_answers()
    {
        $question = Question::factory()->create(['exam_id' => $this->exam->id]);
        
        // Create existing answer
        $answer = Answer::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'question_id' => $question->id,
            'answer' => 0,
            'question_order' => 1,
        ]);

        $response = $this->actingAs($this->student, 'student')
            ->postJson('/student/exam-sync', [
                'answers' => [
                    [
                        'examGroupId' => $this->examGroup->id,
                        'examId' => $this->exam->id,
                        'questionId' => $question->id,
                        'answer' => 2,
                    ]
                ]
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify answer was updated
        $this->assertEquals(2, $answer->fresh()->answer);
    }

    public function test_student_can_get_exam_for_offline()
    {
        $response = $this->actingAs($this->student, 'student')
            ->getJson("/student/exam-offline/{$this->examGroup->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'examGroup',
                'answers',
                'cachedAt',
            ]);
    }

    public function test_student_cannot_get_other_student_exam()
    {
        $otherStudent = Student::factory()->create();
        
        $response = $this->actingAs($otherStudent, 'student')
            ->getJson("/student/exam-offline/{$this->examGroup->id}");

        $response->assertStatus(404);
    }

    public function test_sync_validates_required_fields()
    {
        $response = $this->actingAs($this->student, 'student')
            ->postJson('/student/exam-sync', [
                'answers' => [
                    ['invalid' => 'data']
                ]
            ]);

        $response->assertStatus(422);
    }
}
