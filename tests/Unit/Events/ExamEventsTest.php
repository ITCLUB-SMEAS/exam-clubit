<?php

namespace Tests\Unit\Events;

use App\Events\ExamCompleted;
use App\Events\ExamStarted;
use App\Events\StudentBlocked;
use App\Events\ViolationRecorded;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_exam_started_event_contains_grade()
    {
        $grade = Grade::factory()->create();

        $event = new ExamStarted($grade);

        $this->assertInstanceOf(Grade::class, $event->grade);
        $this->assertEquals($grade->id, $event->grade->id);
    }

    public function test_exam_completed_event_contains_grade_and_auto_submitted()
    {
        $grade = Grade::factory()->create([
            'grade' => 85.5,
        ]);

        $event = new ExamCompleted($grade, true);

        $this->assertInstanceOf(Grade::class, $event->grade);
        $this->assertTrue($event->autoSubmitted);

        $eventNormal = new ExamCompleted($grade, false);
        $this->assertFalse($eventNormal->autoSubmitted);
    }

    public function test_violation_recorded_event_contains_violation_and_count()
    {
        $violation = ExamViolation::factory()->create();

        $event = new ViolationRecorded($violation, 3);

        $this->assertInstanceOf(ExamViolation::class, $event->violation);
        $this->assertEquals(3, $event->totalViolations);
    }

    public function test_student_blocked_event_contains_student_and_reason()
    {
        $student = Student::factory()->create();
        $reason = 'Too many violations';

        $event = new StudentBlocked($student, $reason);

        $this->assertInstanceOf(Student::class, $event->student);
        $this->assertEquals($reason, $event->reason);
    }

    public function test_events_are_dispatchable()
    {
        // Test that events have Dispatchable trait
        $this->assertTrue(method_exists(ExamStarted::class, 'dispatch'));
        $this->assertTrue(method_exists(ExamCompleted::class, 'dispatch'));
        $this->assertTrue(method_exists(ViolationRecorded::class, 'dispatch'));
        $this->assertTrue(method_exists(StudentBlocked::class, 'dispatch'));
    }
}
