<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Question;
use Illuminate\Support\Collection;

class ItemAnalysisService
{
    /**
     * Analyze all questions in an exam
     */
    public function analyzeExam(Exam $exam): array
    {
        $questions = Question::where('exam_id', $exam->id)->get();
        $grades = Grade::where('exam_id', $exam->id)
            ->whereNotNull('end_time')
            ->orderBy('grade', 'desc')
            ->get();

        if ($grades->isEmpty()) {
            return ['questions' => [], 'summary' => null];
        }

        // Split into upper and lower groups (27% each) for discrimination index
        $groupSize = max(1, (int) ceil($grades->count() * 0.27));
        $upperGroup = $grades->take($groupSize)->pluck('student_id');
        $lowerGroup = $grades->reverse()->take($groupSize)->pluck('student_id');

        $analysis = [];
        foreach ($questions as $question) {
            $analysis[] = $this->analyzeQuestion($question, $grades, $upperGroup, $lowerGroup);
        }

        return [
            'questions' => $analysis,
            'summary' => $this->generateSummary($analysis),
            'total_students' => $grades->count(),
        ];
    }

    /**
     * Analyze a single question
     */
    protected function analyzeQuestion(Question $question, Collection $grades, Collection $upperGroup, Collection $lowerGroup): array
    {
        $answers = Answer::where('question_id', $question->id)
            ->whereIn('student_id', $grades->pluck('student_id'))
            ->get();

        $totalAnswers = $answers->count();
        $correctAnswers = $answers->where('is_correct', 'Y')->count();

        // Difficulty Index (P) = correct / total (0-1, lower = harder)
        $difficultyIndex = $totalAnswers > 0 ? round($correctAnswers / $totalAnswers, 3) : 0;

        // Discrimination Index (D) = (upper correct - lower correct) / group size
        $upperCorrect = $answers->whereIn('student_id', $upperGroup)->where('is_correct', 'Y')->count();
        $lowerCorrect = $answers->whereIn('student_id', $lowerGroup)->where('is_correct', 'Y')->count();
        $groupSize = max(1, $upperGroup->count());
        $discriminationIndex = round(($upperCorrect - $lowerCorrect) / $groupSize, 3);

        // Distractor analysis (for multiple choice)
        $distractorAnalysis = $this->analyzeDistractors($question, $answers);

        return [
            'question_id' => $question->id,
            'question_text' => strip_tags(substr($question->question, 0, 100)) . '...',
            'question_type' => $question->question_type,
            'total_answers' => $totalAnswers,
            'correct_answers' => $correctAnswers,
            'difficulty_index' => $difficultyIndex,
            'difficulty_label' => $this->getDifficultyLabel($difficultyIndex),
            'discrimination_index' => $discriminationIndex,
            'discrimination_label' => $this->getDiscriminationLabel($discriminationIndex),
            'recommendation' => $this->getRecommendation($difficultyIndex, $discriminationIndex),
            'distractors' => $distractorAnalysis,
        ];
    }

    /**
     * Analyze distractor effectiveness
     */
    protected function analyzeDistractors(Question $question, Collection $answers): array
    {
        if (!in_array($question->question_type, ['multiple_choice_single', 'true_false'])) {
            return [];
        }

        $distribution = [];
        $options = ['1' => $question->option_1, '2' => $question->option_2];
        if ($question->option_3) $options['3'] = $question->option_3;
        if ($question->option_4) $options['4'] = $question->option_4;
        if ($question->option_5) $options['5'] = $question->option_5;

        $correctAnswer = (string) $question->answer;

        foreach ($options as $key => $text) {
            $count = $answers->where('answer', $key)->count();
            $percentage = $answers->count() > 0 ? round(($count / $answers->count()) * 100, 1) : 0;
            $distribution[] = [
                'option' => chr(64 + (int)$key), // A, B, C, D, E
                'text' => substr(strip_tags($text ?? ''), 0, 50),
                'count' => $count,
                'percentage' => $percentage,
                'is_correct' => $key === $correctAnswer,
            ];
        }

        return $distribution;
    }

    /**
     * Get difficulty label
     */
    protected function getDifficultyLabel(float $index): string
    {
        if ($index >= 0.8) return 'Sangat Mudah';
        if ($index >= 0.6) return 'Mudah';
        if ($index >= 0.4) return 'Sedang';
        if ($index >= 0.2) return 'Sulit';
        return 'Sangat Sulit';
    }

    /**
     * Get discrimination label
     */
    protected function getDiscriminationLabel(float $index): string
    {
        if ($index >= 0.4) return 'Sangat Baik';
        if ($index >= 0.3) return 'Baik';
        if ($index >= 0.2) return 'Cukup';
        if ($index >= 0.1) return 'Kurang';
        return 'Buruk';
    }

    /**
     * Get recommendation for question
     */
    protected function getRecommendation(float $difficulty, float $discrimination): string
    {
        if ($discrimination < 0.1) {
            return 'Revisi - Daya pembeda sangat rendah';
        }
        if ($difficulty > 0.9) {
            return 'Pertimbangkan - Terlalu mudah';
        }
        if ($difficulty < 0.2) {
            return 'Pertimbangkan - Terlalu sulit';
        }
        if ($discrimination >= 0.3 && $difficulty >= 0.3 && $difficulty <= 0.7) {
            return 'Pertahankan - Soal berkualitas baik';
        }
        return 'Tinjau ulang';
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(array $analysis): array
    {
        if (empty($analysis)) return [];

        $difficulties = array_column($analysis, 'difficulty_index');
        $discriminations = array_column($analysis, 'discrimination_index');

        $goodQuestions = count(array_filter($analysis, fn($q) => 
            $q['discrimination_index'] >= 0.3 && $q['difficulty_index'] >= 0.3 && $q['difficulty_index'] <= 0.7
        ));

        $needsRevision = count(array_filter($analysis, fn($q) => 
            $q['discrimination_index'] < 0.1
        ));

        return [
            'avg_difficulty' => round(array_sum($difficulties) / count($difficulties), 3),
            'avg_discrimination' => round(array_sum($discriminations) / count($discriminations), 3),
            'good_questions' => $goodQuestions,
            'needs_revision' => $needsRevision,
            'total_questions' => count($analysis),
            'quality_percentage' => round(($goodQuestions / count($analysis)) * 100, 1),
        ];
    }
}
