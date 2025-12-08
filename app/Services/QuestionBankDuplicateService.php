<?php

namespace App\Services;

use App\Models\QuestionBank;
use Illuminate\Support\Str;

class QuestionBankDuplicateService
{
    public function findSimilar(string $question, int $threshold = 85): array
    {
        $allQuestions = QuestionBank::select('id', 'question')->get();
        $similar = [];

        foreach ($allQuestions as $existing) {
            $similarity = $this->calculateSimilarity($question, $existing->question);
            
            if ($similarity >= $threshold) {
                $similar[] = [
                    'id' => $existing->id,
                    'question' => $existing->question,
                    'similarity' => round($similarity, 2),
                ];
            }
        }

        usort($similar, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $similar;
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        $str1 = $this->normalize($str1);
        $str2 = $this->normalize($str2);

        similar_text($str1, $str2, $percent);

        return $percent;
    }

    protected function normalize(string $text): string
    {
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
