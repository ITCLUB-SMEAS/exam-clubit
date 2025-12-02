<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Authenticatable
{
    use HasFactory;

    /**
     * fillable
     *
     * @var array
     */
    protected $fillable = [
        "classroom_id",
        "nisn",
        "name",
        "password",
        "gender",
        "is_blocked",
        "blocked_at",
        "blocked_reason",
        "session_id",
        "last_login_at",
        "last_login_ip",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = ["password", "session_id"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "password" => "hashed",
            "last_login_at" => "datetime",
            "blocked_at" => "datetime",
            "is_blocked" => "boolean",
        ];
    }

    /**
     * Block the student account
     */
    public function block(string $reason): bool
    {
        return $this->update([
            "is_blocked" => true,
            "blocked_at" => now(),
            "blocked_reason" => $reason,
            "session_id" => null,
        ]);
    }

    /**
     * Unblock the student account
     */
    public function unblock(): bool
    {
        return $this->update([
            "is_blocked" => false,
            "blocked_at" => null,
            "blocked_reason" => null,
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

    /**
     * Update session information when student logs in
     *
     * @param string $sessionId
     * @param string|null $ipAddress
     * @return bool
     */
    public function updateSessionInfo(
        string $sessionId,
        ?string $ipAddress = null,
    ): bool {
        return $this->update([
            "session_id" => $sessionId,
            "last_login_at" => now(),
            "last_login_ip" => $ipAddress,
        ]);
    }

    /**
     * Clear session information when student logs out
     *
     * @return bool
     */
    public function clearSessionInfo(): bool
    {
        return $this->update([
            "session_id" => null,
        ]);
    }

    /**
     * Check if student has an active session
     *
     * @return bool
     */
    public function hasActiveSession(): bool
    {
        return !empty($this->session_id);
    }

    /**
     * Check if the given session ID matches the stored session
     *
     * @param string $sessionId
     * @return bool
     */
    public function isCurrentSession(string $sessionId): bool
    {
        return $this->session_id === $sessionId;
    }
}
