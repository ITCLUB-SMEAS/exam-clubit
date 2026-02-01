<?php

namespace App\Models;

use App\Models\Traits\HasEncryptedAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Student extends Authenticatable
{
    use HasEncryptedAttributes, HasFactory, SoftDeletes;

    // ==========================================
    // Query Scopes
    // ==========================================

    /**
     * Scope: Only blocked students
     */
    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Scope: Only active (non-blocked) students
     */
    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    /**
     * Scope: Students in a specific classroom
     */
    public function scopeInClassroom($query, int $classroomId)
    {
        return $query->where('classroom_id', $classroomId);
    }

    /**
     * Scope: Students in a specific room
     */
    public function scopeInRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    /**
     * Scope: Search by name or NISN
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('nisn', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: By gender
     */
    public function scopeGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    /**
     * Scope: Male students
     */
    public function scopeMale($query)
    {
        return $query->where('gender', 'L');
    }

    /**
     * Scope: Female students
     */
    public function scopeFemale($query)
    {
        return $query->where('gender', 'P');
    }

    /**
     * Scope: Has logged in recently
     */
    public function scopeActiveRecently($query, int $days = 7)
    {
        return $query->where('last_login_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Has an active session
     */
    public function scopeWithActiveSession($query)
    {
        return $query->whereNotNull('session_id');
    }

    /**
     * Scope: Without active session
     */
    public function scopeWithoutActiveSession($query)
    {
        return $query->whereNull('session_id');
    }

    /**
     * Scope: Has a photo
     */
    public function scopeWithPhoto($query)
    {
        return $query->whereNotNull('photo');
    }

    /**
     * Scope: Without photo
     */
    public function scopeWithoutPhoto($query)
    {
        return $query->whereNull('photo');
    }

    // ==========================================
    // Encrypted Attributes
    // ==========================================

    /**
     * Attributes that should be encrypted at rest
     *
     * @var array
     */
    protected $encrypted = ['nisn'];

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        'classroom_id',
        'room_id',
        'nisn',
        'name',
        'password',
        'gender',
        'photo',
        'is_blocked',
        'blocked_at',
        'blocked_reason',
        'session_id',
        'last_login_at',
        'last_login_ip',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ['password', 'session_id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'blocked_at' => 'datetime',
            'is_blocked' => 'boolean',
        ];
    }

    /**
     * Block the student account
     */
    public function block(string $reason): bool
    {
        return $this->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => $reason,
            'session_id' => null,
        ]);
    }

    /**
     * Unblock the student account
     */
    public function unblock(): bool
    {
        return $this->update([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);
    }

    /**
     * classroom
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Update session information when student logs in
     */
    public function updateSessionInfo(
        string $sessionId,
        ?string $ipAddress = null,
    ): bool {
        return $this->update([
            'session_id' => $sessionId,
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ]);
    }

    /**
     * Clear session information when student logs out
     */
    public function clearSessionInfo(): bool
    {
        return $this->update([
            'session_id' => null,
        ]);
    }

    /**
     * Check if student has an active session
     */
    public function hasActiveSession(): bool
    {
        return ! empty($this->session_id);
    }

    /**
     * Check if the given session ID matches the stored session
     */
    public function isCurrentSession(string $sessionId): bool
    {
        return $this->session_id === $sessionId;
    }
}
