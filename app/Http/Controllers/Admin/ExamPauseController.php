<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ExamSession;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ExamPauseController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ExamSession::with('exam')
            ->whereHas('exam_groups')
            ->latest()
            ->get();

        $activeExams = collect();

        if ($request->session_id) {
            $activeExams = Grade::where('exam_session_id', $request->session_id)
                ->whereNotNull('start_time')
                ->whereNull('end_time')
                ->with(['student.classroom', 'exam'])
                ->get();
        }

        return inertia('Admin/ExamPause/Index', [
            'sessions' => $sessions,
            'activeExams' => $activeExams,
            'filters' => ['session_id' => $request->session_id],
        ]);
    }

    public function pause(Request $request, Grade $grade)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        if ($grade->end_time || $grade->is_paused) {
            return back()->with('error', 'Ujian tidak dapat di-pause.');
        }

        $grade->update([
            'is_paused' => true,
            'paused_at' => now(),
            'pause_reason' => $request->reason,
        ]);

        ActivityLogService::log(
            action: 'pause_exam',
            module: 'exam',
            description: "Ujian di-pause: {$request->reason}",
            subject: $grade,
        );

        return back()->with('success', 'Ujian berhasil di-pause.');
    }

    public function resume(Grade $grade)
    {
        if (!$grade->is_paused) {
            return back()->with('error', 'Ujian tidak dalam status pause.');
        }

        $grade->update([
            'is_paused' => false,
            'paused_at' => null,
            'pause_reason' => null,
        ]);

        ActivityLogService::log(
            action: 'resume_exam',
            module: 'exam',
            description: "Ujian di-resume",
            subject: $grade,
        );

        return back()->with('success', 'Ujian berhasil di-resume.');
    }

    public function pauseAll(Request $request, ExamSession $examSession)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $count = Grade::where('exam_session_id', $examSession->id)
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->where('is_paused', false)
            ->update([
                'is_paused' => true,
                'paused_at' => now(),
                'pause_reason' => $request->reason,
            ]);

        ActivityLogService::log(
            action: 'pause_all_exams',
            module: 'exam',
            description: "Semua ujian di-pause ({$count} siswa): {$request->reason}",
            subject: $examSession,
        );

        return back()->with('success', "{$count} ujian berhasil di-pause.");
    }

    public function resumeAll(ExamSession $examSession)
    {
        $count = Grade::where('exam_session_id', $examSession->id)
            ->where('is_paused', true)
            ->update([
                'is_paused' => false,
                'paused_at' => null,
                'pause_reason' => null,
            ]);

        ActivityLogService::log(
            action: 'resume_all_exams',
            module: 'exam',
            description: "Semua ujian di-resume ({$count} siswa)",
            subject: $examSession,
        );

        return back()->with('success', "{$count} ujian berhasil di-resume.");
    }
}
