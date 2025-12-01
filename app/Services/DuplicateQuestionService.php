<?php

namespace App\Services;

use App\Models\Question;

class DuplicateQuestionService
{
    /**
     * Check if question text is duplicate within an exam
     */
    public function checkDuplicate(string $questionText, int $examId, ?int $excludeId = null): array
    {
        $normalized = $this->normalizeText($questionText);
        
        $query = Question::where('exam_id', $examId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $existingQuestions = $query->get();
        
        foreach ($existingQuestions as $existing) {
            $existingNormalized = $this->normalizeText(strip_tags($existing->question));
            $similarity = $this->calculateSimilarity($normalized, $existingNormalized);
            
            if ($similarity >= 85) {
                return [
                    'is_duplicate' => true,
                    'similarity' => $similarity,
                    'duplicate_of' => $existing->id,
                    'duplicate_text' => substr(strip_tags($existing->question), 0, 100) . '...',
                ];
            }
        }
        
        return ['is_duplicate' => false, 'similarity' => 0];
    }

    /**
     * Check duplicates in bulk (for import)
     */
    public function checkBulkDuplicates(array $questions, int $examId): array
    {
        $results = [];
        $newQuestions = [];
        
        foreach ($questions as $index => $question) {
            $text = is_array($question) ? ($question['question'] ?? '') : $question;
            $normalized = $this->normalizeText(strip_tags($text));
            
            // Check against existing questions in exam
            $existingCheck = $this->checkDuplicate($text, $examId);
            if ($existingCheck['is_duplicate']) {
                $results[$index] = [
                    'status' => 'duplicate_existing',
                    'similarity' => $existingCheck['similarity'],
                    'message' => "Duplikat dengan soal ID #{$existingCheck['duplicate_of']}",
                ];
                continue;
            }
            
            // Check against other questions in import batch
            foreach ($newQuestions as $prevIndex => $prevNormalized) {
                $similarity = $this->calculateSimilarity($normalized, $prevNormalized);
                if ($similarity >= 85) {
                    $results[$index] = [
                        'status' => 'duplicate_batch',
                        'similarity' => $similarity,
                        'message' => "Duplikat dengan soal baris #" . ($prevIndex + 1),
                    ];
                    continue 2;
                }
            }
            
            $newQuestions[$index] = $normalized;
            $results[$index] = ['status' => 'ok'];
        }
        
        return $results;
    }

    /**
     * Normalize text for comparison
     */
    private function normalizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Calculate similarity percentage between two texts
     */
    private function calculateSimilarity(string $text1, string $text2): float
    {
        if (empty($text1) || empty($text2)) {
            return 0;
        }
        
        similar_text($text1, $text2, $percent);
        return round($percent, 2);
    }
}
