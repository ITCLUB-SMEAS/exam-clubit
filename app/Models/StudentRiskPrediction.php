<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRiskPrediction extends Model
{
    use HasFactory;

    // ==========================================
    // Constants
    // ==========================================

    public const RISK_LOW = 'low';

    public const RISK_MEDIUM = 'medium';

    public const RISK_HIGH = 'high';

    public const RISK_CRITICAL = 'critical';

    public const STATUS_PENDING = 'pending';

    public const STATUS_NOTIFIED = 'notified';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_INTERVENED = 'intervened';

    public const STATUS_RESOLVED = 'resolved';

    public const CURRENT_VERSION = '1.0';

    // Risk thresholds
    public const THRESHOLD_LOW = 30;

    public const THRESHOLD_MEDIUM = 50;

    public const THRESHOLD_HIGH = 70;

    public const THRESHOLD_CRITICAL = 85;

    // ==========================================
    // Fillable & Casts
    // ==========================================

    protected $fillable = [
        'student_id',
        'exam_id',
        'lesson_id',
        'risk_score',
        'risk_level',
        'risk_factors',
        'predicted_score',
        'weak_topics',
        'recommended_actions',
        'historical_average',
        'total_exams_taken',
        'total_passed',
        'total_failed',
        'total_violations',
        'teacher_notified',
        'notified_at',
        'intervention_by',
        'intervention_notes',
        'intervened_at',
        'intervention_status',
        'actual_score',
        'prediction_accurate',
        'prediction_error',
        'calculation_version',
        'expires_at',
    ];

    protected $casts = [
        'risk_factors' => 'array',
        'weak_topics' => 'array',
        'recommended_actions' => 'array',
        'teacher_notified' => 'boolean',
        'prediction_accurate' => 'boolean',
        'notified_at' => 'datetime',
        'intervened_at' => 'datetime',
        'expires_at' => 'datetime',
        'risk_score' => 'float',
        'predicted_score' => 'float',
        'actual_score' => 'float',
        'historical_average' => 'float',
        'prediction_error' => 'float',
    ];

    // ==========================================
    // Relationships
    // ==========================================

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function intervenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'intervention_by');
    }

    // ==========================================
    // Query Scopes
    // ==========================================

    /**
     * Scope: High risk students (high + critical)
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Scope: Critical risk only
     */
    public function scopeCritical($query)
    {
        return $query->where('risk_level', self::RISK_CRITICAL);
    }

    /**
     * Scope: By risk level
     */
    public function scopeRiskLevel($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    /**
     * Scope: Pending intervention
     */
    public function scopePendingIntervention($query)
    {
        return $query->whereIn('intervention_status', [
            self::STATUS_PENDING,
            self::STATUS_NOTIFIED,
        ]);
    }

    /**
     * Scope: Not yet notified
     */
    public function scopeNotNotified($query)
    {
        return $query->where('teacher_notified', false);
    }

    /**
     * Scope: For specific exam
     */
    public function scopeForExam($query, int $examId)
    {
        return $query->where('exam_id', $examId);
    }

    /**
     * Scope: For specific student
     */
    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: Active (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope: Expired predictions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope: With validation data (post-exam)
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('actual_score');
    }

    /**
     * Scope: Recent predictions (within days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Get risk level from score
     */
    public static function getRiskLevelFromScore(float $score): string
    {
        if ($score >= self::THRESHOLD_CRITICAL) {
            return self::RISK_CRITICAL;
        }
        if ($score >= self::THRESHOLD_HIGH) {
            return self::RISK_HIGH;
        }
        if ($score >= self::THRESHOLD_MEDIUM) {
            return self::RISK_MEDIUM;
        }

        return self::RISK_LOW;
    }

    /**
     * Check if high risk
     */
    public function isHighRisk(): bool
    {
        return in_array($this->risk_level, [self::RISK_HIGH, self::RISK_CRITICAL]);
    }

    /**
     * Check if critical
     */
    public function isCritical(): bool
    {
        return $this->risk_level === self::RISK_CRITICAL;
    }

    /**
     * Check if needs intervention
     */
    public function needsIntervention(): bool
    {
        return $this->isHighRisk() && in_array($this->intervention_status, [
            self::STATUS_PENDING,
            self::STATUS_NOTIFIED,
        ]);
    }

    /**
     * Mark as notified
     */
    public function markAsNotified(): bool
    {
        return $this->update([
            'teacher_notified' => true,
            'notified_at' => now(),
            'intervention_status' => self::STATUS_NOTIFIED,
        ]);
    }

    /**
     * Mark as acknowledged
     */
    public function markAsAcknowledged(): bool
    {
        return $this->update([
            'intervention_status' => self::STATUS_ACKNOWLEDGED,
        ]);
    }

    /**
     * Record intervention
     */
    public function recordIntervention(int $userId, ?string $notes = null): bool
    {
        return $this->update([
            'intervention_by' => $userId,
            'intervention_notes' => $notes,
            'intervened_at' => now(),
            'intervention_status' => self::STATUS_INTERVENED,
        ]);
    }

    /**
     * Mark as resolved
     */
    public function markAsResolved(): bool
    {
        return $this->update([
            'intervention_status' => self::STATUS_RESOLVED,
        ]);
    }

    /**
     * Validate prediction after exam
     */
    public function validatePrediction(float $actualScore): bool
    {
        $error = abs($this->predicted_score - $actualScore);
        // Consider accurate if within 15 points
        $accurate = $error <= 15;

        return $this->update([
            'actual_score' => $actualScore,
            'prediction_error' => $error,
            'prediction_accurate' => $accurate,
        ]);
    }

    /**
     * Get primary risk factors (top 3)
     */
    public function getPrimaryRiskFactors(): array
    {
        if (empty($this->risk_factors)) {
            return [];
        }

        $allFactors = [];
        foreach ($this->risk_factors as $category => $data) {
            if (isset($data['factors'])) {
                foreach ($data['factors'] as $factor) {
                    $allFactors[] = [
                        'category' => $category,
                        'factor' => $factor,
                        'score' => $data['score'] ?? 0,
                    ];
                }
            }
        }

        // Sort by score descending
        usort($allFactors, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($allFactors, 0, 3);
    }

    /**
     * Get risk badge color
     */
    public function getRiskColor(): string
    {
        return match ($this->risk_level) {
            self::RISK_CRITICAL => 'red',
            self::RISK_HIGH => 'orange',
            self::RISK_MEDIUM => 'yellow',
            default => 'green',
        };
    }

    /**
     * Get risk label in Indonesian
     */
    public function getRiskLabel(): string
    {
        return match ($this->risk_level) {
            self::RISK_CRITICAL => 'Kritis',
            self::RISK_HIGH => 'Tinggi',
            self::RISK_MEDIUM => 'Sedang',
            default => 'Rendah',
        };
    }

    /**
     * Check if prediction is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
