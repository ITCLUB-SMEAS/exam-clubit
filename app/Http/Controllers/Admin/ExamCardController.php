<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ExamCardController extends Controller
{
    public function preview(ExamSession $examSession)
    {
        $examSession->load(['exam.lesson', 'exam.classroom', 'exam_groups.student.classroom', 'exam_groups.student.room']);
        
        return inertia('Admin/ExamCards/Preview', [
            'examSession' => $examSession,
            'students' => $examSession->exam_groups->map(fn($eg) => [
                'id' => $eg->student->id,
                'nisn' => $eg->student->nisn,
                'name' => $eg->student->name,
                'classroom' => $eg->student->classroom->title ?? '-',
                'room' => $eg->student->room->name ?? '-',
                'gender' => $eg->student->gender,
                'photo' => $eg->student->photo,
            ]),
        ]);
    }

    public function print(ExamSession $examSession, Request $request)
    {
        $examSession->load(['exam.lesson', 'exam.classroom', 'exam_groups.student.classroom', 'exam_groups.student.room']);

        $examGroups = $examSession->exam_groups;
        if ($request->has('students')) {
            $studentIds = explode(',', $request->students);
            $examGroups = $examGroups->filter(fn($eg) => in_array($eg->student_id, $studentIds));
        }

        $students = $examGroups->map(fn($eg) => [
            'id' => $eg->student->id,
            'nisn' => $eg->student->nisn,
            'name' => $eg->student->name,
            'classroom' => $eg->student->classroom->title ?? '-',
            'room' => $eg->student->room->name ?? '-',
            'gender' => $eg->student->gender,
            'photo' => $eg->student->photo,
        ]);

        $pdf = Pdf::loadView('exports.exam-cards', [
            'examSession' => $examSession,
            'exam' => $examSession->exam,
            'students' => $students,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('kartu-peserta-' . str_replace(' ', '-', strtolower($examSession->exam->title)) . '.pdf');
    }

    public function printSingle(ExamSession $examSession, $studentId)
    {
        $examSession->load(['exam.lesson', 'exam.classroom']);
        
        $examGroup = $examSession->exam_groups()
            ->where('student_id', $studentId)
            ->with(['student.classroom', 'student.room'])
            ->first();

        if (!$examGroup) {
            return back()->with('error', 'Siswa tidak ditemukan di sesi ini');
        }

        $student = [
            'id' => $examGroup->student->id,
            'nisn' => $examGroup->student->nisn,
            'name' => $examGroup->student->name,
            'classroom' => $examGroup->student->classroom->title ?? '-',
            'room' => $examGroup->student->room->name ?? '-',
            'gender' => $examGroup->student->gender,
            'photo' => $examGroup->student->photo,
        ];

        $pdf = Pdf::loadView('exports.exam-cards', [
            'examSession' => $examSession,
            'exam' => $examSession->exam,
            'students' => collect([$student]),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('kartu-' . $examGroup->student->nisn . '.pdf');
    }
}
