<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Classroom;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $lesson;
    protected $classroom;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->lesson = Lesson::create(['title' => 'Matematika']);
        $this->classroom = Classroom::create(['title' => 'Kelas 10']);
    }

    /** @test */
    public function admin_can_create_question_category()
    {
        $response = $this->actingAs($this->admin)->post('/admin/question-categories', [
            'name' => 'Aljabar',
            'description' => 'Soal-soal aljabar',
            'lesson_id' => $this->lesson->id,
        ]);

        $response->assertRedirect('/admin/question-categories');
        $this->assertDatabaseHas('question_categories', ['name' => 'Aljabar']);
    }

    /** @test */
    public function admin_can_create_question_in_bank()
    {
        $category = QuestionCategory::create(['name' => 'Geometri']);

        $response = $this->actingAs($this->admin)->post('/admin/question-bank', [
            'category_id' => $category->id,
            'question' => 'Berapakah 2 + 2?',
            'question_type' => 'multiple_choice_single',
            'points' => 5,
            'option_1' => '3',
            'option_2' => '4',
            'option_3' => '5',
            'answer' => '2',
        ]);

        $response->assertRedirect('/admin/question-bank');
        $this->assertDatabaseHas('question_banks', ['question' => 'Berapakah 2 + 2?']);
    }

    /** @test */
    public function admin_can_import_questions_from_bank_to_exam()
    {
        $exam = Exam::create([
            'title' => 'Ujian Matematika',
            'lesson_id' => $this->lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);

        $bankQuestion1 = QuestionBank::create([
            'question' => 'Soal Bank 1',
            'question_type' => 'multiple_choice_single',
            'points' => 5,
            'option_1' => 'A', 'option_2' => 'B',
            'answer' => '1',
        ]);

        $bankQuestion2 = QuestionBank::create([
            'question' => 'Soal Bank 2',
            'question_type' => 'true_false',
            'points' => 3,
            'answer' => 'true',
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/exams/{$exam->id}/import-from-bank", [
            'question_ids' => [$bankQuestion1->id, $bankQuestion2->id],
        ]);

        $response->assertRedirect("/admin/exams/{$exam->id}");
        $this->assertEquals(2, $exam->questions()->count());
        $this->assertDatabaseHas('questions', ['exam_id' => $exam->id, 'question' => 'Soal Bank 1']);
        $this->assertDatabaseHas('questions', ['exam_id' => $exam->id, 'question' => 'Soal Bank 2']);
    }

    /** @test */
    public function admin_can_update_question_category()
    {
        $category = QuestionCategory::create(['name' => 'Old Name']);

        $response = $this->actingAs($this->admin)->put("/admin/question-categories/{$category->id}", [
            'name' => 'New Name',
        ]);

        $response->assertRedirect('/admin/question-categories');
        $this->assertDatabaseHas('question_categories', ['name' => 'New Name']);
    }

    /** @test */
    public function admin_can_delete_question_from_bank()
    {
        $question = QuestionBank::create([
            'question' => 'To be deleted',
            'question_type' => 'essay',
            'points' => 10,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/question-bank/{$question->id}");

        $response->assertRedirect('/admin/question-bank');
        $this->assertDatabaseMissing('question_banks', ['id' => $question->id]);
    }
}
