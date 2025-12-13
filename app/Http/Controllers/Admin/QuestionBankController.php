<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\Question;
use App\Models\Exam;
use App\Exports\QuestionBankExport;
use App\Imports\QuestionBankImport;
use App\Services\QuestionBankDuplicateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class QuestionBankController extends Controller
{
    protected function getCategories()
    {
        return Cache::remember('question_categories_with_count', 300, fn() => 
            QuestionCategory::withCount('questions')->get()
        );
    }

    public function index(Request $request)
    {
        $query = QuestionBank::with('category:id,name');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->question_type) {
            $query->where('question_type', $request->question_type);
        }
        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->search) {
            $query->where('question', 'like', "%{$request->search}%");
        }
        if ($request->tags) {
            $query->whereJsonContains('tags', $request->tags);
        }

        // Sort by usage or date
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $questions = $query->latest()->paginate(10)->withQueryString();
        $categories = $this->getCategories();

        // Get popular tags
        $popularTags = QuestionBank::whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->keys();

        return inertia('Admin/QuestionBank/Index', compact('questions', 'categories', 'popularTags'));
    }

    public function create()
    {
        $categories = $this->getCategories();
        return inertia('Admin/QuestionBank/Create', compact('categories'));
    }

    public function store(Request $request, QuestionBankDuplicateService $duplicateService)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:question_categories,id',
            'question' => 'required|string',
            'question_type' => 'required|in:multiple_choice_single,multiple_choice_multiple,true_false,short_answer,essay,matching',
            'difficulty' => 'required|in:easy,medium,hard',
            'points' => 'required|numeric|min:0',
            'option_1' => 'nullable|string',
            'option_2' => 'nullable|string',
            'option_3' => 'nullable|string',
            'option_4' => 'nullable|string',
            'option_5' => 'nullable|string',
            'answer' => 'nullable',
            'correct_answers' => 'nullable|array',
            'matching_pairs' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        // Check for duplicates
        $similar = $duplicateService->findSimilar($validated['question']);
        
        if (!empty($similar) && !$request->force_create) {
            return back()->with('warning', [
                'message' => 'Ditemukan soal yang mirip',
                'similar' => $similar,
            ]);
        }

        QuestionBank::create($validated);

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Soal berhasil ditambahkan ke bank soal');
    }

    public function edit(QuestionBank $questionBank)
    {
        return inertia('Admin/QuestionBank/Edit', [
            'question' => $questionBank,
            'categories' => $this->getCategories(),
        ]);
    }

    public function update(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:question_categories,id',
            'question' => 'required|string',
            'question_type' => 'required|in:multiple_choice_single,multiple_choice_multiple,true_false,short_answer,essay,matching',
            'difficulty' => 'required|in:easy,medium,hard',
            'points' => 'required|numeric|min:0',
            'option_1' => 'nullable|string',
            'option_2' => 'nullable|string',
            'option_3' => 'nullable|string',
            'option_4' => 'nullable|string',
            'option_5' => 'nullable|string',
            'answer' => 'nullable',
            'correct_answers' => 'nullable|array',
            'matching_pairs' => 'nullable|array',
            'tags' => 'nullable|array',
        ]);

        $questionBank->update($validated);

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Soal berhasil diupdate');
    }

    public function destroy(QuestionBank $questionBank)
    {
        $questionBank->delete();

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Soal berhasil dihapus');
    }

    public function importToExam(Request $request, Exam $exam)
    {
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:question_banks,id',
        ]);

        $bankQuestions = QuestionBank::whereIn('id', $request->question_ids)->get();

        foreach ($bankQuestions as $bq) {
            Question::create([
                'exam_id' => $exam->id,
                'question' => $bq->question,
                'question_type' => $bq->question_type,
                'difficulty' => $bq->difficulty,
                'points' => $bq->points,
                'option_1' => $bq->option_1,
                'option_2' => $bq->option_2,
                'option_3' => $bq->option_3,
                'option_4' => $bq->option_4,
                'option_5' => $bq->option_5,
                'answer' => $bq->answer,
                'correct_answers' => $bq->correct_answers,
                'matching_pairs' => $bq->matching_pairs,
            ]);

            // Update usage stats
            $bq->increment('usage_count');
            $bq->update(['last_used_at' => now()]);
        }

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', count($bankQuestions) . ' soal berhasil diimport');
    }

    public function getQuestions(Request $request)
    {
        $query = QuestionBank::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->difficulty) {
            $query->where('difficulty', $request->difficulty);
        }

        return response()->json($query->get());
    }

    public function export(Request $request)
    {
        $filters = $request->only(['category_id', 'difficulty', 'question_type']);
        
        return Excel::download(
            new QuestionBankExport($filters),
            'question-bank-' . date('Y-m-d') . '.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            Excel::import(new QuestionBankImport, $request->file('file'));

            return redirect()->route('admin.question-bank.index')
                ->with('success', 'Soal berhasil diimport dari Excel');
        } catch (\Exception $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }
    }

    public function preview(Request $request)
    {
        $questionIds = $request->input('question_ids', []);
        $questions = QuestionBank::with('category')->whereIn('id', $questionIds)->get();

        return response()->json($questions);
    }

    public function checkDuplicate(Request $request, QuestionBankDuplicateService $duplicateService)
    {
        $question = $request->input('question');
        $similar = $duplicateService->findSimilar($question);

        return response()->json([
            'has_similar' => !empty($similar),
            'similar' => $similar,
        ]);
    }

    public function downloadTemplate()
    {
        $headers = [
            'Kategori',
            'Soal',
            'Tipe',
            'Tingkat Kesulitan',
            'Poin',
            'Opsi 1',
            'Opsi 2',
            'Opsi 3',
            'Opsi 4',
            'Opsi 5',
            'Jawaban',
            'Jawaban Benar (Multiple)',
            'Pasangan (Matching)',
            'Tags',
        ];

        $example = [
            'Matematika',
            'Berapa hasil dari 2 + 2?',
            'multiple_choice_single',
            'easy',
            '1',
            '3',
            '4',
            '5',
            '6',
            '',
            'B',
            '',
            '',
            'matematika,dasar',
        ];

        $data = [$headers, $example];

        return Excel::download(
            new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
                protected $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
            },
            'template-question-bank.xlsx'
        );
    }

    // Import questions from existing exam to bank
    public function importFromExam(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'question_ids' => 'nullable|array',
            'category_id' => 'nullable|exists:question_categories,id',
        ]);

        $query = Question::where('exam_id', $request->exam_id);
        
        if ($request->question_ids) {
            $query->whereIn('id', $request->question_ids);
        }

        $questions = $query->get();
        $imported = 0;

        foreach ($questions as $q) {
            QuestionBank::create([
                'category_id' => $request->category_id,
                'question' => $q->question,
                'question_type' => $q->question_type,
                'difficulty' => $q->difficulty ?? 'medium',
                'points' => $q->points,
                'option_1' => $q->option_1,
                'option_2' => $q->option_2,
                'option_3' => $q->option_3,
                'option_4' => $q->option_4,
                'option_5' => $q->option_5,
                'answer' => $q->answer,
                'correct_answers' => $q->correct_answers,
                'matching_pairs' => $q->matching_pairs,
            ]);
            $imported++;
        }

        return back()->with('success', "$imported soal berhasil diimport ke bank soal");
    }

    // Get exams for import modal
    public function getExamsForImport()
    {
        $exams = Exam::withCount('questions')->having('questions_count', '>', 0)->get(['id', 'title']);
        return response()->json($exams);
    }

    // Get questions from exam for selection
    public function getExamQuestions(Exam $exam)
    {
        return response()->json($exam->questions()->select('id', 'question', 'question_type', 'points')->get());
    }

    // Bulk delete
    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1']);
        $deleted = QuestionBank::whereIn('id', $request->ids)->delete();
        return back()->with('success', "$deleted soal berhasil dihapus");
    }

    // Bulk update tags
    public function bulkUpdateTags(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'tags' => 'required|array',
            'mode' => 'required|in:replace,append',
        ]);

        $questions = QuestionBank::whereIn('id', $request->ids)->get();
        
        foreach ($questions as $q) {
            if ($request->mode === 'replace') {
                $q->tags = $request->tags;
            } else {
                $q->tags = array_unique(array_merge($q->tags ?? [], $request->tags));
            }
            $q->save();
        }

        return back()->with('success', count($questions) . ' soal berhasil diupdate');
    }

    // AI Generate Tags
    public function generateTags(Request $request, \App\Services\GeminiService $gemini)
    {
        $request->validate([
            'question' => 'required|string|min:10',
            'category' => 'nullable|string',
        ]);

        $tags = $gemini->generateTags(
            strip_tags($request->question),
            $request->category
        );

        if (!$tags) {
            return response()->json(['error' => 'Gagal generate tags. Coba lagi.'], 500);
        }

        return response()->json(['tags' => $tags]);
    }

    // Get question statistics
    public function statistics(QuestionBank $questionBank)
    {
        $stats = [
            'usage_count' => $questionBank->usage_count,
            'success_rate' => $questionBank->success_rate,
            'last_used_at' => $questionBank->last_used_at?->format('d M Y H:i'),
            'difficulty' => $questionBank->difficulty,
        ];

        return response()->json($stats);
    }
}
