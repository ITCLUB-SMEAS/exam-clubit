<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Collection;

class PlagiarismService
{
    const SIMILARITY_THRESHOLD = 70; // Percentage

    /**
     * Check plagiarism for essay answers in an exam session
     */
    public function checkExamSession(int $examId, int $examSessionId): array
    {
        $essayQuestions = Question::where('exam_id', $examId)
            ->whereIn('question_type', ['essay', 'short_answer'])
            ->pluck('id');

        if ($essayQuestions->isEmpty()) {
            return [];
        }

        $answers = Answer::whereIn('question_id', $essayQuestions)
            ->where('exam_session_id', $examSessionId)
            ->whereNotNull('answer_text')
            ->where('answer_text', '!=', '')
            ->with('student:id,name,nisn')
            ->get();

        $results = [];

        foreach ($essayQuestions as $questionId) {
            $questionAnswers = $answers->where('question_id', $questionId);
            $similarities = $this->compareAnswers($questionAnswers);
            
            if (!empty($similarities)) {
                $question = Question::find($questionId);
                $results[] = [
                    'question_id' => $questionId,
                    'question_text' => strip_tags(substr($question->question, 0, 100)) . '...',
                    'similarities' => $similarities,
                ];
            }
        }

        return $results;
    }

    /**
     * Compare all answers for a question
     */
    protected function compareAnswers(Collection $answers): array
    {
        $similarities = [];
        $answersArray = $answers->values()->all();
        $count = count($answersArray);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $text1 = $this->normalizeText($answersArray[$i]->answer_text);
                $text2 = $this->normalizeText($answersArray[$j]->answer_text);

                if (strlen($text1) < 20 || strlen($text2) < 20) {
                    continue; // Skip very short answers
                }

                $similarity = $this->calculateSimilarity($text1, $text2);

                if ($similarity >= self::SIMILARITY_THRESHOLD) {
                    $similarities[] = [
                        'student1' => [
                            'id' => $answersArray[$i]->student_id,
                            'name' => $answersArray[$i]->student->name ?? 'Unknown',
                            'nisn' => $answersArray[$i]->student->nisn ?? '-',
                        ],
                        'student2' => [
                            'id' => $answersArray[$j]->student_id,
                            'name' => $answersArray[$j]->student->name ?? 'Unknown',
                            'nisn' => $answersArray[$j]->student->nisn ?? '-',
                        ],
                        'similarity' => round($similarity, 1),
                        'answer1_preview' => substr($text1, 0, 150) . '...',
                        'answer2_preview' => substr($text2, 0, 150) . '...',
                    ];
                }
            }
        }

        // Sort by similarity descending
        usort($similarities, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $similarities;
    }

    /**
     * Calculate similarity between two texts using multiple methods
     */
    protected function calculateSimilarity(string $text1, string $text2): float
    {
        // Method 1: Similar text percentage
        similar_text($text1, $text2, $percent1);

        // Method 2: Levenshtein-based (for shorter texts)
        $maxLen = max(strlen($text1), strlen($text2));
        if ($maxLen <= 255) {
            $lev = levenshtein(substr($text1, 0, 255), substr($text2, 0, 255));
            $percent2 = (1 - ($lev / $maxLen)) * 100;
        } else {
            $percent2 = $percent1;
        }

        // Method 3: Word-based Jaccard similarity
        $words1 = array_unique(explode(' ', $text1));
        $words2 = array_unique(explode(' ', $text2));
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        $percent3 = $union > 0 ? ($intersection / $union) * 100 : 0;

        // Weighted average
        return ($percent1 * 0.4) + ($percent2 * 0.3) + ($percent3 * 0.3);
    }

    /**
     * Normalize text for comparison
     */
    protected function normalizeText(string $text): string
    {
        $text = strip_tags($text);
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
