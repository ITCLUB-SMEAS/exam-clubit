<?php

namespace App\Exports;

use App\Models\QuestionBank;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class QuestionBankExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = QuestionBank::with('category');

        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }
        if (!empty($this->filters['difficulty'])) {
            $query->where('difficulty', $this->filters['difficulty']);
        }
        if (!empty($this->filters['question_type'])) {
            $query->where('question_type', $this->filters['question_type']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Kategori',
            'Soal',
            'Tipe',
            'Tingkat Kesulitan',
            'Poin',
            'Opsi 1',
            'Opsi 2',
            'Opsi 3',
            'Opsi 4',
            'Opsi 5',
            'Jawaban',
            'Jawaban Benar (Multiple)',
            'Pasangan (Matching)',
            'Tags',
            'Digunakan',
            'Success Rate (%)',
            'Terakhir Digunakan',
        ];
    }

    public function map($question): array
    {
        return [
            $question->id,
            $question->category->name ?? '-',
            strip_tags($question->question),
            $question->question_type,
            ucfirst($question->difficulty),
            $question->points,
            $question->option_1,
            $question->option_2,
            $question->option_3,
            $question->option_4,
            $question->option_5,
            $question->answer,
            is_array($question->correct_answers) ? implode(', ', $question->correct_answers) : '',
            is_array($question->matching_pairs) ? json_encode($question->matching_pairs) : '',
            is_array($question->tags) ? implode(', ', $question->tags) : '',
            $question->usage_count,
            $question->success_rate,
            $question->last_used_at?->format('Y-m-d H:i:s'),
        ];
    }
}
