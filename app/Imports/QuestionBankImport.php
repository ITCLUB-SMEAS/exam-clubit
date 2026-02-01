<?php

namespace App\Imports;

use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuestionBankImport implements SkipsEmptyRows, ToModel, WithBatchInserts, WithChunkReading, WithHeadingRow
{
    /**
     * Cache for categories to avoid repeated database queries
     */
    protected array $categoryCache = [];

    public function model(array $row)
    {
        // Skip if soal is blank
        if (empty($row['soal']) || trim($row['soal']) === '') {
            return null;
        }

        // Skip if tipe is blank
        if (empty($row['tipe']) || trim($row['tipe']) === '') {
            return null;
        }

        // Find or create category (with local caching)
        $categoryId = null;
        if (! empty($row['kategori'])) {
            $categoryName = trim($row['kategori']);

            // Check local cache first
            if (! isset($this->categoryCache[$categoryName])) {
                $category = QuestionCategory::firstOrCreate(
                    ['name' => $categoryName]
                );
                $this->categoryCache[$categoryName] = $category->id;
            }
            $categoryId = $this->categoryCache[$categoryName];
        }

        return new QuestionBank([
            'category_id' => $categoryId,
            'question' => trim($row['soal']),
            'question_type' => trim($row['tipe']),
            'difficulty' => strtolower(trim($row['tingkat_kesulitan'] ?? 'medium')),
            'points' => $row['poin'] ?? 1,
            'option_1' => ! empty($row['opsi_1']) ? trim($row['opsi_1']) : null,
            'option_2' => ! empty($row['opsi_2']) ? trim($row['opsi_2']) : null,
            'option_3' => ! empty($row['opsi_3']) ? trim($row['opsi_3']) : null,
            'option_4' => ! empty($row['opsi_4']) ? trim($row['opsi_4']) : null,
            'option_5' => ! empty($row['opsi_5']) ? trim($row['opsi_5']) : null,
            'answer' => ! empty($row['jawaban']) ? trim($row['jawaban']) : null,
            'correct_answers' => ! empty($row['jawaban_benar_multiple'])
                ? array_map('trim', explode(',', $row['jawaban_benar_multiple']))
                : null,
            'matching_pairs' => ! empty($row['pasangan_matching'])
                ? json_decode($row['pasangan_matching'], true)
                : null,
            'tags' => ! empty($row['tags'])
                ? array_map('trim', explode(',', $row['tags']))
                : null,
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
