<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Classroom;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamPreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $lesson = Lesson::create(['title' => 'Matematika']);
        $classroom = Classroom::create(['title' => 'Kelas 10A']);
        
        $this->exam = Exam::create([
            'title' => 'UTS Matematika',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);
    }

    public function test_admin_can_access_exam_preview(): void
    {
        $response = $this->actingAs($this->admin)
            ->get("/admin/exams/{$this->exam->id}/preview");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Exams/Preview')
            ->has('exam')
            ->has('questions')
        );
    }

    public function test_preview_shows_all_question_types(): void
    {
        // Create different question types
        Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Pilihan ganda?',
            'question_type' => 'multiple_choice_single',
            'option_1' => 'A', 'option_2' => 'B', 'option_3' => 'C',
            'answer' => 1,
            'points' => 2,
        ]);

        Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Benar atau salah?',
            'question_type' => 'true_false',
            'answer' => 1,
            'points' => 1,
        ]);

        Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Jelaskan!',
            'question_type' => 'essay',
            'points' => 10,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/exams/{$this->exam->id}/preview");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('questions', 3)
        );
    }

    public function test_preview_includes_correct_answers(): void
    {
        Question::create([
            'exam_id' => $this->exam->id,
            'question' => 'Test?',
            'question_type' => 'multiple_choice_single',
            'option_1' => 'Jawaban A',
            'option_2' => 'Jawaban B',
            'answer' => 1,
            'points' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->get("/admin/exams/{$this->exam->id}/preview");

        $response->assertInertia(fn ($page) => $page
            ->has('questions.0.answer')
        );
    }

    public function test_unauthenticated_cannot_preview(): void
    {
        $response = $this->get("/admin/exams/{$this->exam->id}/preview");
        $response->assertRedirect('/login');
    }
}
