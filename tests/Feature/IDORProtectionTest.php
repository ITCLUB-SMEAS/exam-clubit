<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IDORProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student1;
    protected Student $student2;
    protected Exam $exam;
    protected ExamSession $session;
    protected ExamGroup $examGroup1;
    protected ExamGroup $examGroup2;
    protected Grade $grade1;
    protected Grade $grade2;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = Classroom::create(['title' => 'Test Class']);
        $lesson = Lesson::create(['title' => 'Test Lesson']);

        $this->student1 = Student::create([
            'nisn' => '1234567890',
            'name' => 'Student 1',
            'password' => bcrypt('password'),
            'gender' => 'L',
            'classroom_id' => $classroom->id,
        ]);

        $this->student2 = Student::create([
            'nisn' => '0987654321',
            'name' => 'Student 2',
            'password' => bcrypt('password'),
            'gender' => 'P',
            'classroom_id' => $classroom->id,
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

        $this->session = ExamSession::create([
            'title' => 'Test Session',
            'exam_id' => $this->exam->id,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
        ]);

        $this->examGroup1 = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student1->id,
        ]);

        $this->examGroup2 = ExamGroup::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student2->id,
        ]);

        $this->grade1 = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student1->id,
            'duration' => 3600000,
        ]);

        $this->grade2 = Grade::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student2->id,
            'duration' => 3600000,
            'end_time' => now(),
            'grade' => 80,
        ]);
    }

    /** @test */
    public function student_cannot_access_other_student_exam_confirmation()
    {
        $this->actingAs($this->student1, 'student');

        // Try to access student2's exam group
        $response = $this->get(route('student.exams.confirmation', $this->examGroup2->id));

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function student_can_access_own_exam_confirmation()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->get(route('student.exams.confirmation', $this->examGroup1->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function student_cannot_access_other_student_exam_result()
    {
        $this->actingAs($this->student1, 'student');

        // Try to access student2's result
        $response = $this->get(route('student.exams.resultExam', $this->examGroup2->id));

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function student_cannot_start_other_student_exam()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->get(route('student.exams.startExam', $this->examGroup2->id));

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function student_cannot_view_other_student_exam_page()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->get(route('student.exams.show', [
            'id' => $this->examGroup2->id,
            'page' => 1
        ]));

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function student_cannot_update_other_student_duration()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->put(route('student.exams.update_duration', $this->grade2->id));

        $response->assertStatus(404);
    }

    /** @test */
    public function student_cannot_end_other_student_exam()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->post(route('student.exams.endExam'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'exam_group_id' => $this->examGroup2->id,
        ]);

        $response->assertRedirect(route('student.dashboard'));
    }

    /** @test */
    public function student_cannot_record_violation_for_other_student()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->postJson(route('student.anticheat.violation'), [
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'grade_id' => $this->grade2->id,
            'violation_type' => 'tab_switch',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function student_cannot_get_violation_status_for_other_student()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->getJson(route('student.anticheat.status', [
            'grade_id' => $this->grade2->id
        ]));

        $response->assertStatus(404);
    }

    /** @test */
    public function student_cannot_get_anticheat_config_for_unenrolled_exam()
    {
        // Create another exam that student1 is not enrolled in
        $otherExam = Exam::create([
            'title' => 'Other Exam',
            'lesson_id' => $this->exam->lesson_id,
            'classroom_id' => $this->exam->classroom_id,
            'duration' => 60,
            'description' => 'Test',
            'random_question' => 'N',
            'random_answer' => 'N',
            'show_answer' => 'N',
        ]);

        $this->actingAs($this->student1, 'student');

        $response = $this->getJson(route('student.anticheat.config', $otherExam->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_heartbeat_for_other_student()
    {
        $this->actingAs($this->student1, 'student');

        $response = $this->postJson(route('student.anticheat.heartbeat'), [
            'grade_id' => $this->grade2->id,
        ]);

        $response->assertStatus(400);
    }
}
