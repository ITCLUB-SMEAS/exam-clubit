<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements ToModel, WithHeadingRow
{
    protected int $examId;

    public function __construct(int $examId)
    {
        $this->examId = $examId;
    }

    public function model(array $row)
    {
        // Skip blank rows
        if (empty($row['question']) || trim($row['question']) === '') {
            return null;
        }

        return new Question([
            'exam_id'   => $this->examId,
            'question'  => $row['question'],
            'option_1'  => $row['option_1'] ?? null,
            'option_2'  => $row['option_2'] ?? null,
            'option_3'  => $row['option_3'] ?? null,
            'option_4'  => $row['option_4'] ?? null,
            'option_5'  => $row['option_5'] ?? null,
            'answer'    => $row['answer'] ?? null,
        ]);
    }
}