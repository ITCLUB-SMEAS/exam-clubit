<?php

namespace App\Services;

use App\Models\Question;
use App\Models\Exam;

class ExamScoringService
{
    /**
     * Score an answer based on question type
     *
     * @return array [isCorrect, pointsAwarded, needsReview]
     */
    public function scoreAnswer(
        Question $question,
        $submittedAnswer,
        ?string $submittedText,
        $submittedOptions,
        $matchingAnswers = null,
        ?Exam $exam = null
    ): array {
        $type = $question->question_type ?? Question::TYPE_MULTIPLE_CHOICE_SINGLE;
        $pointsAvailable = $question->points ?? 1;
        $exam = $exam ?? $question->exam;

        return match ($type) {
            Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            Question::TYPE_TRUE_FALSE => $this->scoreMultipleChoiceSingle($question, $submittedAnswer, $pointsAvailable, $exam),
            Question::TYPE_MULTIPLE_CHOICE_MULTIPLE => $this->scoreMultipleChoiceMultiple($question, $submittedOptions ?? $submittedAnswer, $pointsAvailable, $exam),
            Question::TYPE_SHORT_ANSWER => $this->scoreShortAnswer($question, $submittedText ?? $submittedAnswer, $pointsAvailable),
            Question::TYPE_ESSAY => ['N', 0, true],
            Question::TYPE_MATCHING => $this->scoreMatching($question, $matchingAnswers, $pointsAvailable, $exam),
            default => $this->scoreMultipleChoiceSingle($question, $submittedAnswer, $pointsAvailable, $exam),
        };
    }

    protected function scoreMultipleChoiceSingle(Question $question, $answer, float $points, ?Exam $exam): array
    {
        $isCorrect = (string) $question->answer === (string) $answer;
        
        if ($isCorrect) {
            return ['Y', $points, false];
        }
        
        // Apply negative marking if enabled and answer was submitted
        if ($exam && $exam->enable_negative_marking && $answer !== null && $answer !== '') {
            $penalty = -1 * ($points * ($exam->negative_marking_percentage / 100));
            return ['N', $penalty, false];
        }
        
        return ['N', 0, false];
    }

    protected function scoreMultipleChoiceMultiple(Question $question, $submitted, float $points, ?Exam $exam): array
    {
        $correct = $this->normalizeOptionArray($question->correct_answers);
        $submitted = $this->normalizeOptionArray($submitted);

        // Fully correct
        if (!empty($correct) && $correct === $submitted) {
            return ['Y', $points, false];
        }

        // Partial credit if enabled
        if ($exam && $exam->enable_partial_credit && !empty($correct) && !empty($submitted)) {
            $correctCount = count(array_intersect($correct, $submitted));
            $incorrectCount = count(array_diff($submitted, $correct));
            $totalCorrect = count($correct);

            if ($correctCount > 0) {
                $partialPoints = round(($correctCount / $totalCorrect) * $points, 2);
                
                // Deduct for incorrect selections if negative marking enabled
                if ($exam->enable_negative_marking && $incorrectCount > 0) {
                    $penalty = ($incorrectCount / $totalCorrect) * $points * ($exam->negative_marking_percentage / 100);
                    $partialPoints = max(0, $partialPoints - $penalty);
                }
                
                return ['N', $partialPoints, false];
            }
        }

        // Apply negative marking if enabled and answer was submitted
        if ($exam && $exam->enable_negative_marking && !empty($submitted)) {
            $penalty = -1 * ($points * ($exam->negative_marking_percentage / 100));
            return ['N', $penalty, false];
        }

        return ['N', 0, false];
    }

    protected function scoreShortAnswer(Question $question, $submitted, float $points): array
    {
        $normalizedSubmitted = $this->normalizeText($submitted);
        $correctAnswers = array_map(
            fn($text) => $this->normalizeText($text),
            $question->correct_answers ?? []
        );

        if ($normalizedSubmitted !== null && in_array($normalizedSubmitted, $correctAnswers, true)) {
            return ['Y', $points, false];
        }
        return ['N', 0, false];
    }

    protected function scoreMatching(Question $question, $submittedPairs, float $points, ?Exam $exam): array
    {
        $correctPairs = $question->matching_pairs ?? [];
        
        if (empty($correctPairs) || empty($submittedPairs)) {
            return ['N', 0, false];
        }

        $correctCount = 0;
        $totalPairs = count($correctPairs);

        foreach ($correctPairs as $pair) {
            $leftKey = $pair['left'] ?? '';
            $correctRight = $pair['right'] ?? '';

            if (isset($submittedPairs[$leftKey]) && $submittedPairs[$leftKey] === $correctRight) {
                $correctCount++;
            }
        }

        if ($correctCount === $totalPairs) {
            return ['Y', $points, false];
        }
        
        // Partial scoring (always enabled for matching)
        $partialPoints = $correctCount > 0 ? round(($correctCount / $totalPairs) * $points, 2) : 0;
        return ['N', $partialPoints, false];
    }

    protected function normalizeOptionArray($value): array
    {
        if (is_null($value)) {
            return [];
        }

        $array = is_array($value) ? $value : explode(',', (string) $value);
        $array = array_filter(array_map('trim', $array), fn($v) => $v !== '');
        sort($array);

        return array_values(array_map('strval', $array));
    }

    protected function normalizeText($text): ?string
    {
        if ($text === null) {
            return null;
        }

        $normalized = trim(strtolower((string) $text));
        return $normalized === '' ? null : $normalized;
    }
}

