<?php

namespace Tests\Unit\Events;

use App\Events\ExamCompleted;
use App\Events\ExamStarted;
use App\Events\StudentBlocked;
use App\Events\ViolationRecorded;
use App\Listeners\HandleViolation;
use App\Listeners\LogExamCompletion;
use App\Listeners\LogExamStart;
use App\Listeners\NotifyStudentBlocked;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventListenerRegistrationTest extends TestCase
{
    public function test_exam_completed_has_listener()
    {
        Event::fake();
        Event::assertListening(
            ExamCompleted::class,
            LogExamCompletion::class
        );
    }

    public function test_exam_started_has_listener()
    {
        Event::fake();
        Event::assertListening(
            ExamStarted::class,
            LogExamStart::class
        );
    }

    public function test_violation_recorded_has_listener()
    {
        Event::fake();
        Event::assertListening(
            ViolationRecorded::class,
            HandleViolation::class
        );
    }

    public function test_student_blocked_has_listener()
    {
        Event::fake();
        Event::assertListening(
            StudentBlocked::class,
            NotifyStudentBlocked::class
        );
    }
}
