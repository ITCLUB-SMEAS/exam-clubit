<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity.
     *
     * @param string $action The action performed (e.g., 'create', 'update', 'delete')
     * @param string $module The module/area where action occurred
     * @param string $description Human-readable description of the activity
     * @param Model|null $subject The model that was acted upon
     * @param array<string, mixed>|null $oldValues Previous values before change
     * @param array<string, mixed>|null $newValues New values after change
     * @param array<string, mixed>|null $metadata Additional metadata
     * @return ActivityLog
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
    ): ActivityLog {
        // Determine user type and get user
        $userType = null;
        $userId = null;
        $userName = null;

        if (Auth::guard("web")->check()) {
            /** @var User $user */
            $user = Auth::guard("web")->user();
            $userType = "admin";
            $userId = $user->id;
            $userName = $user->name;
        } elseif (Auth::guard("student")->check()) {
            /** @var Student $user */
            $user = Auth::guard("student")->user();
            $userType = "student";
            $userId = $user->id;
            $userName = $user->name;
        }

        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => $userType,
            "user_id" => $userId,
            "user_name" => $userName,
            "action" => $action,
            "module" => $module,
            "description" => $description,
            "subject_type" => $subject ? get_class($subject) : null,
            "subject_id" => $subject?->id,
            "old_values" => $oldValues,
            "new_values" => $newValues,
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "url" => Request::fullUrl(),
            "method" => Request::method(),
            "metadata" => $metadata,
        ]);

        return $log;
    }

    /**
     * Log a login event.
     *
     * @param string $guard The authentication guard ('web' or 'student')
     * @param User|Student|object $user The user who attempted to login
     * @param string $status Login status ('success' or 'failed')
     * @return ActivityLog
     */
    public static function logLogin(
        string $guard,
        $user,
        string $status = "success",
    ): ActivityLog {
        $userType = $guard === "web" ? "admin" : "student";
        $userName = $user->name ?? "unknown";
        $description =
            $status === "success"
                ? "{$userName} berhasil login"
                : "Percobaan login gagal untuk {$userName}";

        // Record to login_histories table
        \App\Models\LoginHistory::record($userType, $user->id ?? 0, $status, request());

        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => $userType,
            "user_id" => $user->id ?? null,
            "user_name" => $userName,
            "action" => $status === "success" ? "login" : "login_failed",
            "module" => "auth",
            "description" => $description,
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "url" => Request::fullUrl(),
            "method" => Request::method(),
        ]);

        return $log;
    }

    /**
     * Log a logout event.
     *
     * @param string $guard The authentication guard ('web' or 'student')
     * @param User|Student|object $user The user who logged out
     * @return ActivityLog
     */
    public static function logLogout(string $guard, $user): ActivityLog
    {
        $userType = $guard === "web" ? "admin" : "student";

        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => $userType,
            "user_id" => $user->id,
            "user_name" => $user->name,
            "action" => "logout",
            "module" => "auth",
            "description" => "{$user->name} telah logout",
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "url" => Request::fullUrl(),
            "method" => Request::method(),
        ]);

        return $log;
    }

    /**
     * Log a create event.
     *
     * @param Model $model The model that was created
     * @param string $module The module name
     * @param string|null $description Custom description (optional)
     * @return ActivityLog
     */
    public static function logCreate(
        Model $model,
        string $module,
        ?string $description = null,
    ): ActivityLog {
        $modelName = class_basename($model);
        $description = $description ?? "{$modelName} baru telah dibuat";

        return self::log(
            "create",
            $module,
            $description,
            $model,
            null,
            self::filterSensitiveData($model->toArray()),
        );
    }

    /**
     * Log an update event.
     *
     * @param Model $model The model that was updated
     * @param string $module The module name
     * @param array<string, mixed> $oldValues The original values before update
     * @param string|null $description Custom description (optional)
     * @return ActivityLog
     */
    public static function logUpdate(
        Model $model,
        string $module,
        array $oldValues,
        ?string $description = null,
    ): ActivityLog {
        $modelName = class_basename($model);
        $description = $description ?? "{$modelName} telah diupdate";

        // Filter only changed values
        $newValues = [];
        foreach ($oldValues as $key => $oldValue) {
            if (isset($model->$key) && $model->$key !== $oldValue) {
                $newValues[$key] = $model->$key;
            }
        }

        return self::log(
            "update",
            $module,
            $description,
            $model,
            self::filterSensitiveData($oldValues),
            self::filterSensitiveData($newValues),
        );
    }

    /**
     * Log a delete event.
     *
     * @param Model $model The model that was deleted
     * @param string $module The module name
     * @param string|null $description Custom description (optional)
     * @return ActivityLog
     */
    public static function logDelete(
        Model $model,
        string $module,
        ?string $description = null,
    ): ActivityLog {
        $modelName = class_basename($model);
        $description = $description ?? "{$modelName} telah dihapus";

        return self::log(
            "delete",
            $module,
            $description,
            $model,
            self::filterSensitiveData($model->toArray()),
            null,
        );
    }

    /**
     * Log exam started.
     *
     * @param Student|object $student The student starting the exam
     * @param Exam|object $exam The exam being started
     * @param ExamSession|object $examSession The exam session
     * @return ActivityLog
     */
    public static function logExamStart(
        $student,
        $exam,
        $examSession,
    ): ActivityLog {
        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => "student",
            "user_id" => $student->id,
            "user_name" => $student->name,
            "action" => "exam_start",
            "module" => "exam",
            "description" => "{$student->name} memulai ujian: {$exam->title}",
            "subject_type" => get_class($exam),
            "subject_id" => $exam->id,
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "url" => Request::fullUrl(),
            "method" => Request::method(),
            "metadata" => [
                "exam_session_id" => $examSession->id,
                "exam_title" => $exam->title,
            ],
        ]);

        return $log;
    }

    /**
     * Log exam ended.
     *
     * @param Student|object $student The student completing the exam
     * @param Exam|object $exam The exam that was completed
     * @param Grade|object $grade The grade/result of the exam
     * @return ActivityLog
     */
    public static function logExamEnd($student, $exam, $grade): ActivityLog
    {
        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => "student",
            "user_id" => $student->id,
            "user_name" => $student->name,
            "action" => "exam_end",
            "module" => "exam",
            "description" => "{$student->name} menyelesaikan ujian: {$exam->title} dengan nilai {$grade->grade}",
            "subject_type" => get_class($exam),
            "subject_id" => $exam->id,
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "url" => Request::fullUrl(),
            "method" => Request::method(),
            "metadata" => [
                "grade" => $grade->grade,
                "total_correct" => $grade->total_correct,
            ],
        ]);

        return $log;
    }

    /**
     * Log answer submitted.
     *
     * @param Student|object $student The student submitting the answer
     * @param Question|object $question The question being answered
     * @param int|string $answer The answer submitted
     * @return ActivityLog
     */
    public static function logAnswerSubmit(
        $student,
        $question,
        $answer,
    ): ActivityLog {
        /** @var ActivityLog $log */
        $log = ActivityLog::query()->create([
            "user_type" => "student",
            "user_id" => $student->id,
            "user_name" => $student->name,
            "action" => "answer_submit",
            "module" => "exam",
            "description" => "{$student->name} menjawab soal #{$question->id}",
            "subject_type" => get_class($question),
            "subject_id" => $question->id,
            "ip_address" => Request::ip(),
            "user_agent" => Request::userAgent(),
            "metadata" => [
                "answer" => $answer,
                "is_correct" => $question->answer == $answer ? "Y" : "N",
            ],
        ]);

        return $log;
    }

    /**
     * Filter sensitive data from arrays.
     *
     * @param array<string, mixed> $data The data array to filter
     * @return array<string, mixed> The filtered data array
     */
    protected static function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            "password",
            "password_confirmation",
            "remember_token",
            "two_factor_secret",
            "two_factor_recovery_codes",
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = "[REDACTED]";
            }
        }

        return $data;
    }
}
