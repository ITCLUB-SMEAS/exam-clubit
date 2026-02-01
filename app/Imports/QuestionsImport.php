<?php

namespace App\Imports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionsImport implements SkipsEmptyRows, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
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
            'exam_id' => $this->examId,
            'question' => $row['question'],
            'question_type' => $row['question_type'] ?? 'multiple_choice_single',
            'difficulty' => $row['difficulty'] ?? 'medium',
            'points' => $row['points'] ?? 1,
            'option_1' => $row['option_1'] ?? null,
            'option_2' => $row['option_2'] ?? null,
            'option_3' => $row['option_3'] ?? null,
            'option_4' => $row['option_4'] ?? null,
            'option_5' => $row['option_5'] ?? null,
            'answer' => $row['answer'] ?? null,
            'correct_answers' => isset($row['correct_answers']) ? json_decode($row['correct_answers'], true) : null,
            'matching_pairs' => isset($row['matching_pairs']) ? json_decode($row['matching_pairs'], true) : null,
        ]);
    }

    /**
     * Batch size for database inserts (reduces number of queries)
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk size for reading from Excel (reduces memory usage)
     */
    public function chunkSize(): int
    {
        return 100;
    }
}
