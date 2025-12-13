<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GradesExport implements FromCollection, WithMapping, WithHeadings, WithStyles
{    
    protected $grades;
    
    public function __construct($grades)
    {
        $this->grades = $grades;
    }

    public function collection()
    {
        return $this->grades;
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