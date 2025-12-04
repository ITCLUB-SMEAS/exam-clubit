<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    /**
     * Show attendance monitoring page
     */
    public function show(ExamSession $examSession)
    {
        $examSession->load(['exam', 'exam_groups.student.classroom']);
        
        // Generate token if not exists
        if (!$examSession->access_token) {
            $examSession->generateAccessToken();
        }
        if (!$examSession->qr_secret) {
            $examSession->generateQrSecret();
        }

        return Inertia::render('Admin/Attendance/Show', [
            'examSession' => $examSession,
        ]);
    }

    /**
     * Get current QR code (API endpoint for refresh)
     */
    public function getQrCode(ExamSession $examSession)
    {
        return response()->json([
            'qr_code' => $examSession->getCurrentQrCode(),
            'session_id' => $examSession->id,
        ]);
    }

    /**
     * Regenerate access token
     */
    public function regenerateToken(ExamSession $examSession)
    {
        $token = $examSession->generateAccessToken();
        
        return back()->with('success', "Token baru: {$token}");
    }

    /**
     * Toggle require attendance setting
     */
    public function toggleRequirement(ExamSession $examSession)
    {
        $examSession->update([
            'require_attendance' => !$examSession->require_attendance
        ]);

        $status = $examSession->require_attendance ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Absensi wajib {$status}");
    }

    /**
     * Manual check-in by admin
     */
    public function manualCheckIn(ExamSession $examSession, Request $request)
    {
        $request->validate(['student_id' => 'required|exists:students,id']);

        $examGroup = $examSession->exam_groups()
            ->where('student_id', $request->student_id)
            ->first();

        if (!$examGroup) {
            return back()->with('error', 'Siswa tidak terdaftar di sesi ini');
        }

        $examGroup->checkIn('manual', $request->ip());

        return back()->with('success', 'Siswa berhasil di-checkin');
    }

    /**
     * Get attendance list (API for real-time update)
     */
    public function getAttendanceList(ExamSession $examSession)
    {
        $examGroups = $examSession->exam_groups()
            ->with('student.classroom')
            ->get()
            ->map(fn($eg) => [
                'id' => $eg->id,
                'student_id' => $eg->student_id,
                'student_name' => $eg->student->name,
                'nisn' => $eg->student->nisn,
                'classroom' => $eg->student->classroom->name ?? '-',
                'checked_in' => $eg->isCheckedIn(),
                'checked_in_at' => $eg->checked_in_at?->format('H:i:s'),
                'checkin_method' => $eg->checkin_method,
            ]);

        return response()->json([
            'attendances' => $examGroups,
            'total' => $examGroups->count(),
            'checked_in' => $examGroups->where('checked_in', true)->count(),
        ]);
    }
}
