<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ExamSession;
use Illuminate\Http\Request;

class TimeExtensionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = ExamSession::with('exam')
            ->where('end_time', '>', now())
            ->latest()
            ->get();

        $activeExams = collect();
        
        if ($request->session_id) {
            $activeExams = Grade::with(['student', 'exam'])
                ->where('exam_session_id', $request->session_id)
                ->whereNull('end_time')
                ->get()
                ->map(fn($g) => [
                    'id' => $g->id,
                    'student_name' => $g->student->name,
                    'student_nisn' => $g->student->nisn,
                    'exam_title' => $g->exam->title,
                    'duration' => $g->duration,
                    'time_extension' => $g->time_extension ?? 0,
                    'total_time' => $g->duration + ($g->time_extension ?? 0),
                    'start_time' => $g->start_time,
                    'extension_reason' => $g->extension_reason,
                ]);
        }

        return inertia('Admin/TimeExtension/Index', [
            'sessions' => $sessions,
            'activeExams' => $activeExams,
            'selectedSession' => $request->session_id,
        ]);
    }

    public function extend(Request $request, Grade $grade)
    {
        $request->validate([
            'minutes' => 'required|integer|min:1|max:120',
            'reason' => 'required|string|max:255',
        ]);

        if ($grade->end_time) {
            return back()->withErrors(['error' => 'Ujian sudah selesai, tidak bisa diperpanjang.']);
        }

        $grade->update([
            'time_extension' => ($grade->time_extension ?? 0) + $request->minutes,
            'extension_reason' => $request->reason,
        ]);

        return back()->with('success', "Waktu berhasil diperpanjang {$request->minutes} menit untuk {$grade->student->name}");
    }
}
