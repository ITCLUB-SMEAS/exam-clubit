<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Grade;

class AdaptiveTestingService
{
    // Difficulty levels with theta values
    const DIFFICULTY_THETA = [
        'easy' => -1.0,
        'medium' => 0.0,
        'hard' => 1.0,
    ];

    /**
     * Get next question based on student's current ability estimate
     */
    public function getNextQuestion(Grade $grade, array $answeredQuestionIds = []): ?Question
    {
        $exam = $grade->exam;
        
        if (!$exam->adaptive_mode) {
            return null;
        }

        // Calculate current ability estimate
        $theta = $this->estimateAbility($grade);
        
        // Get available questions not yet answered
        $availableQuestions = Question::where('exam_id', $exam->id)
            ->whereNotIn('id', $answeredQuestionIds)
            ->get();

        if ($availableQuestions->isEmpty()) {
            return null;
        }

        // Select question closest to current ability
        return $this->selectOptimalQuestion($availableQuestions, $theta);
    }

    /**
     * Estimate student ability using simple IRT model
     */
    public function estimateAbility(Grade $grade): float
    {
        $answers = Answer::where('exam_id', $grade->exam_id)
            ->where('student_id', $grade->student_id)
            ->where('exam_session_id', $grade->exam_session_id)
            ->with('question')
            ->get();

        if ($answers->isEmpty()) {
            return 0.0; // Start at medium difficulty
        }

        $correct = 0;
        $totalWeight = 0;

        foreach ($answers as $answer) {
            if (!$answer->question) continue;
            
            $difficulty = self::DIFFICULTY_THETA[$answer->question->difficulty] ?? 0;
            $weight = 1 + abs($difficulty); // Harder questions worth more
            
            if ($this->isCorrect($answer)) {
                $correct += $weight;
            }
            $totalWeight += $weight;
        }

        if ($totalWeight == 0) return 0.0;

        // Simple ability estimate: -1 to 1 scale
        $ratio = $correct / $totalWeight;
        return ($ratio - 0.5) * 2;
    }

    /**
     * Select optimal question based on ability
     */
    protected function selectOptimalQuestion($questions, float $theta): Question
    {
        $bestQuestion = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($questions as $question) {
            $questionTheta = self::DIFFICULTY_THETA[$question->difficulty] ?? 0;
            $distance = abs($questionTheta - $theta);
            
            // Add small random factor to avoid predictability
            $distance += (mt_rand(0, 100) / 1000);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestQuestion = $question;
            }
        }

        return $bestQuestion;
    }

    /**
     * Check if answer is correct
     */
    protected function isCorrect(Answer $answer): bool
    {
        if ($answer->answer && $answer->question) {
            return $answer->answer == $answer->question->answer;
        }
        return $answer->is_correct === 'Y' || $answer->is_correct === true;
    }

    /**
     * Get difficulty recommendation for next question
     */
    public function getRecommendedDifficulty(Grade $grade): string
    {
        $theta = $this->estimateAbility($grade);

        if ($theta < -0.3) return 'easy';
        if ($theta > 0.3) return 'hard';
        return 'medium';
    }

    /**
     * Calculate final adaptive score with ability weighting
     */
    public function calculateAdaptiveScore(Grade $grade): array
    {
        $answers = Answer::where('exam_id', $grade->exam_id)
            ->where('student_id', $grade->student_id)
            ->where('exam_session_id', $grade->exam_session_id)
            ->with('question')
            ->get();

        $totalScore = 0;
        $maxScore = 0;
        $abilityEstimate = $this->estimateAbility($grade);

        foreach ($answers as $answer) {
            if (!$answer->question) continue;
            
            $points = $answer->question->points ?? 1;
            $difficultyMultiplier = $this->getDifficultyMultiplier($answer->question->difficulty);
            
            $maxScore += $points * $difficultyMultiplier;
            
            if ($this->isCorrect($answer)) {
                $totalScore += $points * $difficultyMultiplier;
            }
        }

        $percentage = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        return [
            'raw_score' => $totalScore,
            'max_score' => $maxScore,
            'percentage' => round($percentage, 2),
            'ability_estimate' => round($abilityEstimate, 3),
            'ability_level' => $this->getAbilityLevel($abilityEstimate),
        ];
    }

    protected function getDifficultyMultiplier(string $difficulty): float
    {
        return match($difficulty) {
            'easy' => 1.0,
            'medium' => 1.25,
            'hard' => 1.5,
            default => 1.0,
        };
    }

    protected function getAbilityLevel(float $theta): string
    {
        if ($theta < -0.5) return 'Perlu Bimbingan';
        if ($theta < 0) return 'Cukup';
        if ($theta < 0.5) return 'Baik';
        return 'Sangat Baik';
    }
}
