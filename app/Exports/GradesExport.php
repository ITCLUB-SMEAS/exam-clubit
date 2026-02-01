<?php

namespace App\Exports;

use App\Models\Grade;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradesExport implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithStyles
{
    protected int $examId;

    public function __construct(int $examId)
    {
        $this->examId = $examId;
    }

    /**
     * Use query builder to enable chunking for memory efficiency
     */
    public function query()
    {
        return Grade::query()
            ->with([
                'student:id,name,nisn,classroom_id',
                'student.classroom:id,title',
                'exam:id,title,lesson_id',
                'exam.lesson:id,title',
                'exam_session:id,title',
            ])
            ->where('exam_id', $this->examId)
            ->select('id', 'student_id', 'exam_id', 'exam_session_id', 'grade', 'status')
            ->orderBy('grade', 'desc');
    }

    /**
     * Chunk size for memory-efficient processing
     */
    public function chunkSize(): int
    {
        return 500;
    }

    public function map($grade): array
    {
        return [
            $grade->student?->nisn ?? '-',
            $grade->student?->name ?? '-',
            $grade->student?->classroom?->title ?? '-',
            $grade->exam?->title ?? '-',
            $grade->exam?->lesson?->title ?? '-',
            $grade->exam_session?->title ?? '-',
            $grade->grade,
            $grade->status ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            'NISN',
            'Nama Siswa',
            'Kelas',
            'Ujian',
            'Mata Pelajaran',
            'Sesi',
            'Nilai',
            'Status',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
