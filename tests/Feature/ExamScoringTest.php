<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExamScoringTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Exam $exam;
    protected ExamSession $session;
    protected ExamGroup $examGroup;
    protected Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $classroom = Classroom::create(["title" => "XII IPA"]);
        $lesson = Lesson::create(["title" => "Matematika"]);

        $this->student = Student::create([
            "classroom_id" => $classroom->id,
            "nisn" => "9999999999",
            "name" => "Siswa Uji",
            "password" => Hash::make("password"),
            "gender" => "L",
        ]);

        $this->exam = Exam::create([
            "title" => "Ulangan Harian",
            "lesson_id" => $lesson->id,
            "classroom_id" => $classroom->id,
            "duration" => 60,
            "description" => "Ujian campuran",
            "random_question" => "N",
            "random_answer" => "N",
            "show_answer" => "N",
        ]);

        $this->session = ExamSession::create([
            "exam_id" => $this->exam->id,
            "title" => "Sesi 1",
            "start_time" => Carbon::now()->subMinutes(5),
            "end_time" => Carbon::now()->addMinutes(60),
        ]);

        $this->examGroup = ExamGroup::create([
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "student_id" => $this->student->id,
        ]);

        $this->grade = Grade::create([
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "student_id" => $this->student->id,
            "duration" => $this->exam->duration * 60000,
            "total_correct" => 0,
            "grade" => 0,
        ]);

        // Authenticate student with session id for middleware checks
        $this->actingAs($this->student, "student");
        $this->student->updateSessionInfo(session()->getId(), "127.0.0.1");
    }

    public function test_scoring_supports_multiple_types_and_points(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\AuthStudent::class);

        // Questions with different types and points
        $qSingle = Question::create([
            "exam_id" => $this->exam->id,
            "question" => "Apa ibu kota?",
            "question_type" => Question::TYPE_MULTIPLE_CHOICE_SINGLE,
            "points" => 2,
            "option_1" => "Jakarta",
            "option_2" => "Bandung",
            "answer" => 1,
        ]);

        $qMulti = Question::create([
            "exam_id" => $this->exam->id,
            "question" => "Pilih bilangan prima",
            "question_type" => Question::TYPE_MULTIPLE_CHOICE_MULTIPLE,
            "points" => 3,
            "option_1" => "2",
            "option_2" => "3",
            "option_3" => "4",
            "answer" => 0,
            "correct_answers" => [1, 2],
        ]);

        $qShort = Question::create([
            "exam_id" => $this->exam->id,
            "question" => "Jawaban singkat",
            "question_type" => Question::TYPE_SHORT_ANSWER,
            "points" => 5,
            "answer" => 0,
            "correct_answers" => ["jakarta"],
        ]);

        $qEssay = Question::create([
            "exam_id" => $this->exam->id,
            "question" => "Tuliskan pendapatmu",
            "question_type" => Question::TYPE_ESSAY,
            "points" => 4,
            "answer" => 0,
        ]);

        // Mulai ujian untuk membentuk jawaban default
        $this->actAsStudent();
        $responseStart = $this->get(
            "/student/exam-start/{$this->examGroup->id}",
        );
        $responseStart->assertRedirect();

        // Jawaban benar single choice
        $this->postAsStudent("/student/exam-answer", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "question_id" => $qSingle->id,
            "answer" => 1,
            "duration" => $this->exam->duration * 60000,
        ]);

        // Jawaban benar multi choice (multiple)
        $this->postAsStudent("/student/exam-answer", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "question_id" => $qMulti->id,
            "answer_options" => [1, 2],
            "duration" => $this->exam->duration * 60000,
        ]);

        // Jawaban benar short answer (case-insensitive)
        $this->postAsStudent("/student/exam-answer", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "question_id" => $qShort->id,
            "answer_text" => "Jakarta",
            "duration" => $this->exam->duration * 60000,
        ]);

        // Jawaban essay (dinilai manual)
        $this->postAsStudent("/student/exam-answer", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
            "question_id" => $qEssay->id,
            "answer_text" => "Pendapat panjang...",
            "duration" => $this->exam->duration * 60000,
        ]);

        // Akhiri ujian
        $responseEnd = $this->postAsStudent("/student/exam-end", [
            "exam_group_id" => $this->examGroup->id,
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->session->id,
        ]);
        $responseEnd->assertRedirect(
            route("student.exams.resultExam", $this->examGroup->id),
        );

        $this->grade->refresh();

        $this->assertNotNull(
            $this->grade->end_time,
            "Exam should be finalized",
        );

        // total points: 2 + 3 + 5 + 4 = 14
        // earned: 2 + 3 + 5 + 0 (essay) = 10
        $this->assertEquals(14.0, $this->grade->points_possible);
        $this->assertEquals(10.0, $this->grade->points_earned);
        $this->assertEquals(3, $this->grade->total_correct); // essay not auto-corrected
        $this->assertEquals(round((10 / 14) * 100, 2), $this->grade->grade);
        $this->assertEquals("completed", $this->grade->attempt_status);

        $essayAnswer = Answer::where("question_id", $qEssay->id)
            ->where("student_id", $this->student->id)
            ->first();
        $this->assertTrue($essayAnswer->needs_manual_review);
        $this->assertEquals(0.0, $essayAnswer->points_awarded);
    }

    private function postAsStudent(string $uri, array $data)
    {
        $this->actAsStudent();
        return $this->post($uri, $data);
    }

    private function actAsStudent(): void
    {
        $this->actingAs($this->student, "student");
        $this->student->updateSessionInfo(session()->getId(), "127.0.0.1");
    }
}
