<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamSyncController extends Controller
{
    /**
     * Sync offline answers to server
     */
    public function sync(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.examGroupId' => 'required',
            'answers.*.questionId' => 'required',
        ]);

        $studentId = auth()->guard('student')->id();
        $synced = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->answers as $answerData) {
                $answer = Answer::where('student_id', $studentId)
                    ->where('question_id', $answerData['questionId'])
                    ->where('exam_id', $answerData['examId'] ?? null)
                    ->first();

                if ($answer) {
                    // Update existing answer
                    if (isset($answerData['answer'])) {
                        $answer->answer = $answerData['answer'];
                    }
                    if (isset($answerData['answerText'])) {
                        $answer->answer_text = $answerData['answerText'];
                    }
                    if (isset($answerData['answerOptions'])) {
                        $answer->answer_options = $answerData['answerOptions'];
                    }
                    $answer->save();
                    $synced++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'synced' => $synced,
                'message' => "Berhasil sync {$synced} jawaban"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Sync gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get exam data for offline caching
     */
    public function getExamForOffline($examGroupId)
    {
        $studentId = auth()->guard('student')->id();
        
        $examGroup = \App\Models\ExamGroup::with([
            'exam.lesson',
            'exam.questions' => function($q) {
                $q->select('id', 'exam_id', 'question', 'question_type', 'option_1', 'option_2', 'option_3', 'option_4', 'option_5', 'points', 'difficulty');
            },
            'exam_session'
        ])
        ->where('student_id', $studentId)
        ->where('id', $examGroupId)
        ->first();

        if (!$examGroup) {
            return response()->json(['error' => 'Exam not found'], 404);
        }

        // Get existing answers
        $answers = Answer::where('student_id', $studentId)
            ->where('exam_id', $examGroup->exam_id)
            ->get()
            ->keyBy('question_id');

        return response()->json([
            'examGroup' => $examGroup,
            'answers' => $answers,
            'cachedAt' => now()->toISOString(),
        ]);
    }
}
