<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Student;
use App\Services\ExamTimerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTimerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExamTimerService $service;
    protected Exam $exam;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExamTimerService();

        $classroom = Classroom::create(['title' => 'Test Class']);
        $lesson = Lesson::create(['title' => 'Test Lesson']);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => 'password',
            'gender' => 'L',
        ]);

        $this->exam = Exam::create([
            'title' => 'Test Exam',
            'lesson_id' => $lesson->id,
            'classroom_id' => $classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);
    }

    /** @test */
    public function it_validates_session_not_started()
    {
        $session = ExamSession::create([
            'title' => 'Future Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(3),
        ]);

        $examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
        ]);
        $examGroup->load('exam_session');

        $error = $this->service->validateSessionWindow($examGroup);
        $this->assertEquals('Ujian belum dapat dimulai. Silakan cek jadwal.', $error);
    }

    /** @test */
    public function it_validates_session_ended()
    {
        $session = ExamSession::create([
            'title' => 'Past Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->subHours(3),
            'end_time' => Carbon::now()->subHour(),
        ]);

        $examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
        ]);
        $examGroup->load('exam_session');

        $error = $this->service->validateSessionWindow($examGroup);
        $this->assertEquals('Sesi ujian telah berakhir.', $error);
    }

    /** @test */
    public function it_validates_session_active()
    {
        $session = ExamSession::create([
            'title' => 'Active Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->subHour(),
            'end_time' => Carbon::now()->addHour(),
        ]);

        $examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
        ]);
        $examGroup->load('exam_session');

        $error = $this->service->validateSessionWindow($examGroup);
        $this->assertNull($error);
    }

    /** @test */
    public function it_calculates_remaining_duration_correctly()
    {
        $session = ExamSession::create([
            'title' => 'Active Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->subMinutes(10),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
        ]);
        $examGroup->load('exam', 'exam_session');

        $grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => Carbon::now()->subMinutes(10),
            'total_correct' => 0,
            'grade' => 0,
        ]);

        // Exam is 60 minutes, started 10 minutes ago
        // Should have ~50 minutes remaining (3000000 ms)
        $remaining = $this->service->calculateRemainingDurationMs($examGroup, $grade);
        
        // Allow 5 second tolerance for test execution time
        $expectedMin = 49 * 60 * 1000; // 49 minutes
        $expectedMax = 51 * 60 * 1000; // 51 minutes
        
        $this->assertGreaterThan($expectedMin, $remaining);
        $this->assertLessThan($expectedMax, $remaining);
    }

    /** @test */
    public function it_respects_time_extension()
    {
        $session = ExamSession::create([
            'title' => 'Active Session',
            'exam_id' => $this->exam->id,
            'start_time' => Carbon::now()->subMinutes(55),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $examGroup = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
        ]);
        $examGroup->load('exam', 'exam_session');

        $grade = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $session->id,
            'student_id' => $this->student->id,
            'duration' => 3600000,
            'start_time' => Carbon::now()->subMinutes(55),
            'time_extension' => 30, // 30 minutes extension
            'total_correct' => 0,
            'grade' => 0,
        ]);

        // Exam is 60 minutes + 30 extension = 90 minutes total
        // Started 55 minutes ago, should have ~35 minutes remaining
        $remaining = $this->service->calculateRemainingDurationMs($examGroup, $grade);
        
        $expectedMin = 33 * 60 * 1000; // 33 minutes
        $expectedMax = 37 * 60 * 1000; // 37 minutes
        
        $this->assertGreaterThan($expectedMin, $remaining);
        $this->assertLessThan($expectedMax, $remaining);
    }
}
