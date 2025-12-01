<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorVmDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Student $student;
    protected Classroom $classroom;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->classroom = Classroom::create(['title' => 'Kelas 12 IPA']);
        $this->lesson = Lesson::create(['title' => 'Matematika']);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'nisn' => '1234567890',
            'name' => 'Test Student',
            'password' => bcrypt('password'),
            'gender' => 'L',
        ]);
    }

    /** @test */
    public function exam_automatically_has_monitor_and_vm_blocking_enabled()
    {
        $response = $this->actingAs($this->admin)->post('/admin/exams', [
            'title' => 'Ujian Baru',
            'lesson_id' => $this->lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test exam',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'Y',
        ]);

        $response->assertRedirect();

        $exam = Exam::where('title', 'Ujian Baru')->first();
        $this->assertNotNull($exam);
        $this->assertTrue((bool) $exam->block_multiple_monitors);
        $this->assertTrue((bool) $exam->block_virtual_machine);
    }

    /** @test */
    public function anticheat_config_includes_monitor_and_vm_settings()
    {
        $exam = Exam::create([
            'title' => 'Ujian Test',
            'lesson_id' => $this->lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'Y',
        ]);

        $config = $exam->getAntiCheatSettings();

        $this->assertArrayHasKey('block_multiple_monitors', $config);
        $this->assertArrayHasKey('block_virtual_machine', $config);
        $this->assertTrue($config['block_multiple_monitors']);
        $this->assertTrue($config['block_virtual_machine']);
    }

    /** @test */
    public function multiple_monitor_violation_is_recorded()
    {
        $exam = Exam::create([
            'title' => 'Ujian Test',
            'lesson_id' => $this->lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'Y',
        ]);

        $examSession = ExamSession::create([
            'title' => 'Sesi 1',
            'exam_id' => $exam->id,
            'start_time' => now(),
            'end_time' => now()->addHours(2),
        ]);

        ExamGroup::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
        ]);

        $grade = Grade::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
            'duration' => 60,
            'start_time' => now(),
            'end_time' => now()->addMinutes(60),
            'total_correct' => 0,
            'grade' => 0,
        ]);

        ExamViolation::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
            'grade_id' => $grade->id,
            'violation_type' => 'multiple_monitors',
            'description' => 'Terdeteksi 2 monitor',
            'metadata' => json_encode(['screenCount' => 2]),
        ]);

        $this->assertDatabaseHas('exam_violations', [
            'violation_type' => 'multiple_monitors',
            'student_id' => $this->student->id,
        ]);
    }

    /** @test */
    public function virtual_machine_violation_is_recorded()
    {
        $exam = Exam::create([
            'title' => 'Ujian Test',
            'lesson_id' => $this->lesson->id,
            'classroom_id' => $this->classroom->id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'Y',
        ]);

        $examSession = ExamSession::create([
            'title' => 'Sesi 1',
            'exam_id' => $exam->id,
            'start_time' => now(),
            'end_time' => now()->addHours(2),
        ]);

        ExamGroup::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
        ]);

        $grade = Grade::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
            'duration' => 60,
            'start_time' => now(),
            'end_time' => now()->addMinutes(60),
            'total_correct' => 0,
            'grade' => 0,
        ]);

        ExamViolation::create([
            'exam_id' => $exam->id,
            'exam_session_id' => $examSession->id,
            'student_id' => $this->student->id,
            'grade_id' => $grade->id,
            'violation_type' => 'virtual_machine',
            'description' => 'Terdeteksi penggunaan Virtual Machine',
            'metadata' => json_encode(['indicators' => [['method' => 'webgl', 'value' => 'vmware']]]),
        ]);

        $this->assertDatabaseHas('exam_violations', [
            'violation_type' => 'virtual_machine',
            'student_id' => $this->student->id,
        ]);
    }
}
