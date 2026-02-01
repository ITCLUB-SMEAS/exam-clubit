<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    // ==========================================
    // Query Scopes
    // ==========================================

    /**
     * Scope: Only exams with anti-cheat enabled
     */
    public function scopeWithAntiCheat($query)
    {
        return $query->where('anticheat_enabled', true);
    }

    /**
     * Scope: Only exams for a specific classroom
     */
    public function scopeForClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope: Only exams for a specific lesson
     */
    public function scopeForLesson($query, int $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    /**
     * Scope: Search by title
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where('title', 'like', "%{$search}%");
    }

    /**
     * Scope: Exams with adaptive mode enabled
     */
    public function scopeAdaptive($query)
    {
        return $query->where('adaptive_mode', true);
    }

    /**
     * Scope: Exams with questions count
     */
    public function scopeWithQuestionCount($query)
    {
        return $query->withCount('questions');
    }

    /**
     * Scope: Exams that have at least one question
     */
    public function scopeHasQuestions($query)
    {
        return $query->has('questions');
    }

    /**
     * Scope: Recently created exams
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
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
        'title',
        'lesson_id',
        'classroom_id',
        'duration',
        'description',
        'random_question',
        'random_answer',
        'show_answer',
        'passing_grade',
        'max_attempts',
        'question_limit',
        'time_per_question',
        // Scoring options
        'enable_partial_credit',
        'enable_negative_marking',
        'negative_marking_percentage',
        // Anti-cheat settings
        'anticheat_enabled',
        'face_detection_enabled',
        'audio_detection_enabled',
        'fullscreen_required',
        'block_tab_switch',
        'block_copy_paste',
        'block_right_click',
        'block_multiple_monitors',
        'block_virtual_machine',
        'detect_devtools',
        'max_violations',
        'auto_submit_on_max_violations',
        'warning_threshold',
        'adaptive_mode',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'passing_grade' => 'float',
        'max_attempts' => 'integer',
        'question_limit' => 'integer',
        'time_per_question' => 'integer',
        'enable_partial_credit' => 'boolean',
        'enable_negative_marking' => 'boolean',
        'negative_marking_percentage' => 'float',
        'anticheat_enabled' => 'boolean',
        'face_detection_enabled' => 'boolean',
        'fullscreen_required' => 'boolean',
        'block_tab_switch' => 'boolean',
        'block_copy_paste' => 'boolean',
        'block_right_click' => 'boolean',
        'block_multiple_monitors' => 'boolean',
        'block_virtual_machine' => 'boolean',
        'detect_devtools' => 'boolean',
        'auto_submit_on_max_violations' => 'boolean',
        'max_violations' => 'integer',
        'warning_threshold' => 'integer',
    ];

    /**
     * lesson
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * classroom
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * questions
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('id', 'DESC');
    }

    /**
     * grades
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * violations
     */
    public function violations(): HasMany
    {
        return $this->hasMany(ExamViolation::class);
    }

    /**
     * Get the anti-cheat settings as an array
     */
    public function getAntiCheatSettings(): array
    {
        return [
            'enabled' => $this->anticheat_enabled ?? true,
            'fullscreen_required' => $this->fullscreen_required ?? true,
            'block_tab_switch' => $this->block_tab_switch ?? true,
            'block_copy_paste' => $this->block_copy_paste ?? true,
            'block_right_click' => $this->block_right_click ?? true,
            'block_multiple_monitors' => true, // Always enabled
            'block_virtual_machine' => true,   // Always enabled
            'detect_devtools' => $this->detect_devtools ?? true,
            'max_violations' => $this->max_violations ?? 3,
            'auto_submit_on_max_violations' => $this->auto_submit_on_max_violations ?? false,
            'warning_threshold' => $this->warning_threshold ?? 2,
        ];
    }

    /**
     * Check if anti-cheat is enabled
     */
    public function isAntiCheatEnabled(): bool
    {
        return $this->anticheat_enabled ?? true;
    }

    /**
     * Get violation count for a specific student in this exam
     */
    public function getViolationCountForStudent(
        int $studentId,
        int $examSessionId,
    ): int {
        return $this->violations()
            ->where('student_id', $studentId)
            ->where('exam_session_id', $examSessionId)
            ->count();
    }
}
