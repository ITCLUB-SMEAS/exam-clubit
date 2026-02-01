<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\StudentRiskPrediction;
use App\Services\PredictiveAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PredictiveAnalyticsController extends Controller
{
    public function __construct(
        protected PredictiveAnalyticsService $service
    ) {}

    /**
     * Display at-risk students dashboard
     */
    public function index(Request $request)
    {
        // Get filters
        $riskLevel = $request->get('risk_level');
        $examId = $request->get('exam_id');
        $status = $request->get('status');

        // Build query
        $query = StudentRiskPrediction::with(['student.classroom', 'exam.lesson'])
            ->active()
            ->recent(14) // Last 2 weeks
            ->orderByDesc('risk_score');

        if ($riskLevel) {
            $query->riskLevel($riskLevel);
        } else {
            // Default: show high risk and critical
            $query->highRisk();
        }

        if ($examId) {
            $query->forExam($examId);
        }

        if ($status) {
            $query->where('intervention_status', $status);
        }

        $predictions = $query->paginate(20)->withQueryString();

        // Get summary stats
        $summary = $this->service->getDashboardSummary();

        // Get upcoming exams for filter
        $upcomingExams = ExamSession::where('start_time', '>', now())
            ->where('start_time', '<=', now()->addDays(7))
            ->with('exam:id,title')
            ->get()
            ->pluck('exam')
            ->filter()
            ->unique('id')
            ->values();

        return inertia('Admin/Analytics/AtRiskStudents', [
            'predictions' => $predictions,
            'summary' => $summary,
            'upcomingExams' => $upcomingExams,
            'filters' => [
                'risk_level' => $riskLevel,
                'exam_id' => $examId,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Show detailed risk analysis for a specific prediction
     */
    public function show(StudentRiskPrediction $prediction)
    {
        $prediction->load(['student.classroom', 'exam.lesson', 'intervenedBy']);

        // Get student's historical grades for context
        $historicalGrades = $prediction->student->grades()
            ->completed()
            ->with('exam:id,title')
            ->orderByDesc('end_time')
            ->limit(10)
            ->get()
            ->map(fn ($g) => [
                'id' => $g->id,
                'exam_title' => $g->exam?->title ?? 'N/A',
                'grade' => $g->grade,
                'status' => $g->status,
                'violations' => $g->violation_count,
                'date' => $g->end_time?->format('d M Y'),
            ]);

        return inertia('Admin/Analytics/StudentRiskDetail', [
            'prediction' => $prediction,
            'historicalGrades' => $historicalGrades,
        ]);
    }

    /**
     * Acknowledge a risk prediction (mark as seen)
     */
    public function acknowledge(StudentRiskPrediction $prediction)
    {
        $prediction->markAsAcknowledged();

        return back()->with('success', 'Prediksi telah ditandai sebagai dilihat.');
    }

    /**
     * Record intervention for a student
     */
    public function intervene(Request $request, StudentRiskPrediction $prediction)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $prediction->recordIntervention(
            Auth::id(),
            $request->input('notes')
        );

        return back()->with('success', 'Intervensi berhasil dicatat.');
    }

    /**
     * Mark intervention as resolved
     */
    public function resolve(StudentRiskPrediction $prediction)
    {
        $prediction->markAsResolved();

        return back()->with('success', 'Intervensi ditandai sebagai selesai.');
    }

    /**
     * Manually trigger risk calculation for an exam
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $predictions = $this->service->generatePredictionsForExam($exam);

        $highRiskCount = $predictions->filter->isHighRisk()->count();

        return back()->with('success', "Prediksi berhasil dihitung. {$predictions->count()} siswa dianalisis, {$highRiskCount} berisiko tinggi.");
    }

    /**
     * Get prediction data for widget (API endpoint)
     */
    public function widgetData()
    {
        $summary = $this->service->getDashboardSummary();

        $recentHighRisk = StudentRiskPrediction::with('student:id,name')
            ->highRisk()
            ->active()
            ->recent(7)
            ->orderByDesc('risk_score')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'student_name' => $p->student?->name ?? 'N/A',
                'risk_score' => $p->risk_score,
                'risk_level' => $p->risk_level,
                'risk_label' => $p->getRiskLabel(),
            ]);

        return response()->json([
            'summary' => $summary,
            'recent_high_risk' => $recentHighRisk,
        ]);
    }

    /**
     * Bulk acknowledge multiple predictions
     */
    public function bulkAcknowledge(Request $request)
    {
        $request->validate([
            'prediction_ids' => 'required|array',
            'prediction_ids.*' => 'exists:student_risk_predictions,id',
        ]);

        $count = StudentRiskPrediction::whereIn('id', $request->prediction_ids)
            ->where('intervention_status', StudentRiskPrediction::STATUS_PENDING)
            ->update([
                'intervention_status' => StudentRiskPrediction::STATUS_ACKNOWLEDGED,
            ]);

        return back()->with('success', "{$count} prediksi berhasil ditandai.");
    }
}
