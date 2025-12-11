<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Grade;
use App\Models\Question;
use App\Models\ExamViolation;
use Illuminate\Support\Facades\Cache;

class AnswerTimingService
{
    // Minimum seconds to read and answer based on question type
    const MIN_TIME_BY_TYPE = [
        'multiple_choice_single' => 3,
        'multiple_choice_multiple' => 5,
        'true_false' => 2,
        'short_answer' => 5,
        'essay' => 15,
        'matching' => 8,
    ];

    // Minimum seconds based on question length (per 100 chars)
    const SECONDS_PER_100_CHARS = 2;

    // Threshold for flagging (consecutive fast answers)
    const CONSECUTIVE_FAST_THRESHOLD = 3;

    // Cache key prefix for tracking
    const CACHE_PREFIX = 'answer_timing:';

    /**
     * Validate answer timing and record if suspicious
     */
    public static function validateAndRecord(
        Grade $grade,
        Question $question,
        int $questionNumber
    ): array {
        $cacheKey = self::CACHE_PREFIX . $grade->id;
        $timingData = Cache::get($cacheKey, [
            'last_answer_time' => null,
            'last_question_viewed' => null,
            'question_view_time' => null,
            'consecutive_fast' => 0,
            'total_fast' => 0,
            'answers_timing' => [],
        ]);

        $now = now();
        $result = [
            'is_suspicious' => false,
            'reason' => null,
            'time_spent' => null,
        ];

        // Calculate time spent on this question
        if ($timingData['question_view_time'] && $timingData['last_question_viewed'] == $questionNumber) {
            $viewTime = \Carbon\Carbon::parse($timingData['question_view_time']);
            $timeSpent = $viewTime->diffInSeconds($now);
            $result['time_spent'] = $timeSpent;

            // Get minimum expected time
            $minTime = self::getMinimumTime($question);

            if ($timeSpent < $minTime) {
                $timingData['consecutive_fast']++;
                $timingData['total_fast']++;

                $result['is_suspicious'] = true;
                $result['reason'] = "Jawaban terlalu cepat ({$timeSpent}s, minimum {$minTime}s)";

                // Record violation if threshold reached
                if ($timingData['consecutive_fast'] >= self::CONSECUTIVE_FAST_THRESHOLD) {
                    self::recordTimingViolation($grade, $timingData['consecutive_fast'], $timeSpent);
                    $timingData['consecutive_fast'] = 0; // Reset after recording
                }
            } else {
                $timingData['consecutive_fast'] = 0; // Reset on normal answer
            }

            // Store timing for this answer
            $timingData['answers_timing'][$questionNumber] = [
                'time_spent' => $timeSpent,
                'min_expected' => $minTime,
                'is_fast' => $timeSpent < $minTime,
                'answered_at' => $now->toDateTimeString(),
            ];
        }

        // Update last answer time
        $timingData['last_answer_time'] = $now->toDateTimeString();

        // Save to cache (expires after exam duration + buffer)
        Cache::put($cacheKey, $timingData, now()->addHours(3));

        return $result;
    }

    /**
     * Record when student views a question
     */
    public static function recordQuestionView(Grade $grade, int $questionNumber): void
    {
        $cacheKey = self::CACHE_PREFIX . $grade->id;
        $timingData = Cache::get($cacheKey, [
            'last_answer_time' => null,
            'last_question_viewed' => null,
            'question_view_time' => null,
            'consecutive_fast' => 0,
            'total_fast' => 0,
            'answers_timing' => [],
        ]);

        $timingData['last_question_viewed'] = $questionNumber;
        $timingData['question_view_time'] = now()->toDateTimeString();

        Cache::put($cacheKey, $timingData, now()->addHours(3));
    }

    /**
     * Get minimum expected time for a question
     */
    protected static function getMinimumTime(Question $question): int
    {
        $baseTime = self::MIN_TIME_BY_TYPE[$question->question_type] ?? 3;
        
        // Add time based on question length
        $questionLength = strlen(strip_tags($question->question ?? ''));
        $readingTime = ceil($questionLength / 100) * self::SECONDS_PER_100_CHARS;

        // Add time for options (multiple choice)
        $optionsTime = 0;
        if (in_array($question->question_type, ['multiple_choice_single', 'multiple_choice_multiple'])) {
            for ($i = 1; $i <= 5; $i++) {
                $option = $question->{"option_$i"};
                if ($option) {
                    $optionsTime += ceil(strlen(strip_tags($option)) / 100);
                }
            }
        }

        return $baseTime + $readingTime + $optionsTime;
    }

    /**
     * Record timing violation
     */
    protected static function recordTimingViolation(Grade $grade, int $consecutiveCount, int $lastTimeSpent): void
    {
        ExamViolation::create([
            'exam_id' => $grade->exam_id,
            'exam_session_id' => $grade->exam_session_id,
            'student_id' => $grade->student_id,
            'grade_id' => $grade->id,
            'violation_type' => 'fast_answer',
            'description' => "{$consecutiveCount} jawaban berturut-turut dijawab terlalu cepat (terakhir: {$lastTimeSpent}s)",
            'metadata' => [
                'consecutive_count' => $consecutiveCount,
                'last_time_spent' => $lastTimeSpent,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Update grade violation count
        $grade->increment('violation_count');

        // Flag if too many fast answers
        $cacheKey = self::CACHE_PREFIX . $grade->id;
        $timingData = Cache::get($cacheKey, []);
        
        if (($timingData['total_fast'] ?? 0) >= 10 && !$grade->is_flagged) {
            $grade->update([
                'is_flagged' => true,
                'flag_reason' => 'Terlalu banyak jawaban cepat: ' . $timingData['total_fast'] . ' jawaban',
            ]);
        }
    }

    /**
     * Get timing summary for a grade
     */
    public static function getSummary(Grade $grade): array
    {
        $cacheKey = self::CACHE_PREFIX . $grade->id;
        $timingData = Cache::get($cacheKey, []);

        $answersTimings = $timingData['answers_timing'] ?? [];
        $fastCount = collect($answersTimings)->where('is_fast', true)->count();
        $totalAnswers = count($answersTimings);
        $avgTime = $totalAnswers > 0 
            ? round(collect($answersTimings)->avg('time_spent'), 1) 
            : 0;

        return [
            'total_answers' => $totalAnswers,
            'fast_answers' => $fastCount,
            'fast_percentage' => $totalAnswers > 0 ? round(($fastCount / $totalAnswers) * 100, 1) : 0,
            'average_time' => $avgTime,
            'consecutive_fast_max' => $timingData['consecutive_fast'] ?? 0,
        ];
    }

    /**
     * Clear timing data for a grade
     */
    public static function clear(Grade $grade): void
    {
        Cache::forget(self::CACHE_PREFIX . $grade->id);
    }
}
