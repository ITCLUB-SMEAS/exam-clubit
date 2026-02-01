<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Imports\QuestionsImport;
use App\Models\Exam;
use App\Models\Question;
use App\Services\DuplicateQuestionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Controller for managing questions within an exam.
 * Extracted from ExamController for Single Responsibility.
 */
class ExamQuestionController extends Controller
{
    use HandlesTransactions;

    public function __construct(
        protected DuplicateQuestionService $duplicateService
    ) {}

    /**
     * Show the form for creating a new question.
     */
    public function create(Exam $exam)
    {
        return inertia('Admin/Questions/Create', [
            'exam' => $exam,
        ]);
    }

    /**
     * Store a newly created question.
     */
    public function store(StoreQuestionRequest $request, Exam $exam)
    {
        // Check for duplicates unless explicitly skipped
        if (! $request->input('skip_duplicate_check')) {
            $check = $this->duplicateService->checkDuplicate($request->question, $exam->id);

            if ($check['is_duplicate']) {
                return back()->withErrors([
                    'question' => "Soal terdeteksi duplikat ({$check['similarity']}% mirip) dengan: \"{$check['duplicate_text']}\"",
                ])->withInput();
            }
        }

        Question::create($request->getQuestionData($exam->id));

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('success', 'Soal berhasil ditambahkan.');
    }

    /**
     * Show the form for editing a question.
     */
    public function edit(Exam $exam, Question $question)
    {
        return inertia('Admin/Questions/Edit', [
            'exam' => $exam,
            'question' => $question,
        ]);
    }

    /**
     * Update the specified question.
     */
    public function update(UpdateQuestionRequest $request, Exam $exam, Question $question)
    {
        // Check for duplicates (exclude current question)
        if (! $request->input('skip_duplicate_check')) {
            $check = $this->duplicateService->checkDuplicate(
                $request->question,
                $exam->id,
                $question->id
            );

            if ($check['is_duplicate']) {
                return back()->withErrors([
                    'question' => "Soal terdeteksi duplikat ({$check['similarity']}% mirip) dengan: \"{$check['duplicate_text']}\"",
                ])->withInput();
            }
        }

        // Create version before update
        $question->createVersion(auth()->id(), 'Updated via form');

        $question->update($request->getQuestionData());

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('success', 'Soal berhasil diperbarui.');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Exam $exam, Question $question)
    {
        $question->delete();

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Bulk update question points.
     */
    public function bulkUpdatePoints(Request $request, Exam $exam)
    {
        $request->validate([
            'questions' => 'required|array|max:100',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.points' => 'required|numeric|min:0|max:1000',
        ]);

        return $this->executeInTransaction(function () use ($request, $exam) {
            $questionIds = collect($request->questions)->pluck('id')->toArray();
            $pointsMap = collect($request->questions)->pluck('points', 'id')->toArray();

            // Batch update using case-when for efficiency
            $cases = [];
            $ids = [];

            foreach ($pointsMap as $id => $points) {
                $cases[] = "WHEN id = {$id} THEN {$points}";
                $ids[] = $id;
            }

            if (! empty($cases)) {
                $casesString = implode(' ', $cases);
                $idsString = implode(',', $ids);

                Question::whereIn('id', $ids)
                    ->where('exam_id', $exam->id)
                    ->update([
                        'points' => \DB::raw("CASE {$casesString} ELSE points END"),
                    ]);
            }

            return back()->with('success', count($request->questions).' soal berhasil diupdate.');
        }, 'Gagal mengupdate poin soal.');
    }

    /**
     * Bulk delete questions.
     */
    public function bulkDelete(Request $request, Exam $exam)
    {
        $request->validate([
            'question_ids' => 'required|array|max:100',
            'question_ids.*' => 'exists:questions,id',
        ]);

        return $this->executeInTransaction(function () use ($request, $exam) {
            $deleted = Question::whereIn('id', $request->question_ids)
                ->where('exam_id', $exam->id)
                ->delete();

            return back()->with('success', "{$deleted} soal berhasil dihapus.");
        }, 'Gagal menghapus soal.');
    }

    /**
     * Show import page.
     */
    public function import(Exam $exam)
    {
        return inertia('Admin/Questions/Import', [
            'exam' => $exam,
        ]);
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'question',
            'question_type',
            'difficulty',
            'points',
            'option_1',
            'option_2',
            'option_3',
            'option_4',
            'option_5',
            'answer',
        ];

        $examples = [
            [
                'Berapa hasil dari 2 + 2?',
                'multiple_choice_single',
                'easy',
                '1',
                '3',
                '4',
                '5',
                '6',
                '',
                '2',
            ],
            [
                'Manakah yang termasuk bilangan prima?',
                'multiple_choice_multiple',
                'medium',
                '2',
                '2',
                '4',
                '5',
                '9',
                '7',
                '',
            ],
        ];

        $notes = [
            'Catatan:',
            '- question_type: multiple_choice_single, multiple_choice_multiple, true_false, short_answer, essay, matching',
            '- difficulty: easy, medium, hard',
            '- answer: nomor opsi yang benar (1-5) untuk single choice',
            '', '', '', '', '', '',
        ];

        $data = array_merge([$headers], $examples, [$notes]);

        return Excel::download(
            new class($data) implements \Maatwebsite\Excel\Concerns\FromArray
            {
                protected $data;

                public function __construct($data)
                {
                    $this->data = $data;
                }

                public function array(): array
                {
                    return $this->data;
                }
            },
            'template-import-soal.xlsx'
        );
    }

    /**
     * Process import.
     */
    public function storeImport(Request $request, Exam $exam)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xls,xlsx|max:5120',
        ]);

        Excel::import(new QuestionsImport($exam->id), $request->file('file'));

        return redirect()->route('admin.exams.show', $exam->id)
            ->with('success', 'Soal berhasil diimport.');
    }

    /**
     * Check for duplicate questions via AJAX.
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'exam_id' => 'required|integer',
            'exclude_id' => 'nullable|integer',
        ]);

        $result = $this->duplicateService->checkDuplicate(
            $request->question,
            $request->exam_id,
            $request->exclude_id
        );

        return response()->json($result);
    }
}
