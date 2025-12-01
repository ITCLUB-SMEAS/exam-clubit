<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\ExamSession;
use App\Models\ExamViolation;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use App\Services\AntiCheatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiCheatTest extends TestCase
{
    use RefreshDatabase;

    protected Student $student;
    protected Exam $exam;
    protected ExamSession $examSession;
    protected ExamGroup $examGroup;
    protected Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        // Create classroom
        $classroom = Classroom::create([
            "title" => "Kelas 12 IPA 1",
        ]);

        // Create lesson
        $lesson = Lesson::create([
            "title" => "Matematika",
        ]);

        // Create student
        $this->student = Student::create([
            "classroom_id" => $classroom->id,
            "nisn" => "1234567890",
            "name" => "Test Student",
            "password" => "password123",
            "gender" => "L",
        ]);

        // Create exam with anti-cheat enabled
        $this->exam = Exam::create([
            "title" => "Ujian Matematika",
            "lesson_id" => $lesson->id,
            "classroom_id" => $classroom->id,
            "duration" => 60,
            "description" => "Ujian Matematika Semester 1",
            "random_question" => "N",
            "random_answer" => "N",
            "show_answer" => "Y",
            "anticheat_enabled" => true,
            "fullscreen_required" => true,
            "block_tab_switch" => true,
            "block_copy_paste" => true,
            "block_right_click" => true,
            "detect_devtools" => true,
            "max_violations" => 10,
            "warning_threshold" => 3,
            "auto_submit_on_max_violations" => false,
        ]);

        // Create exam session
        $this->examSession = ExamSession::create([
            "title" => "Sesi 1",
            "exam_id" => $this->exam->id,
            "start_time" => now(),
            "end_time" => now()->addHours(2),
        ]);

        // Create exam group (enrollment)
        $this->examGroup = ExamGroup::create([
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "student_id" => $this->student->id,
        ]);

        // Create grade
        $this->grade = Grade::create([
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "student_id" => $this->student->id,
            "duration" => 3600000,
            "start_time" => now(),
            "end_time" => null,
            "total_correct" => 0,
            "grade" => 0,
        ]);

        // Create a question for the exam
        Question::create([
            "exam_id" => $this->exam->id,
            "question" => "Berapa hasil dari 2 + 2?",
            "option_1" => "3",
            "option_2" => "4",
            "option_3" => "5",
            "option_4" => "6",
            "option_5" => "7",
            "answer" => 2,
        ]);
    }

    /** @test */
    public function it_can_record_a_violation()
    {
        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
            "description" => "Siswa berpindah ke tab lain",
        ]);

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "message" => "Violation recorded.",
        ]);

        $this->assertDatabaseHas("exam_violations", [
            "student_id" => $this->student->id,
            "exam_id" => $this->exam->id,
            "violation_type" => "tab_switch",
        ]);

        // Check that grade violation count was incremented
        $this->grade->refresh();
        $this->assertEquals(1, $this->grade->violation_count);
        $this->assertEquals(1, $this->grade->tab_switch_count);
    }

    /** @test */
    public function it_can_record_batch_violations()
    {
        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violations", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violations" => [
                ["type" => "tab_switch", "description" => "First tab switch"],
                ["type" => "copy_paste", "description" => "Tried to copy"],
                ["type" => "right_click", "description" => "Right clicked"],
            ],
        ]);

        $response->assertStatus(200)->assertJson([
            "success" => true,
        ]);

        $this->assertEquals(3, ExamViolation::count());

        $this->grade->refresh();
        $this->assertEquals(3, $this->grade->violation_count);
    }

    /** @test */
    public function it_rejects_invalid_violation_type()
    {
        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "invalid_type",
        ]);

        $response->assertStatus(400)->assertJson([
            "success" => false,
            "message" => "Invalid violation type.",
        ]);
    }

    /** @test */
    public function it_rejects_violation_for_ended_exam()
    {
        // End the exam
        $this->grade->update(["end_time" => now()]);

        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
        ]);

        $response->assertStatus(400)->assertJson([
            "success" => false,
            "message" => "Invalid exam session or exam already ended.",
        ]);
    }

    /** @test */
    public function it_can_get_violation_status()
    {
        $this->actingAs($this->student, "student");

        // Record some violations first
        AntiCheatService::recordViolation(
            $this->student,
            $this->exam,
            $this->examSession->id,
            $this->grade,
            "tab_switch",
        );

        AntiCheatService::recordViolation(
            $this->student,
            $this->exam,
            $this->examSession->id,
            $this->grade,
            "copy_paste",
        );

        $response = $this->getJson(
            "/student/anticheat/status?grade_id=" . $this->grade->id,
        );

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "data" => [
                "total_violations" => 2,
                "max_violations" => 10,
                "remaining_violations" => 8,
            ],
        ]);
    }

    /** @test */
    public function it_can_get_anticheat_config()
    {
        $this->actingAs($this->student, "student");

        $response = $this->getJson(
            "/student/anticheat/config/" . $this->exam->id,
        );

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "data" => [
                "enabled" => true,
                "fullscreen_required" => true,
                "block_tab_switch" => true,
                "block_copy_paste" => true,
                "block_right_click" => true,
                "detect_devtools" => true,
                "max_violations" => 10,
                "warning_threshold" => 3,
            ],
        ]);
    }

    /** @test */
    public function it_flags_grade_when_warning_threshold_reached()
    {
        $this->actingAs($this->student, "student");

        // Record violations up to warning threshold
        for ($i = 0; $i < 3; $i++) {
            AntiCheatService::recordViolation(
                $this->student,
                $this->exam,
                $this->examSession->id,
                $this->grade,
                "tab_switch",
            );
        }

        $this->grade->refresh();
        $this->assertTrue($this->grade->is_flagged);
        $this->assertNotNull($this->grade->flag_reason);
    }

    /** @test */
    public function it_indicates_when_max_violations_exceeded()
    {
        $this->actingAs($this->student, "student");

        // Set a lower max violations for testing
        $this->exam->update(["max_violations" => 3]);

        // Record violations
        for ($i = 0; $i < 3; $i++) {
            AntiCheatService::recordViolation(
                $this->student,
                $this->exam,
                $this->examSession->id,
                $this->grade,
                "tab_switch",
            );
        }

        $this->assertTrue(
            AntiCheatService::hasExceededLimit($this->grade, $this->exam),
        );
        $this->assertEquals(
            0,
            AntiCheatService::getRemainingViolations($this->grade, $this->exam),
        );
    }

    /** @test */
    public function it_records_violation_with_metadata()
    {
        $this->actingAs($this->student, "student");

        $metadata = [
            "key_pressed" => "Ctrl+C",
            "timestamp" => now()->toISOString(),
        ];

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "keyboard_shortcut",
            "description" => "Keyboard shortcut detected",
            "metadata" => $metadata,
        ]);

        $response->assertStatus(200);

        $violation = ExamViolation::first();
        $this->assertNotNull($violation->metadata);
        $this->assertEquals("Ctrl+C", $violation->metadata["key_pressed"]);
    }

    /** @test */
    public function it_heartbeat_returns_correct_status()
    {
        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/heartbeat", [
            "grade_id" => $this->grade->id,
        ]);

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "data" => [
                "exam_active" => true,
                "total_violations" => 0,
                "should_auto_submit" => false,
            ],
        ]);
    }

    /** @test */
    public function it_heartbeat_indicates_auto_submit_when_max_violations_exceeded()
    {
        $this->actingAs($this->student, "student");

        // Enable auto submit and set low max violations
        $this->exam->update([
            "max_violations" => 2,
            "auto_submit_on_max_violations" => true,
        ]);

        // Record violations to exceed limit
        for ($i = 0; $i < 3; $i++) {
            AntiCheatService::recordViolation(
                $this->student,
                $this->exam,
                $this->examSession->id,
                $this->grade,
                "tab_switch",
            );
        }

        $response = $this->postJson("/student/anticheat/heartbeat", [
            "grade_id" => $this->grade->id,
        ]);

        $response->assertStatus(200)->assertJson([
            "success" => true,
            "data" => [
                "should_auto_submit" => true,
            ],
        ]);
    }

    /** @test */
    public function anticheat_service_can_get_violation_summary()
    {
        // Record various violations
        AntiCheatService::recordViolation(
            $this->student,
            $this->exam,
            $this->examSession->id,
            $this->grade,
            "tab_switch",
        );

        AntiCheatService::recordViolation(
            $this->student,
            $this->exam,
            $this->examSession->id,
            $this->grade,
            "tab_switch",
        );

        AntiCheatService::recordViolation(
            $this->student,
            $this->exam,
            $this->examSession->id,
            $this->grade,
            "copy_paste",
        );

        $summary = AntiCheatService::getViolationSummary($this->grade);

        $this->assertEquals(3, $summary["total"]);
        $this->assertArrayHasKey("by_type", $summary);
        $this->assertEquals(2, $summary["details"]["tab_switch"]);
        $this->assertEquals(1, $summary["details"]["copy_paste"]);
    }

    /** @test */
    public function anticheat_service_can_clear_violations()
    {
        // Record some violations
        for ($i = 0; $i < 5; $i++) {
            AntiCheatService::recordViolation(
                $this->student,
                $this->exam,
                $this->examSession->id,
                $this->grade,
                "tab_switch",
            );
        }

        $this->grade->refresh();
        $this->assertEquals(5, $this->grade->violation_count);
        $this->assertTrue($this->grade->is_flagged);

        // Clear violations
        AntiCheatService::clearViolations(
            $this->grade,
            "Admin cleared violations",
        );

        $this->grade->refresh();
        $this->assertEquals(0, $this->grade->violation_count);
        $this->assertFalse($this->grade->is_flagged);
        $this->assertEquals(
            0,
            ExamViolation::byGrade($this->grade->id)->count(),
        );
    }

    /** @test */
    public function anticheat_is_disabled_when_exam_setting_is_off()
    {
        // Disable anti-cheat for the exam
        $this->exam->update(["anticheat_enabled" => false]);

        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
        ]);

        $response->assertStatus(400)->assertJson([
            "success" => false,
            "message" => "Anti-cheat is not enabled for this exam.",
        ]);
    }

    /** @test */
    public function exam_violation_model_has_correct_relationships()
    {
        $violation = ExamViolation::create([
            "student_id" => $this->student->id,
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
            "description" => "Test violation",
        ]);

        $this->assertInstanceOf(Student::class, $violation->student);
        $this->assertInstanceOf(Exam::class, $violation->exam);
        $this->assertInstanceOf(ExamSession::class, $violation->examSession);
        $this->assertInstanceOf(Grade::class, $violation->grade);
    }

    /** @test */
    public function exam_violation_model_has_scopes()
    {
        // Create violations for different types
        ExamViolation::create([
            "student_id" => $this->student->id,
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
        ]);

        ExamViolation::create([
            "student_id" => $this->student->id,
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "copy_paste",
        ]);

        $this->assertEquals(
            2,
            ExamViolation::byStudent($this->student->id)->count(),
        );
        $this->assertEquals(2, ExamViolation::byExam($this->exam->id)->count());
        $this->assertEquals(
            2,
            ExamViolation::byGrade($this->grade->id)->count(),
        );
        $this->assertEquals(1, ExamViolation::byType("tab_switch")->count());
        $this->assertEquals(1, ExamViolation::byType("copy_paste")->count());
    }

    /** @test */
    public function grade_model_can_increment_specific_violation_types()
    {
        $this->grade->incrementViolation("tab_switch");
        $this->grade->incrementViolation("copy_paste");
        $this->grade->incrementViolation("fullscreen_exit");

        $this->grade->refresh();

        $this->assertEquals(3, $this->grade->violation_count);
        $this->assertEquals(1, $this->grade->tab_switch_count);
        $this->assertEquals(1, $this->grade->copy_paste_count);
        $this->assertEquals(1, $this->grade->fullscreen_exit_count);
    }

    /** @test */
    public function grade_model_can_get_violations_summary()
    {
        $this->grade->update([
            "violation_count" => 5,
            "tab_switch_count" => 2,
            "copy_paste_count" => 1,
            "fullscreen_exit_count" => 1,
            "right_click_count" => 1,
            "blur_count" => 0,
            "is_flagged" => true,
            "flag_reason" => "Too many violations",
        ]);

        $summary = $this->grade->getViolationsSummary();

        $this->assertEquals(5, $summary["total"]);
        $this->assertEquals(2, $summary["tab_switch"]);
        $this->assertEquals(1, $summary["copy_paste"]);
        $this->assertTrue($summary["is_flagged"]);
        $this->assertEquals("Too many violations", $summary["flag_reason"]);
    }

    /** @test */
    public function exam_model_returns_correct_anticheat_settings()
    {
        $settings = $this->exam->getAntiCheatSettings();

        $this->assertTrue($settings["enabled"]);
        $this->assertTrue($settings["fullscreen_required"]);
        $this->assertTrue($settings["block_tab_switch"]);
        $this->assertTrue($settings["block_copy_paste"]);
        $this->assertTrue($settings["block_right_click"]);
        $this->assertTrue($settings["detect_devtools"]);
        $this->assertEquals(10, $settings["max_violations"]);
        $this->assertEquals(3, $settings["warning_threshold"]);
        $this->assertFalse($settings["auto_submit_on_max_violations"]);
    }

    /** @test */
    public function anticheat_service_validates_violation_types()
    {
        $this->assertTrue(AntiCheatService::isValidViolationType("tab_switch"));
        $this->assertTrue(
            AntiCheatService::isValidViolationType("fullscreen_exit"),
        );
        $this->assertTrue(AntiCheatService::isValidViolationType("copy_paste"));
        $this->assertTrue(
            AntiCheatService::isValidViolationType("right_click"),
        );
        $this->assertTrue(AntiCheatService::isValidViolationType("devtools"));
        $this->assertTrue(AntiCheatService::isValidViolationType("blur"));
        $this->assertTrue(AntiCheatService::isValidViolationType("screenshot"));
        $this->assertTrue(
            AntiCheatService::isValidViolationType("keyboard_shortcut"),
        );

        $this->assertFalse(
            AntiCheatService::isValidViolationType("invalid_type"),
        );
        $this->assertFalse(AntiCheatService::isValidViolationType(""));
    }

    /** @test */
    public function anticheat_service_returns_correct_violation_labels()
    {
        $this->assertEquals(
            "Pindah Tab/Window",
            AntiCheatService::getViolationLabel("tab_switch"),
        );
        $this->assertEquals(
            "Keluar Fullscreen",
            AntiCheatService::getViolationLabel("fullscreen_exit"),
        );
        $this->assertEquals(
            "Copy/Paste",
            AntiCheatService::getViolationLabel("copy_paste"),
        );
    }

    /** @test */
    public function student_cannot_access_other_students_violation_status()
    {
        // Create another student
        $otherStudent = Student::create([
            "classroom_id" => $this->student->classroom_id,
            "nisn" => "9876543210",
            "name" => "Other Student",
            "password" => "password123",
            "gender" => "P",
        ]);

        // Create grade for other student
        $otherGrade = Grade::create([
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "student_id" => $otherStudent->id,
            "duration" => 3600000,
            "start_time" => now(),
            "end_time" => null,
            "total_correct" => 0,
            "grade" => 0,
        ]);

        $this->actingAs($this->student, "student");

        $response = $this->getJson(
            "/student/anticheat/status?grade_id=" . $otherGrade->id,
        );

        $response->assertStatus(404);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_anticheat_endpoints()
    {
        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "tab_switch",
        ]);

        // JSON requests return 401 Unauthorized instead of redirect
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_record_remote_desktop_violation()
    {
        $this->actingAs($this->student, "student");

        $response = $this->postJson("/student/anticheat/violation", [
            "exam_id" => $this->exam->id,
            "exam_session_id" => $this->examSession->id,
            "grade_id" => $this->grade->id,
            "violation_type" => "remote_desktop",
            "description" => "Terdeteksi penggunaan Remote Desktop",
            "metadata" => [
                "indicators" => [
                    ["method" => "webgl_renderer", "value" => "SwiftShader"]
                ],
                "colorDepth" => 16,
            ],
        ]);

        $response->assertStatus(200)->assertJson([
            "success" => true,
        ]);

        $this->assertDatabaseHas("exam_violations", [
            "student_id" => $this->student->id,
            "exam_id" => $this->exam->id,
            "violation_type" => "remote_desktop",
        ]);
    }

    /** @test */
    public function remote_desktop_is_valid_violation_type()
    {
        $this->assertTrue(
            AntiCheatService::isValidViolationType("remote_desktop"),
        );
    }

    /** @test */
    public function remote_desktop_has_correct_label()
    {
        $this->assertEquals(
            "Remote Desktop",
            AntiCheatService::getViolationLabel("remote_desktop"),
        );
    }
}
