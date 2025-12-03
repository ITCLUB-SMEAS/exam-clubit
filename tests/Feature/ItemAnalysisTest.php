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
use App\Models\User;
use App\Services\ItemAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Exam $exam;
    protected ExamSession $session;
    protected Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Matematika']);
        $this->classroom = Classroom::create(['title' => 'XII IPA 1']);
        
        $this->exam = Exam::create([
            'title' => 'UTS Matematika',
            'lesson_id' => $lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test exam',
        ]);
        
        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'title' => 'Sesi 1',
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);
    }

    public function test_admin_can_access_item_analysis_page(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/exams/{$this->exam->id}/analysis");
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Admin/ItemAnalysis/Show'));
    }

    public function test_analysis_shows_no_data_when_no_completed_exams(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/exams/{$this->exam->id}/analysis");
        $response->assertInertia(fn ($page) => $page
            ->has('analysis', 0)
            ->where('total_students', 0)
        );
    }

    public function test_analysis_calculates_difficulty_index(): void
    {
        $this->createExamData();
        
        $service = new ItemAnalysisService();
        $result = $service->analyzeExam($this->exam);
        
        $this->assertNotEmpty($result['questions']);
        $this->assertArrayHasKey('difficulty_index', $result['questions'][0]);
        $this->assertGreaterThanOrEqual(0, $result['questions'][0]['difficulty_index']);
        $this->assertLessThanOrEqual(1, $result['questions'][0]['difficulty_index']);
    }

    public function test_analysis_calculates_discrimination_index(): void
    {
        $this->createExamData();
        
        $service = new ItemAnalysisService();
        $result = $service->analyzeExam($this->exam);
        
        $this->assertArrayHasKey('discrimination_index', $result['questions'][0]);
    }

    public function test_analysis_provides_recommendations(): void
    {
        $this->createExamData();
        
        $service = new ItemAnalysisService();
        $result = $service->analyzeExam($this->exam);
        
        $this->assertArrayHasKey('recommendation', $result['questions'][0]);
        $this->assertNotEmpty($result['questions'][0]['recommendation']);
    }

    public function test_analysis_generates_summary(): void
    {
        $this->createExamData();
        
        $service = new ItemAnalysisService();
        $result = $service->analyzeExam($this->exam);
        
        $this->assertNotNull($result['summary']);
        $this->assertArrayHasKey('total_questions', $result['summary']);
        $this->assertArrayHasKey('good_questions', $result['summary']);
        $this->assertArrayHasKey('needs_revision', $result['summary']);
    }

    public function test_distractor_analysis_for_multiple_choice(): void
    {
        $this->createExamData();
        
        $service = new ItemAnalysisService();
        $result = $service->analyzeExam($this->exam);
        
        $this->assertNotEmpty($result['questions'][0]['distractors']);
    }

    protected function createExamData(): void
    {
        $question = Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Berapa 2 + 2?',
            'question_type' => 'multiple_choice_single',
            'option_1' => '4',
            'option_2' => '5',
            'option_3' => '6',
            'option_4' => '7',
            'answer' => 1,
        ]);
        
        // Create 10 students with varying scores
        for ($i = 0; $i < 10; $i++) {
            $student = Student::create([
                'nisn' => '100000000' . $i,
                'name' => 'Student ' . $i,
                'classroom_id' => $this->classroom->id,
                'gender' => 'L',
                'password' => bcrypt('password'),
            ]);
            
            ExamGroup::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'student_id' => $student->id,
            ]);
            
            Grade::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'student_id' => $student->id,
                'grade' => 100 - ($i * 10),
                'total_correct' => $i < 5 ? 1 : 0,
                'start_time' => now()->subMinutes(30),
                'end_time' => now(),
                'duration' => 0,
            ]);
            
            Answer::create([
                'exam_id' => $this->exam->id,
                'exam_session_id' => $this->session->id,
                'student_id' => $student->id,
                'question_id' => $question->id,
                'question_order' => 1,
                'answer_order' => '1,2,3,4',
                'answer' => $i < 5 ? 1 : 2,
                'is_correct' => $i < 5 ? 'Y' : 'N',
            ]);
        }
    }
}
