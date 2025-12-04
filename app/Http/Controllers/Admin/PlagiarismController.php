<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\PlagiarismService;
use Illuminate\Http\Request;

class PlagiarismController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::whereHas('questions', fn($q) => $q->whereIn('question_type', ['essay', 'short_answer']))
            ->with('lesson')
            ->latest()
            ->get();

        $sessions = collect();
        $results = [];

        if ($request->exam_id) {
            $sessions = ExamSession::where('exam_id', $request->exam_id)->get();
        }

        if ($request->exam_id && $request->session_id) {
            $service = new PlagiarismService();
            $results = $service->checkExamSession($request->exam_id, $request->session_id);
        }

        return inertia('Admin/Plagiarism/Index', [
            'exams' => $exams,
            'sessions' => $sessions,
            'results' => $results,
            'filters' => $request->only(['exam_id', 'session_id']),
        ]);
    }
}
