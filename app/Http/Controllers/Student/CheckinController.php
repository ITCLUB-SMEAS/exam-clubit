<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckinController extends Controller
{
    /**
     * Check-in via QR code
     */
    public function qrCheckin(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'session_id' => 'required|integer',
        ]);

        $student = Auth::guard('student')->user();
        $examSession = ExamSession::find($request->session_id);

        if (!$examSession) {
            return response()->json(['success' => false, 'message' => 'Sesi ujian tidak ditemukan'], 404);
        }

        // Validate QR code
        if (!$examSession->validateQrCode($request->qr_code)) {
            return response()->json(['success' => false, 'message' => 'QR Code tidak valid atau sudah expired'], 400);
        }

        // Find student enrollment
        $examGroup = ExamGroup::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$examGroup) {
            return response()->json(['success' => false, 'message' => 'Anda tidak terdaftar di sesi ujian ini'], 403);
        }

        if ($examGroup->isCheckedIn()) {
            return response()->json(['success' => true, 'message' => 'Anda sudah melakukan absensi']);
        }

        $examGroup->checkIn('qr', $request->ip());

        return response()->json(['success' => true, 'message' => 'Absensi berhasil!']);
    }

    /**
     * Check-in via token
     */
    public function tokenCheckin(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:6',
            'session_id' => 'required|integer',
        ]);

        $student = Auth::guard('student')->user();
        $examSession = ExamSession::find($request->session_id);

        if (!$examSession) {
            return response()->json(['success' => false, 'message' => 'Sesi ujian tidak ditemukan'], 404);
        }

        // Validate token
        if (strtoupper($request->token) !== $examSession->access_token) {
            return response()->json(['success' => false, 'message' => 'Token tidak valid'], 400);
        }

        // Find student enrollment
        $examGroup = ExamGroup::where('exam_session_id', $examSession->id)
            ->where('student_id', $student->id)
            ->first();

        if (!$examGroup) {
            return response()->json(['success' => false, 'message' => 'Anda tidak terdaftar di sesi ujian ini'], 403);
        }

        if ($examGroup->isCheckedIn()) {
            return response()->json(['success' => true, 'message' => 'Anda sudah melakukan absensi']);
        }

        $examGroup->checkIn('token', $request->ip());

        return response()->json(['success' => true, 'message' => 'Absensi berhasil!']);
    }
}
