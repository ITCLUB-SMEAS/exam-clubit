<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Models\Question;
use App\Models\Exam;
use Illuminate\Http\Request;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $query = QuestionBank::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->question_type) {
            $query->where('question_type', $request->question_type);
        }
        if ($request->search) {
            $query->where('question', 'like', "%{$request->search}%");
        }

        $questions = $query->latest()->paginate(10)->withQueryString();
        $categories = QuestionCategory::all();

        return inertia('Admin/QuestionBank/Index', compact('questions', 'categories'));
    }

    public function create()
    {
        $categories = QuestionCategory::all();
        return inertia('Admin/QuestionBank/Create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:question_categories,id',
            'question' => 'required|string',
            'question_type' => 'required|in:multiple_choice_single,multiple_choice_multiple,true_false,short_answer,essay,matching',
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

        QuestionBank::create($validated);

        return redirect()->route('admin.question-bank.index')
            ->with('success', 'Soal berhasil ditambahkan ke bank soal');
    }

    public function edit(QuestionBank $questionBank)
    {
        $categories = QuestionCategory::all();
        return inertia('Admin/QuestionBank/Edit', [
            'question' => $questionBank,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, QuestionBank $questionBank)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:question_categories,id',
            'question' => 'required|string',
            'question_type' => 'required|in:multiple_choice_single,multiple_choice_multiple,true_false,short_answer,essay,matching',
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

    // Import soal dari bank ke exam
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
        }

        return redirect()->route('admin.exams.show', $exam)
            ->with('success', count($bankQuestions) . ' soal berhasil diimport');
    }

    // Get questions for modal selection
    public function getQuestions(Request $request)
    {
        $query = QuestionBank::with('category');

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return response()->json($query->get());
    }
}
