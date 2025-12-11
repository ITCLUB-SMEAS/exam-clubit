<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamViolation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'exam_id',
        'exam_session_id',
        'grade_id',
        'violation_type',
        'description',
        'metadata',
        'snapshot_path',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Violation type constants
     */
    public const TYPE_TAB_SWITCH = 'tab_switch';
    public const TYPE_FULLSCREEN_EXIT = 'fullscreen_exit';
    public const TYPE_COPY_PASTE = 'copy_paste';
    public const TYPE_RIGHT_CLICK = 'right_click';
    public const TYPE_DEVTOOLS = 'devtools';
    public const TYPE_BLUR = 'blur';
    public const TYPE_SCREENSHOT = 'screenshot';
    public const TYPE_KEYBOARD_SHORTCUT = 'keyboard_shortcut';
    public const TYPE_MULTIPLE_MONITORS = 'multiple_monitors';
    public const TYPE_REMOTE_DESKTOP = 'remote_desktop';
    public const TYPE_VIRTUAL_MACHINE = 'virtual_machine';
    public const TYPE_NO_FACE = 'no_face';
    public const TYPE_MULTIPLE_FACES = 'multiple_faces';
    public const TYPE_MULTIPLE_TABS = 'multiple_tabs';
    public const TYPE_POPUP_BLOCKED = 'popup_blocked';
    public const TYPE_EXTERNAL_LINK = 'external_link';
    public const TYPE_TIME_MANIPULATION = 'time_manipulation';
    public const TYPE_EXTENDED_BLUR = 'extended_blur';
    public const TYPE_PROLONGED_BLUR = 'prolonged_blur';
    public const TYPE_EXCESSIVE_BLUR = 'excessive_blur';
    public const TYPE_SUSPICIOUS_AUDIO = 'suspicious_audio';
    public const TYPE_FAST_ANSWER = 'fast_answer';
    public const TYPE_LIVENESS_FAILED = 'liveness_failed';

    /**
     * Get all violation types
     *
     * @return array<string, string>
     */
    public static function getViolationTypes(): array
    {
        return [
            self::TYPE_TAB_SWITCH => 'Pindah Tab/Window',
            self::TYPE_FULLSCREEN_EXIT => 'Keluar Fullscreen',
            self::TYPE_COPY_PASTE => 'Copy/Paste',
            self::TYPE_RIGHT_CLICK => 'Klik Kanan',
            self::TYPE_DEVTOOLS => 'Buka DevTools',
            self::TYPE_BLUR => 'Window Blur',
            self::TYPE_SCREENSHOT => 'Screenshot',
            self::TYPE_KEYBOARD_SHORTCUT => 'Shortcut Keyboard',
            self::TYPE_MULTIPLE_MONITORS => 'Multiple Monitor',
            self::TYPE_REMOTE_DESKTOP => 'Remote Desktop',
            self::TYPE_VIRTUAL_MACHINE => 'Virtual Machine',
            self::TYPE_NO_FACE => 'Wajah Tidak Terdeteksi',
            self::TYPE_MULTIPLE_FACES => 'Multiple Wajah',
            self::TYPE_MULTIPLE_TABS => 'Multiple Tab',
            self::TYPE_POPUP_BLOCKED => 'Popup Diblokir',
            self::TYPE_EXTERNAL_LINK => 'Link Eksternal',
            self::TYPE_TIME_MANIPULATION => 'Manipulasi Waktu',
            self::TYPE_EXTENDED_BLUR => 'Tidak Fokus Lama',
            self::TYPE_PROLONGED_BLUR => 'Tidak Fokus Berkepanjangan',
            self::TYPE_EXCESSIVE_BLUR => 'Total Tidak Fokus Berlebihan',
            self::TYPE_SUSPICIOUS_AUDIO => 'Suara Mencurigakan',
            self::TYPE_FAST_ANSWER => 'Jawaban Terlalu Cepat',
            self::TYPE_LIVENESS_FAILED => 'Gagal Verifikasi Liveness',
        ];
    }

    /**
     * Get the student that owns the violation.
     *
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the exam that owns the violation.
     *
     * @return BelongsTo
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the exam session that owns the violation.
     *
     * @return BelongsTo
     */
    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    /**
     * Get the grade that owns the violation.
     *
     * @return BelongsTo
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Scope a query to only include violations for a specific student.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $studentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope a query to only include violations for a specific exam.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $examId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope a query to only include violations for a specific exam session.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $examSessionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByExamSession($query, int $examSessionId)
    {
        return $query->where('exam_session_id', $examSessionId);
    }

    /**
     * Scope a query to only include violations of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }

    /**
     * Scope a query to only include violations for a specific grade.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $gradeId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByGrade($query, int $gradeId)
    {
        return $query->where('grade_id', $gradeId);
    }

    /**
     * Get the violation type label.
     *
     * @return string
     */
    public function getViolationTypeLabelAttribute(): string
    {
        $types = self::getViolationTypes();
        return $types[$this->violation_type] ?? $this->violation_type;
    }
}
