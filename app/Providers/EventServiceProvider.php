<?php

namespace App\Providers;

use App\Events\ExamCompleted;
use App\Events\ExamStarted;
use App\Events\HighRiskStudentsDetected;
use App\Events\StudentBlocked;
use App\Events\ViolationRecorded;
use App\Listeners\HandleViolation;
use App\Listeners\LogExamCompletion;
use App\Listeners\LogExamStart;
use App\Listeners\NotifyStudentBlocked;
use App\Listeners\NotifyTeacherHighRisk;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ExamCompleted::class => [
            LogExamCompletion::class,
        ],
        ExamStarted::class => [
            LogExamStart::class,
        ],
        ViolationRecorded::class => [
            HandleViolation::class,
        ],
        StudentBlocked::class => [
            NotifyStudentBlocked::class,
        ],
        HighRiskStudentsDetected::class => [
            NotifyTeacherHighRisk::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
