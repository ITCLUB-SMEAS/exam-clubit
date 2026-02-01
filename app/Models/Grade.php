<?php

namespace App\Models;

use App\Models\Traits\OptimizedQueries;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use HasFactory, OptimizedQueries, SoftDeletes;

    protected $defaultRelations = ['student', 'exam', 'exam_session'];

    // ==========================================
    // Query Scopes
    // ==========================================

    /**
     * Scope: Only completed exams (end_time not null)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
    }

    /**
     * Scope: Only in-progress exams
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotNull('start_time')->whereNull('end_time');
    }

    /**
     * Scope: Not started exams
     */
    public function scopeNotStarted($query)
    {
        return $query->whereNull('start_time');
    }

    /**
     * Scope: Passed exams
     */
    public function scopePassed($query)
    {
        return $query->where('status', 'passed');
    }

    /**
     * Scope: Failed exams
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope: Flagged for suspicious activity
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope: Has violations
     */
    public function scopeHasViolations($query)
    {
        return $query->where('violation_count', '>', 0);
    }

    /**
     * Scope: For a specific exam
     */
    public function scopeForExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope: For a specific student
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: For a specific session
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('exam_session_id', $sessionId);
    }

    /**
     * Scope: Currently paused exams
     */
    public function scopePaused($query)
    {
        return $query->where('is_paused', true);
    }

    /**
     * Scope: Completed today
     */
    public function scopeCompletedToday($query)
    {
        return $query->completed()->whereDate('end_time', today());
    }

    /**
     * Scope: With minimum grade
     */
    public function scopeWithMinGrade($query, float $minGrade)
    {
        return $query->where('grade', '>=', $minGrade);
    }

    // ==========================================
    // Fillable & Casts
    // ==========================================

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'exam_id',
        'exam_session_id',
        'student_id',
        'duration',
        'time_extension',
        'extension_reason',
        'start_time',
        'end_time',
        'total_correct',
        'grade',
        'points_possible',
        'points_earned',
        'attempt_status',
        'attempt_count',
        'attempt_number',
        'status',
        // Pause feature
        'is_paused',
        'paused_at',
        'pause_reason',
        'total_paused_ms',
        // Anti-cheat fields
        'violation_count',
        'tab_switch_count',
        'fullscreen_exit_count',
        'copy_paste_count',
        'right_click_count',
        'blur_count',
        'is_flagged',
        'flag_reason',
        'anticheat_metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'paused_at' => 'datetime',
        'is_paused' => 'boolean',
        'is_flagged' => 'boolean',
        'anticheat_metadata' => 'array',
        'attempt_count' => 'integer',
        'points_possible' => 'float',
        'points_earned' => 'float',
    ];

    /**
     * exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * exam_session
     */
    public function exam_session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * violations
     */
    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }

    /**
     * Increment a specific violation counter
     */
    public function incrementViolation(string $type): void
    {
        $columnMap = [
            'tab_switch' => 'tab_switch_count',
            'fullscreen_exit' => 'fullscreen_exit_count',
            'copy_paste' => 'copy_paste_count',
            'right_click' => 'right_click_count',
            'blur' => 'blur_count',
            // Extended blur types map to blur_count
            'extended_blur' => 'blur_count',
            'prolonged_blur' => 'blur_count',
            'excessive_blur' => 'blur_count',
        ];

        if (isset($columnMap[$type])) {
            $this->increment($columnMap[$type]);
        }

        $this->increment('violation_count');
    }

    /**
     * Check if violation limit exceeded
     */
    public function hasExceededViolationLimit(int $maxViolations): bool
    {
        return $this->violation_count >= $maxViolations;
    }

    /**
     * Flag the grade for suspicious activity
     */
    public function flagAsSuspicious(string $reason): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);
    }

    /**
     * Get total violations summary
     */
    public function getViolationsSummary(): array
    {
        return [
            'total' => $this->violation_count,
            'tab_switch' => $this->tab_switch_count,
            'fullscreen_exit' => $this->fullscreen_exit_count,
            'copy_paste' => $this->copy_paste_count,
            'right_click' => $this->right_click_count,
            'blur' => $this->blur_count,
            'is_flagged' => $this->is_flagged,
            'flag_reason' => $this->flag_reason,
        ];
    }
}
