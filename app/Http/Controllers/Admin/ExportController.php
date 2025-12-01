<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Exam;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportGradePdf(Grade $grade)
    {
        $grade->load(['student.classroom', 'exam.lesson', 'exam_session']);

        $pdf = Pdf::loadView('exports.grade', compact('grade'));
        
        $filename = "hasil_ujian_{$grade->student->nisn}_{$grade->exam->id}.pdf";
        
        return $pdf->download($filename);
    }

    public function exportExamResultsPdf(Exam $exam, Request $request)
    {
        $query = Grade::where('exam_id', $exam->id)
            ->whereNotNull('end_time')
            ->with(['student.classroom']);

        if ($request->session_id) {
            $query->where('exam_session_id', $request->session_id);
        }

        $grades = $query->orderByDesc('grade')->get();
        $exam->load('lesson', 'classroom');

        $stats = [
            'total' => $grades->count(),
            'average' => round($grades->avg('grade') ?? 0, 1),
            'highest' => $grades->max('grade') ?? 0,
            'lowest' => $grades->min('grade') ?? 0,
            'passed' => $grades->where('status', 'passed')->count(),
            'failed' => $grades->where('status', 'failed')->count(),
        ];

        $pdf = Pdf::loadView('exports.exam-results', compact('exam', 'grades', 'stats'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download("hasil_ujian_{$exam->id}.pdf");
    }

    public function exportStudentReportPdf(Student $student)
    {
        $student->load('classroom');
        
        $grades = Grade::where('student_id', $student->id)
            ->whereNotNull('end_time')
            ->with(['exam.lesson'])
            ->orderByDesc('end_time')
            ->get();

        $stats = [
            'total_exams' => $grades->count(),
            'average' => round($grades->avg('grade') ?? 0, 1),
            'passed' => $grades->where('status', 'passed')->count(),
            'failed' => $grades->where('status', 'failed')->count(),
        ];

        $pdf = Pdf::loadView('exports.student-report', compact('student', 'grades', 'stats'));
        
        return $pdf->download("laporan_siswa_{$student->nisn}.pdf");
    }
}
