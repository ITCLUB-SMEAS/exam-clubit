<?php

namespace App\Http\Controllers\Admin;

use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Classroom;
use Illuminate\Http\Request;
use App\Imports\QuestionsImport;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Services\DuplicateQuestionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExamController extends Controller
{
    use HandlesTransactions;
    /**
     * Get cached lessons
     */
    private function getLessons()
    {
        return Cache::remember('lessons_all', 3600, fn() => Lesson::all());
    }

    /**
     * Get cached classrooms
     */
    private function getClassrooms()
    {
        return Cache::remember('classrooms_all', 3600, fn() => Classroom::all());
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $exams = Exam::when(request()->q, function ($exams) {
            $exams = $exams->where("title", "like", "%" . request()->q . "%");
        })
            ->with("lesson", "classroom", "questions")
            ->latest()
            ->paginate(10);

        $exams->appends(["q" => request()->q]);

        return inertia("Admin/Exams/Index", [
            "exams" => $exams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return inertia("Admin/Exams/Create", [
            "lessons" => $this->getLessons(),
            "classrooms" => $this->getClassrooms(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validate request
        $request->validate([
            "title" => "required",
            "lesson_id" => "required|integer",
            "classroom_id" => "required|integer",
            "duration" => "required|integer",
            "description" => "required",
            "random_question" => "required",
            "random_answer" => "required",
            "show_answer" => "required",
            "passing_grade" => "nullable|numeric|min:0|max:100",
            "max_attempts" => "nullable|integer|min:1",
            "question_limit" => "nullable|integer|min:1",
            "time_per_question" => "nullable|integer|min:1",
        ]);

        //create exam
        Exam::create([
            "title" => $request->title,
            "lesson_id" => $request->lesson_id,
            "classroom_id" => $request->classroom_id,
            "duration" => $request->duration,
            "description" => $request->description,
            "random_question" => $request->adaptive_mode ? 'N' : $request->random_question,
            "random_answer" => $request->random_answer,
            "show_answer" => $request->show_answer,
            "passing_grade" => $request->passing_grade ?? 0,
            "max_attempts" => $request->max_attempts ?? 1,
            "question_limit" => $request->question_limit,
            "time_per_question" => $request->time_per_question,
            "block_multiple_monitors" => true,
            "block_virtual_machine" => true,
            "adaptive_mode" => $request->adaptive_mode ?? false,
        ]);

        //redirect
        return redirect()->route("admin.exams.index");
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //get exam
        $exam = Exam::with("lesson", "classroom")->findOrFail($id);

        //get total questions count
        $totalQuestions = $exam->questions()->count();

        //get relation questions with pagination
        $exam->setRelation("questions", $exam->questions()->paginate(5));

        //render with inertia
        return inertia("Admin/Exams/Show", [
            "exam" => $exam,
            "totalQuestions" => $totalQuestions,
            "bankQuestions" => QuestionBank::all(),
            "categories" => QuestionCategory::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $exam = Exam::findOrFail($id);

        return inertia("Admin/Exams/Edit", [
            "exam" => $exam,
            "lessons" => $this->getLessons(),
            "classrooms" => $this->getClassrooms(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Exam $exam)
    {
        //validate request
        $request->validate([
            "title" => "required",
            "lesson_id" => "required|integer",
            "classroom_id" => "required|integer",
            "duration" => "required|integer",
            "description" => "required",
            "random_question" => "required",
            "random_answer" => "required",
            "show_answer" => "required",
            "passing_grade" => "nullable|numeric|min:0|max:100",
            "max_attempts" => "nullable|integer|min:1",
            "question_limit" => "nullable|integer|min:1",
            "time_per_question" => "nullable|integer|min:1",
        ]);

        //update exam
        $exam->update([
            "title" => $request->title,
            "lesson_id" => $request->lesson_id,
            "classroom_id" => $request->classroom_id,
            "duration" => $request->duration,
            "description" => $request->description,
            "random_question" => $request->adaptive_mode ? 'N' : $request->random_question,
            "random_answer" => $request->random_answer,
            "show_answer" => $request->show_answer,
            "passing_grade" => $request->passing_grade ?? 0,
            "max_attempts" => $request->max_attempts ?? 1,
            "question_limit" => $request->question_limit,
            "time_per_question" => $request->time_per_question,
            "block_multiple_monitors" => true,
            "block_virtual_machine" => true,
            "adaptive_mode" => $request->adaptive_mode ?? false,
        ]);

        //redirect
        return redirect()->route("admin.exams.index");
    }

    /**
     * Duplicate exam with all questions
     */
    public function duplicate(Exam $exam)
    {
        return DB::transaction(function () use ($exam) {
            // Clone exam
            $newExam = $exam->replicate();
            $newExam->title = $exam->title . ' (Copy)';
            $newExam->save();

            // Clone questions
            foreach ($exam->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->exam_id = $newExam->id;
                $newQuestion->save();
            }

            return redirect()->route("admin.exams.show", $newExam->id)
                ->with('success', 'Ujian berhasil diduplikasi dengan ' . $exam->questions->count() . ' soal.');
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //get exam
        $exam = Exam::findOrFail($id);

        //delete exam
        $exam->delete();

        //redirect
        return redirect()->route("admin.exams.index");
    }

    /**
     * createQuestion
     *
     * @param  mixed $exam
     * @return void
     */
    public function createQuestion(Exam $exam)
    {
        //render with inertia
        return inertia("Admin/Questions/Create", [
            "exam" => $exam,
        ]);
    }

    /**
     * storeQuestion
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @return void
     */
    public function storeQuestion(Request $request, Exam $exam)
    {
        $request->validate([
            "question" => "required",
            "option_1" => "nullable",
            "option_2" => "nullable",
            "option_3" => "nullable",
            "option_4" => "nullable",
            "option_5" => "nullable",
            "answer" => "nullable",
            "question_type" => "nullable|in:multiple_choice_single,multiple_choice_multiple,short_answer,essay,true_false,matching",
            "points" => "nullable|numeric|min:0",
            "correct_answers" => "nullable|array",
            "matching_pairs" => "nullable|array",
            "skip_duplicate_check" => "nullable|boolean",
        ]);

        // Check for duplicates unless explicitly skipped
        if (!$request->input("skip_duplicate_check")) {
            $duplicateService = new DuplicateQuestionService();
            $check = $duplicateService->checkDuplicate($request->question, $exam->id);
            
            if ($check['is_duplicate']) {
                return back()->withErrors([
                    'question' => "Soal terdeteksi duplikat ({$check['similarity']}% mirip) dengan: \"{$check['duplicate_text']}\""
                ])->withInput();
            }
        }

        $questionType = $request->input("question_type", Question::TYPE_MULTIPLE_CHOICE_SINGLE);

        $correctAnswers = $request->input("correct_answers");
        if (is_string($correctAnswers)) {
            $correctAnswers = array_filter(array_map("trim", explode(",", $correctAnswers)));
        }

        Question::create([
            "exam_id" => $exam->id,
            "question" => $request->question,
            "question_type" => $questionType,
            "points" => $request->input("points", 1),
            "option_1" => $request->option_1,
            "option_2" => $request->option_2,
            "option_3" => $request->option_3,
            "option_4" => $request->option_4,
            "option_5" => $request->option_5,
            "answer" => $request->answer,
            "correct_answers" => $correctAnswers,
            "matching_pairs" => $request->input("matching_pairs"),
        ]);

        return redirect()->route("admin.exams.show", $exam->id);
    }

    /**
     * editQuestion
     *
     * @param  mixed $exam
     * @param  mixed $question
     * @return void
     */
    public function editQuestion(Exam $exam, Question $question)
    {
        //render with inertia
        return inertia("Admin/Questions/Edit", [
            "exam" => $exam,
            "question" => $question,
        ]);
    }

    /**
     * updateQuestion
     *
     * @param  mixed $request
     * @param  mixed $exam
     * @param  mixed $question
     * @return void
     */
    public function updateQuestion(Request $request, Exam $exam, Question $question)
    {
        $request->validate([
            "question" => "required",
            "option_1" => "nullable",
            "option_2" => "nullable",
            "option_3" => "nullable",
            "option_4" => "nullable",
            "option_5" => "nullable",
            "answer" => "nullable",
            "question_type" => "nullable|in:multiple_choice_single,multiple_choice_multiple,short_answer,essay,true_false,matching",
            "points" => "nullable|numeric|min:0",
            "correct_answers" => "nullable|array",
            "matching_pairs" => "nullable|array",
            "skip_duplicate_check" => "nullable|boolean",
        ]);

        // Check for duplicates (exclude current question)
        if (!$request->input("skip_duplicate_check")) {
            $duplicateService = new DuplicateQuestionService();
            $check = $duplicateService->checkDuplicate($request->question, $exam->id, $question->id);
            
            if ($check['is_duplicate']) {
                return back()->withErrors([
                    'question' => "Soal terdeteksi duplikat ({$check['similarity']}% mirip) dengan: \"{$check['duplicate_text']}\""
                ])->withInput();
            }
        }

        $questionType = $request->input("question_type", Question::TYPE_MULTIPLE_CHOICE_SINGLE);

        $correctAnswers = $request->input("correct_answers");
        if (is_string($correctAnswers)) {
            $correctAnswers = array_filter(array_map("trim", explode(",", $correctAnswers)));
        }

        // Create version before update
        $question->createVersion(auth()->id(), 'Updated via form');

        $question->update([
            "question" => $request->question,
            "question_type" => $questionType,
            "points" => $request->input("points", 1),
            "option_1" => $request->option_1,
            "option_2" => $request->option_2,
            "option_3" => $request->option_3,
            "option_4" => $request->option_4,
            "option_5" => $request->option_5,
            "answer" => $request->answer,
            "correct_answers" => $correctAnswers,
            "matching_pairs" => $request->input("matching_pairs"),
        ]);

        return redirect()->route("admin.exams.show", $exam->id);
    }

    /**
     * destroyQuestion
     */
    public function destroyQuestion(Exam $exam, Question $question)
    {
        $question->delete();
        return redirect()->route("admin.exams.show", $exam->id);
    }

    /**
     * Bulk update question points
     */
    public function bulkUpdatePoints(Request $request, Exam $exam)
    {
        $request->validate([
            'questions' => 'required|array|max:100',
            'questions.*.id' => 'required|exists:questions,id',
            'questions.*.points' => 'required|numeric|min:0',
        ]);

        return $this->executeInTransaction(function () use ($request, $exam) {
            $updated = 0;
            foreach ($request->questions as $q) {
                Question::where('id', $q['id'])
                    ->where('exam_id', $exam->id)
                    ->update(['points' => $q['points']]);
                $updated++;
            }

            return back()->with('success', "{$updated} soal berhasil diupdate.");
        }, 'Gagal mengupdate poin soal.');
    }

    /**
     * Bulk delete questions
     */
    public function bulkDeleteQuestions(Request $request, Exam $exam)
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
     * import
     */
    public function import(Exam $exam)
    {
        return inertia("Admin/Questions/Import", [
            "exam" => $exam,
        ]);
    }

    /**
     * Download template for question import
     */
    public function downloadTemplate()
    {
        $headers = [
            'question',
            'option_1',
            'option_2',
            'option_3',
            'option_4',
            'option_5',
            'answer',
        ];

        $example = [
            'Berapa hasil dari 2 + 2?',
            '3',
            '4',
            '5',
            '6',
            '',
            '2', // jawaban bener = option_2 (angka 4)
        ];

        $notes = [
            'Catatan: Kolom "answer" diisi dengan nomor opsi yang benar (1-5). Contoh: 2 berarti option_2 adalah jawaban benar.',
            '', '', '', '', '', '',
        ];

        $data = [$headers, $example, $notes];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
                protected $data;
                public function __construct($data) { $this->data = $data; }
                public function array(): array { return $this->data; }
            },
            'template-import-soal.xlsx'
        );
    }

    /**
     * storeImport
     *
     * @param  mixed $request
     * @return void
     */
    public function storeImport(Request $request, Exam $exam)
    {
        $request->validate([
            "file" => "required|mimes:csv,xls,xlsx",
        ]);

        // import data
        Excel::import(new QuestionsImport($exam->id), $request->file("file"));

        //redirect
        return redirect()->route("admin.exams.show", $exam->id);
    }

    /**
     * Check for duplicate questions via AJAX
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'exam_id' => 'required|integer',
            'exclude_id' => 'nullable|integer',
        ]);

        $service = new DuplicateQuestionService();
        $result = $service->checkDuplicate(
            $request->question,
            $request->exam_id,
            $request->exclude_id
        );

        return response()->json($result);
    }

    /**
     * Preview exam as student view
     */
    public function preview(Exam $exam)
    {
        $exam->load(['lesson', 'classroom', 'questions']);

        $questions = $exam->questions->map(function ($q, $index) use ($exam) {
            $options = [];
            if (in_array($q->question_type, ['multiple_choice_single', 'multiple_choice_multiple', 'true_false'])) {
                for ($i = 1; $i <= 5; $i++) {
                    if ($q->{"option_$i"}) {
                        $options[] = [
                            'number' => $i,
                            'text' => $q->{"option_$i"},
                        ];
                    }
                }
                if ($exam->random_answer === 'Y') {
                    shuffle($options);
                }
            }

            return [
                'number' => $index + 1,
                'id' => $q->id,
                'question' => $q->question,
                'type' => $q->question_type,
                'points' => $q->points ?? 1,
                'options' => $options,
                'answer' => $q->answer,
                'correct_answers' => $q->correct_answers,
                'matching_pairs' => $q->matching_pairs,
            ];
        });

        if ($exam->random_question === 'Y') {
            $questions = $questions->shuffle()->values();
        }

        return inertia('Admin/Exams/Preview', [
            'exam' => $exam,
            'questions' => $questions,
        ]);
    }
}
