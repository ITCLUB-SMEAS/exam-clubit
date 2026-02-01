<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesTransactions;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Controller for Exam CRUD operations.
 * Question-related operations are handled by ExamQuestionController.
 */
class ExamController extends Controller
{
    use HandlesTransactions;

    /**
     * Get cached lessons
     */
    private function getLessons()
    {
        return Cache::remember('lessons_all', 3600, fn () => Lesson::all());
    }

    /**
     * Get cached classrooms
     */
    private function getClassrooms()
    {
        return Cache::remember('classrooms_all', 3600, fn () => Classroom::all());
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $exams = Exam::when(request()->q, function ($exams) {
            $exams = $exams->where('title', 'like', '%'.request()->q.'%');
        })
            ->with('lesson', 'classroom', 'questions')
            ->latest()
            ->paginate(10);

        $exams->appends(['q' => request()->q]);

        return inertia('Admin/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Admin/Exams/Create', [
            'lessons' => $this->getLessons(),
            'classrooms' => $this->getClassrooms(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'question_limit' => 'nullable|integer|min:1',
            'time_per_question' => 'nullable|integer|min:1',
        ]);

        Exam::create([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->adaptive_mode ? 'N' : $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
            'passing_grade' => $request->passing_grade ?? 0,
            'max_attempts' => $request->max_attempts ?? 1,
            'question_limit' => $request->question_limit,
            'time_per_question' => $request->time_per_question,
            'block_multiple_monitors' => true,
            'block_virtual_machine' => true,
            'adaptive_mode' => $request->adaptive_mode ?? false,
        ]);

        return redirect()->route('admin.exams.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $exam = Exam::with('lesson', 'classroom')->findOrFail($id);

        $totalQuestions = $exam->questions()->count();

        $exam->setRelation('questions', $exam->questions()->paginate(5));

        return inertia('Admin/Exams/Show', [
            'exam' => $exam,
            'totalQuestions' => $totalQuestions,
            'bankQuestions' => QuestionBank::all(),
            'categories' => QuestionCategory::all(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $exam = Exam::findOrFail($id);

        return inertia('Admin/Exams/Edit', [
            'exam' => $exam,
            'lessons' => $this->getLessons(),
            'classrooms' => $this->getClassrooms(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        $request->validate([
            'title' => 'required',
            'lesson_id' => 'required|integer',
            'classroom_id' => 'required|integer',
            'duration' => 'required|integer',
            'description' => 'required',
            'random_question' => 'required',
            'random_answer' => 'required',
            'show_answer' => 'required',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'question_limit' => 'nullable|integer|min:1',
            'time_per_question' => 'nullable|integer|min:1',
        ]);

        $exam->update([
            'title' => $request->title,
            'lesson_id' => $request->lesson_id,
            'classroom_id' => $request->classroom_id,
            'duration' => $request->duration,
            'description' => $request->description,
            'random_question' => $request->adaptive_mode ? 'N' : $request->random_question,
            'random_answer' => $request->random_answer,
            'show_answer' => $request->show_answer,
            'passing_grade' => $request->passing_grade ?? 0,
            'max_attempts' => $request->max_attempts ?? 1,
            'question_limit' => $request->question_limit,
            'time_per_question' => $request->time_per_question,
            'block_multiple_monitors' => true,
            'block_virtual_machine' => true,
            'adaptive_mode' => $request->adaptive_mode ?? false,
        ]);

        return redirect()->route('admin.exams.index');
    }

    /**
     * Duplicate exam with all questions.
     */
    public function duplicate(Exam $exam)
    {
        return DB::transaction(function () use ($exam) {
            $newExam = $exam->replicate();
            $newExam->title = $exam->title.' (Copy)';
            $newExam->save();

            foreach ($exam->questions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->exam_id = $newExam->id;
                $newQuestion->save();
            }

            return redirect()->route('admin.exams.show', $newExam->id)
                ->with('success', 'Ujian berhasil diduplikasi dengan '.$exam->questions->count().' soal.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $exam->delete();

        return redirect()->route('admin.exams.index');
    }

    /**
     * Preview exam as student view.
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
