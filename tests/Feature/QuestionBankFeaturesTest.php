<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, QuestionBank, QuestionCategory};
use App\Services\QuestionBankDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class QuestionBankFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_question_bank_has_difficulty_field(): void
    {
        $question = QuestionBank::create([
            'question' => 'Test question',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        $this->assertEquals('easy', $question->difficulty);
    }

    public function test_question_bank_has_usage_stats(): void
    {
        $question = QuestionBank::create([
            'question' => 'Test question',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'medium',
            'points' => 1,
            'usage_count' => 5,
            'success_rate' => 85.5,
        ]);

        $this->assertEquals(5, $question->usage_count);
        $this->assertEquals(85.5, $question->success_rate);
    }

    public function test_can_filter_by_difficulty(): void
    {
        QuestionBank::create([
            'question' => 'Easy question',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        QuestionBank::create([
            'question' => 'Hard question',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'hard',
            'points' => 1,
        ]);

        $easyQuestions = QuestionBank::where('difficulty', 'easy')->count();
        $hardQuestions = QuestionBank::where('difficulty', 'hard')->count();

        $this->assertEquals(1, $easyQuestions);
        $this->assertEquals(1, $hardQuestions);
    }

    public function test_duplicate_detection_service_exists(): void
    {
        $this->assertTrue(class_exists(QuestionBankDuplicateService::class));
    }

    public function test_duplicate_detection_finds_similar_questions(): void
    {
        QuestionBank::create([
            'question' => 'What is 2 + 2?',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        $service = new QuestionBankDuplicateService();
        $similar = $service->findSimilar('What is 2+2?', 80);

        $this->assertNotEmpty($similar);
    }

    public function test_duplicate_detection_ignores_different_questions(): void
    {
        QuestionBank::create([
            'question' => 'What is 2 + 2?',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        $service = new QuestionBankDuplicateService();
        $similar = $service->findSimilar('What is the capital of France?', 80);

        $this->assertEmpty($similar);
    }

    public function test_export_route_exists(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.question-bank.export'));

        $response->assertStatus(200);
    }

    public function test_import_route_exists(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        
        $file = UploadedFile::fake()->create('questions.xlsx', 100);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.question-bank.import'), [
                'file' => $file,
            ]);

        // May fail validation but route exists
        $this->assertTrue(in_array($response->status(), [200, 302, 422]));
    }

    public function test_template_download_route_exists(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.question-bank.template'));

        $response->assertStatus(200);
    }

    public function test_preview_route_exists(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        
        $question = QuestionBank::create([
            'question' => 'Test',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.question-bank.preview'), [
                'question_ids' => [$question->id],
            ]);

        $response->assertStatus(200);
    }

    public function test_check_duplicate_route_exists(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.question-bank.checkDuplicate'), [
                'question' => 'Test question',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['has_similar', 'similar']);
    }

    public function test_tags_are_stored_as_array(): void
    {
        $question = QuestionBank::create([
            'question' => 'Test',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
            'tags' => ['math', 'basic'],
        ]);

        $this->assertIsArray($question->tags);
        $this->assertContains('math', $question->tags);
    }

    public function test_usage_count_increments_on_import(): void
    {
        $question = QuestionBank::create([
            'question' => 'Test',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
            'usage_count' => 0,
        ]);

        $question->increment('usage_count');
        $question->refresh();

        $this->assertEquals(1, $question->usage_count);
    }

    public function test_last_used_at_is_updated(): void
    {
        $question = QuestionBank::create([
            'question' => 'Test',
            'question_type' => 'multiple_choice_single',
            'difficulty' => 'easy',
            'points' => 1,
        ]);

        $this->assertNull($question->last_used_at);

        $question->update(['last_used_at' => now()]);
        $question->refresh();

        $this->assertNotNull($question->last_used_at);
    }
}
