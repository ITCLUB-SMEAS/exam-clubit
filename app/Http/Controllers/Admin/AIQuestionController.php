<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AIQuestionController extends Controller
{
    public function index()
    {
        $exams = Exam::with('lesson')->latest()->get();
        
        return inertia('Admin/AIGenerator/Index', [
            'exams' => $exams,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'type' => 'required|in:multiple_choice_single,true_false,essay,short_answer',
            'count' => 'required|integer|min:1|max:10',
            'difficulty' => 'required|in:easy,medium,hard',
        ]);

        $gemini = new GeminiService();
        $questions = $gemini->generateQuestions(
            $request->topic,
            $request->type,
            $request->count,
            $request->difficulty
        );

        if (!$questions) {
            return response()->json(['error' => 'Gagal generate soal. Coba lagi.'], 500);
        }

        return response()->json(['questions' => $questions]);
    }

    public function saveToExam(Request $request, Exam $exam)
    {
        $request->validate([
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string',
            'questions.*.type' => 'required|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.answer' => 'required',
            'points' => 'nullable|integer|min:1',
        ]);

        $points = $request->points ?? 1;
        $saved = 0;

        foreach ($request->questions as $q) {
            $data = [
                'exam_id' => $exam->id,
                'question' => $q['question'],
                'question_type' => $q['type'],
                'points' => $points,
            ];

            if (in_array($q['type'], ['multiple_choice_single', 'true_false'])) {
                $options = $q['options'] ?? [];
                for ($i = 0; $i < 5; $i++) {
                    $data["option_" . ($i + 1)] = $options[$i] ?? null;
                }
                $data['answer'] = $q['answer'];
            } elseif ($q['type'] === 'essay' || $q['type'] === 'short_answer') {
                $data['answer'] = $q['answer'];
            }

            Question::create($data);
            $saved++;
        }

        return back()->with('success', "{$saved} soal berhasil ditambahkan.");
    }
}
