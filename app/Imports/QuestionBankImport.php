<?php

namespace App\Imports;

use App\Models\QuestionBank;
use App\Models\QuestionCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class QuestionBankImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Find or create category
        $category = null;
        if (!empty($row['kategori'])) {
            $category = QuestionCategory::firstOrCreate(
                ['name' => $row['kategori']]
            );
        }

        return new QuestionBank([
            'category_id' => $category?->id,
            'question' => $row['soal'],
            'question_type' => $row['tipe'],
            'difficulty' => strtolower($row['tingkat_kesulitan'] ?? 'medium'),
            'points' => $row['poin'] ?? 1,
            'option_1' => $row['opsi_1'] ?? null,
            'option_2' => $row['opsi_2'] ?? null,
            'option_3' => $row['opsi_3'] ?? null,
            'option_4' => $row['opsi_4'] ?? null,
            'option_5' => $row['opsi_5'] ?? null,
            'answer' => $row['jawaban'] ?? null,
            'correct_answers' => !empty($row['jawaban_benar_multiple']) 
                ? explode(',', $row['jawaban_benar_multiple']) 
                : null,
            'matching_pairs' => !empty($row['pasangan_matching']) 
                ? json_decode($row['pasangan_matching'], true) 
                : null,
            'tags' => !empty($row['tags']) 
                ? explode(',', $row['tags']) 
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'soal' => 'required|string',
            'tipe' => 'required|in:multiple_choice_single,multiple_choice_multiple,true_false,short_answer,essay,matching',
            'poin' => 'nullable|numeric|min:0',
        ];
    }
}
