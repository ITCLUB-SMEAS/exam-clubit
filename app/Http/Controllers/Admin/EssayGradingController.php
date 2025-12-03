<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Grade;
use App\Models\Exam;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class EssayGradingController extends Controller
{
    public function index(Request $request)
    {
        $exams = Exam::whereHas('questions', function ($q) {
            $q->whereIn('question_type', ['essay', 'short_answer']);
        })->with('lesson')->get();

        $pendingCount = Answer::where('needs_manual_review', true)
            ->whereNull('points_awarded')
            ->count();

        $sessions = collect();
        $answers = collect();

        if ($request->exam_id) {
            $sessions = ExamSession::where('exam_id', $request->exam_id)->get();
        }

        if ($request->exam_id && $request->session_id) {
            $answers = Answer::where('exam_id', $request->exam_id)
                ->where('exam_session_id', $request->session_id)
                ->where('needs_manual_review', true)
                ->with(['question', 'student'])
                ->orderByRaw('points_awarded IS NOT NULL')
                ->orderBy('created_at')
                ->paginate(10);
        }

        return inertia('Admin/EssayGrading/Index', [
            'exams' => $exams,
            'sessions' => $sessions,
            'answers' => $answers,
            'pendingCount' => $pendingCount,
            'filters' => [
                'exam_id' => $request->exam_id,
                'session_id' => $request->session_id,
            ],
        ]);
    }

    public function grade(Request $request, Answer $answer)
    {
        $request->validate([
            'points' => 'required|numeric|min:0',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $maxPoints = $answer->question->points ?? 1;
        $points = min($request->points, $maxPoints);

        $answer->update([
            'points_awarded' => $points,
            'is_correct' => $points >= ($maxPoints * 0.5) ? 'Y' : 'N',
        ]);

        // Recalculate grade
        $this->recalculateGrade($answer);

        return back()->with('success', 'Nilai berhasil disimpan.');
    }

    public function bulkGrade(Request $request)
    {
        $request->validate([
            'grades' => 'required|array|max:50', // Limit 50 items per request
            'grades.*.answer_id' => 'required|exists:answers,id',
            'grades.*.points' => 'required|numeric|min:0',
        ]);

        foreach ($request->grades as $item) {
            $answer = Answer::find($item['answer_id']);
            if ($answer) {
                $maxPoints = $answer->question->points ?? 1;
                $points = min($item['points'], $maxPoints);

                $answer->update([
                    'points_awarded' => $points,
                    'is_correct' => $points >= ($maxPoints * 0.5) ? 'Y' : 'N',
                ]);

                $this->recalculateGrade($answer);
            }
        }

        return back()->with('success', count($request->grades) . ' jawaban berhasil dinilai.');
    }

    private function recalculateGrade(Answer $answer): void
    {
        $grade = Grade::where('exam_id', $answer->exam_id)
            ->where('exam_session_id', $answer->exam_session_id)
            ->where('student_id', $answer->student_id)
            ->first();

        if (!$grade) return;

        $answers = Answer::where('exam_id', $answer->exam_id)
            ->where('exam_session_id', $answer->exam_session_id)
            ->where('student_id', $answer->student_id)
            ->with('question')
            ->get();

        $totalPoints = 0;
        $earnedPoints = 0;
        $correctCount = 0;

        foreach ($answers as $ans) {
            $qPoints = $ans->question->points ?? 1;
            $totalPoints += $qPoints;

            if ($ans->points_awarded !== null) {
                $earnedPoints += $ans->points_awarded;
                if ($ans->is_correct === 'Y') $correctCount++;
            } elseif ($ans->is_correct === 'Y') {
                $earnedPoints += $qPoints;
                $correctCount++;
            }
        }

        $gradeValue = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0;
        $exam = $grade->exam;
        $status = ($exam->passing_grade ?? 0) > 0 && $gradeValue >= $exam->passing_grade ? 'passed' : 'failed';

        $grade->update([
            'total_correct' => $correctCount,
            'grade' => $gradeValue,
            'points_possible' => $totalPoints,
            'points_earned' => $earnedPoints,
            'status' => $status,
        ]);
    }
}
