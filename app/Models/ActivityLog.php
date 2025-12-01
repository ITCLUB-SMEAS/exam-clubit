<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * ActivityLog Model
 *
 * @property int $id
 * @property string|null $user_type
 * @property int|null $user_id
 * @property string|null $user_name
 * @property string $action
 * @property string $module
 * @property string $description
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property string|null $method
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $time_ago
 * @property-read Model|null $user
 * @property-read Model|null $subject
 *
 * @method static Builder|ActivityLog byUser(string $userType, int $userId)
 * @method static Builder|ActivityLog byAction(string $action)
 * @method static Builder|ActivityLog byModule(string $module)
 * @method static Builder|ActivityLog dateBetween($startDate, $endDate)
 * @method static Builder|ActivityLog today()
 * @method static Builder|ActivityLog newModelQuery()
 * @method static Builder|ActivityLog newQuery()
 * @method static Builder|ActivityLog query()
 */
class ActivityLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        "user_type",
        "user_id",
        "user_name",
        "action",
        "module",
        "description",
        "subject_type",
        "subject_id",
        "old_values",
        "new_values",
        "ip_address",
        "user_agent",
        "url",
        "method",
        "metadata",
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "old_values" => "array",
            "new_values" => "array",
            "metadata" => "array",
        ];
    }

    /**
     * Get the user that performed the action.
     *
     * @return MorphTo<Model, ActivityLog>
     */
    public function user(): MorphTo
    {
        return $this->morphTo("user", "user_type", "user_id");
    }

    /**
     * Get the subject of the activity.
     *
     * @return MorphTo<Model, ActivityLog>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo("subject", "subject_type", "subject_id");
    }

    /**
     * Scope to filter by user.
     *
     * @param Builder<ActivityLog> $query
     * @param string $userType
     * @param int $userId
     * @return Builder<ActivityLog>
     */
    public function scopeByUser(
        Builder $query,
        string $userType,
        int $userId,
    ): Builder {
        return $query->where("user_type", $userType)->where("user_id", $userId);
    }

    /**
     * Scope to filter by action.
     *
     * @param Builder<ActivityLog> $query
     * @param string $action
     * @return Builder<ActivityLog>
     */
    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where("action", $action);
    }

    /**
     * Scope to filter by module.
     *
     * @param Builder<ActivityLog> $query
     * @param string $module
     * @return Builder<ActivityLog>
     */
    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where("module", $module);
    }

    /**
     * Scope to filter by date range.
     *
     * @param Builder<ActivityLog> $query
     * @param \DateTimeInterface|string $startDate
     * @param \DateTimeInterface|string $endDate
     * @return Builder<ActivityLog>
     */
    public function scopeDateBetween(
        Builder $query,
        $startDate,
        $endDate,
    ): Builder {
        return $query->whereBetween("created_at", [$startDate, $endDate]);
    }

    /**
     * Scope for today's logs.
     *
     * @param Builder<ActivityLog> $query
     * @return Builder<ActivityLog>
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate("created_at", today());
    }

    /**
     * Get human-readable time difference.
     *
     * @return string
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
