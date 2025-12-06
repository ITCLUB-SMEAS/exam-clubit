<?php

namespace App\Http\Controllers\Student;

use Carbon\Carbon;
use App\Models\Grade;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\AntiCheatService;
use App\Services\ExamScoringService;
use App\Services\ExamTimerService;
use App\Services\ExamCompletionService;

class ExamController extends Controller
{
    public function __construct(
        protected ExamScoringService $scoringService,
        protected ExamTimerService $timerService,
        protected ExamCompletionService $completionService
    ) {}
    /**
     * confirmation
     *
     * @param  mixed $id
     * @return void
     */
    public function confirmation($id)
    {
        //get exam group with ownership check
        $exam_group = ExamGroup::with(
            "exam.lesson",
            "exam_session",
            "student.classroom",
        )
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("id", $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route("student.dashboard")
                ->with("error", "Ujian tidak ditemukan.");
        }

        //get grade / nilai with ownership check
        $grade = Grade::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        // Get anti-cheat config for display
        $anticheat_config = AntiCheatService::getAntiCheatConfig(
            $exam_group->exam,
        );

        //return with inertia
        return inertia("Student/Exams/Confirmation", [
            "exam_group" => $exam_group,
            "grade" => $grade,
            "anticheat_config" => $anticheat_config,
            "attendance" => [
                "required" => $exam_group->exam_session->require_attendance,
                "checked_in" => $exam_group->isCheckedIn(),
                "checked_in_at" => $exam_group->checked_in_at?->format('H:i:s'),
                "session_id" => $exam_group->exam_session_id,
            ],
        ]);
    }

    /**
     * retryExam - Remedial untuk siswa yang tidak lulus
     */
    public function retryExam($id)
    {
        $exam_group = ExamGroup::with("exam", "exam_session")
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("id", $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route("student.dashboard")->with("error", "Ujian tidak ditemukan.");
        }

        $grade = Grade::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        // Check if retry is allowed
        $maxAttempts = $exam_group->exam->max_attempts ?? 1;
        $currentAttempt = $grade->attempt_number ?? 1;

        if ($grade->status !== 'failed' || $currentAttempt >= $maxAttempts) {
            return redirect()->route("student.exams.confirmation", $id)
                ->with("error", "Anda tidak dapat mengulang ujian ini.");
        }

        // Delete old answers for this attempt
        Answer::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->delete();

        // Reset grade for new attempt
        $grade->update([
            'start_time' => null,
            'end_time' => null,
            'duration' => $exam_group->exam->duration * 60 * 1000,
            'total_correct' => 0,
            'grade' => 0,
            'points_possible' => 0,
            'points_earned' => 0,
            'attempt_status' => 'not_started',
            'attempt_number' => $currentAttempt + 1,
            'status' => 'pending',
            'violation_count' => 0,
        ]);

        return redirect()->route("student.exams.startExam", $id);
    }

    /**
     * startExam
     *
     * @param  mixed $id
     * @return void
     */
    public function startExam($id)
    {
        //get exam group
        $exam_group = ExamGroup::with(
            "exam.lesson",
            "exam_session",
            "student.classroom",
        )
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("id", $id)
            ->first();

        if (!$exam_group) {
            return redirect()
                ->route("student.dashboard")
                ->with("error", "Ujian tidak ditemukan.");
        }

        //get grade / nilai
        $grade = Grade::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        if (!$grade) {
            return redirect()
                ->route("student.dashboard")
                ->with("error", "Data ujian tidak ditemukan.");
        }

        // Guard: session window must be active
        if ($redirect = $this->guardExamSchedule($exam_group, $grade)) {
            return $redirect;
        }

        // Guard: check attendance if required
        if ($exam_group->exam_session->require_attendance && !$exam_group->isCheckedIn()) {
            return redirect()
                ->route("student.exams.confirmation", $exam_group->id)
                ->with("error", "Anda harus melakukan absensi terlebih dahulu sebelum memulai ujian.");
        }

        // Prevent restart after completion
        if (
            $grade->end_time !== null ||
            $grade->attempt_status === "completed"
        ) {
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        // Set start time once
        $justStarted = false;
        if ($grade->start_time === null) {
            $grade->start_time = Carbon::now();
            $justStarted = true;
        }

        // Mark attempt in progress and increment attempt count on first start
        if (
            $grade->attempt_status === null ||
            $grade->attempt_status === "not_started"
        ) {
            $grade->attempt_status = "in_progress";
            $grade->attempt_count = ($grade->attempt_count ?? 0) + 1;
            $justStarted = true;
        }

        // Calculate remaining duration on server side
        $grade->duration = $this->calculateRemainingDurationMs(
            $exam_group,
            $grade,
        );
        $grade->save();

        // Auto-end if duration already exhausted
        if ($grade->duration <= 0) {
            $this->finalizeExam($exam_group, $grade);
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        // Log exam start activity (only first time)
        if ($justStarted) {
            ActivityLogService::logExamStart(
                auth()->guard("student")->user(),
                $exam_group->exam,
                $exam_group->exam_session,
            );
        }

        // Check if answers already exist (to preserve question selection on refresh)
        $existingAnswers = Answer::where("student_id", auth()->guard("student")->user()->id)
            ->where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->exists();

        // Only generate new questions if no answers exist yet
        if (!$existingAnswers) {
            DB::transaction(function () use ($exam_group) {
                //cek apakah questions / soal ujian di random
                if ($exam_group->exam->random_question == "Y") {
                    $query = Question::where("exam_id", $exam_group->exam->id)
                        ->inRandomOrder();
                } else {
                    $query = Question::where("exam_id", $exam_group->exam->id);
                }

                // Apply question limit if set
                if ($exam_group->exam->question_limit) {
                    $query->limit($exam_group->exam->question_limit);
                }

                $questions = $query->get();
                $question_order = 1;

                foreach ($questions as $question) {
                    $options = [1, 2];
                    if (!empty($question->option_3)) $options[] = 3;
                    if (!empty($question->option_4)) $options[] = 4;
                    if (!empty($question->option_5)) $options[] = 5;

                    if ($exam_group->exam->random_answer == "Y") {
                        shuffle($options);
                    }

                    $isEssay = $question->question_type === Question::TYPE_ESSAY;
                    $isMultiple = in_array($question->question_type, [
                        Question::TYPE_MULTIPLE_CHOICE_SINGLE,
                        Question::TYPE_MULTIPLE_CHOICE_MULTIPLE
                    ]);

                    Answer::create([
                        "exam_id" => $exam_group->exam->id,
                        "exam_session_id" => $exam_group->exam_session->id,
                        "question_id" => $question->id,
                        "student_id" => auth()->guard("student")->user()->id,
                        "question_order" => $question_order,
                        "answer_order" => $isMultiple ? implode(",", $options) : "1",
                        "answer" => 0,
                        "is_correct" => "N",
                        "answer_text" => null,
                        "answer_options" => null,
                        "points_awarded" => 0,
                        "needs_manual_review" => $isEssay,
                    ]);
                    $question_order++;
                }
            });
        }

        //redirect ke ujian halaman 1
        return redirect()->route("student.exams.show", [
            "id" => $exam_group->id,
            "page" => 1,
        ]);
    }

    /**
     * show
     *
     * @param  mixed $id
     * @param  mixed $page
     * @return void
     */
    public function show($id, $page)
    {
        //get exam group
        $exam_group = ExamGroup::with(
            "exam.lesson",
            "exam_session",
            "student.classroom",
        )
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("id", $id)
            ->first();

        if (!$exam_group) {
            return redirect()->route("student.dashboard");
        }

        //get grade / nilai
        $grade = Grade::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        if (!$grade) {
            return redirect()
                ->route("student.dashboard")
                ->with("error", "Data ujian tidak ditemukan.");
        }

        // Prevent accessing after completion
        if (
            $grade->end_time !== null ||
            $grade->attempt_status === "completed"
        ) {
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        // Check if exam is paused
        if ($grade->is_paused) {
            return inertia("Student/Exams/Paused", [
                "exam_group" => $exam_group,
                "grade" => $grade,
                "pause_reason" => $grade->pause_reason,
            ]);
        }

        // Guard: session window must be active
        if ($redirect = $this->guardExamSchedule($exam_group, $grade)) {
            return $redirect;
        }

        // Ensure start time and attempt status set for timer calculation
        if ($grade->start_time === null) {
            $grade->start_time = Carbon::now();
        }

        if (
            $grade->attempt_status === null ||
            $grade->attempt_status === "not_started"
        ) {
            $grade->attempt_status = "in_progress";
            $grade->attempt_count = ($grade->attempt_count ?? 0) + 1;
        }

        // Server-side duration calculation
        $remainingDuration = $this->calculateRemainingDurationMs(
            $exam_group,
            $grade,
        );
        $grade->duration = $remainingDuration;
        $grade->save();

        // Auto-end if waktu habis
        if ($remainingDuration <= 0) {
            $this->finalizeExam($exam_group, $grade);
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        //get all questions - single query with eager loading
        $all_questions = Answer::with("question:id,question,question_type,option_1,option_2,option_3,option_4,option_5,correct_answers,points,matching_pairs")
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("exam_id", $exam_group->exam->id)
            ->orderBy("question_order", "ASC")
            ->get();

        //count all question answered (support text/multiple)
        $question_answered = $all_questions
            ->filter(function ($item) {
                return $item->answer != 0 ||
                    !empty($item->answer_text) ||
                    !empty($item->answer_options);
            })
            ->count();

        //get question active from already loaded collection
        $question_active = $all_questions->firstWhere("question_order", $page);
        if ($question_active) {
            $question_active->load("question.exam:id,title,random_answer");
        }

        //explode atau pecah jawaban
        if ($question_active) {
            $answer_order = explode(",", $question_active->answer_order);
        } else {
            $answer_order = [];
        }

        //pass latest duration (already server-calculated)
        $duration = $grade;

        // Get anti-cheat configuration
        $anticheat_config = AntiCheatService::getAntiCheatConfig(
            $exam_group->exam,
        );

        // Get initial violation count
        $initial_violations = $duration->violation_count ?? 0;

        //return with inertia
        return inertia("Student/Exams/Show", [
            "id" => (int) $id,
            "page" => (int) $page,
            "exam_group" => $exam_group,
            "all_questions" => $all_questions,
            "question_answered" => $question_answered,
            "question_active" => $question_active,
            "answer_order" => $answer_order,
            "duration" => $duration,
            "anticheat_config" => $anticheat_config,
            "initial_violations" => $initial_violations,
            "face_detection_enabled" => $exam_group->exam->face_detection_enabled ?? false,
            "audio_detection_enabled" => $exam_group->exam->audio_detection_enabled ?? false,
        ]);
    }

    /**
     * updateDuration
     *
     * @param  mixed $request
     * @param  mixed $grade_id
     * @return void
     */
    public function updateDuration(Request $request, $grade_id)
    {
        $grade = Grade::where("id", $grade_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->firstOrFail();

        $exam_group = ExamGroup::where("exam_id", $grade->exam_id)
            ->where("exam_session_id", $grade->exam_session_id)
            ->where("student_id", $grade->student_id)
            ->first();

        if (!$exam_group) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Ujian tidak ditemukan.",
                ],
                404,
            );
        }

        $exam_group->load("exam", "exam_session");

        if ($grade->end_time !== null) {
            return response()->json([
                "success" => false,
                "message" => "Ujian sudah berakhir.",
            ]);
        }

        if ($grade->attempt_status === "completed") {
            return response()->json([
                "success" => false,
                "message" => "Ujian sudah berakhir.",
            ]);
        }

        $now = Carbon::now();
        if ($now->lt($exam_group->exam_session->start_time)) {
            return response()->json([
                "success" => false,
                "message" => "Ujian belum dimulai.",
            ]);
        }

        if ($grade->start_time === null) {
            $grade->start_time = Carbon::now();
        }

        if (
            $grade->attempt_status === null ||
            $grade->attempt_status === "not_started"
        ) {
            $grade->attempt_status = "in_progress";
            $grade->attempt_count = ($grade->attempt_count ?? 0) + 1;
        }

        // Hitung ulang sisa waktu di server
        $remaining = $this->calculateRemainingDurationMs($exam_group, $grade);

        $grade->duration = $remaining;
        $grade->save();

        // Jika waktu habis atau sesi selesai, akhiri ujian
        if ($remaining <= 0) {
            $this->finalizeExam($exam_group, $grade);

            return response()->json([
                "success" => true,
                "ended" => true,
                "duration" => 0,
            ]);
        }

        return response()->json([
            "success" => true,
            "message" => "Duration updated successfully.",
            "duration" => $remaining,
        ]);
    }

    /**
     * answerQuestion
     *
     * @param  mixed $request
     * @return void
     */
    public function answerQuestion(Request $request)
    {
        $grade = Grade::where("exam_id", $request->exam_id)
            ->where("exam_session_id", $request->exam_session_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->firstOrFail();

        $exam_group = ExamGroup::where("exam_id", $request->exam_id)
            ->where("exam_session_id", $request->exam_session_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        if (!$exam_group) {
            return redirect()
                ->route("student.dashboard")
                ->with("error", "Ujian tidak ditemukan.");
        }

        $exam_group->load("exam", "exam_session");

        if ($grade->end_time !== null) {
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        if ($grade->attempt_status === "completed") {
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        // Check if exam is paused
        if ($grade->is_paused) {
            return redirect()->back()->with("error", "Ujian sedang di-pause.");
        }

        if ($redirect = $this->guardExamSchedule($exam_group, $grade)) {
            return $redirect;
        }

        if ($grade->start_time === null) {
            $grade->start_time = Carbon::now();
        }

        if (
            $grade->attempt_status === null ||
            $grade->attempt_status === "not_started"
        ) {
            $grade->attempt_status = "in_progress";
            $grade->attempt_count = ($grade->attempt_count ?? 0) + 1;
        }

        $remaining = $this->calculateRemainingDurationMs($exam_group, $grade);
        $grade->duration = $remaining;
        $grade->save();

        if ($remaining <= 0) {
            $this->finalizeExam($exam_group, $grade);
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        //get question
        $question = Question::findOrFail($request->question_id);

        //get answer
        $answer = Answer::where("exam_id", $request->exam_id)
            ->where("exam_session_id", $request->exam_session_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("question_id", $request->question_id)
            ->first();

        $submittedOptions = $request->input(
            "answer_options",
            $request->input("answers"),
        );
        $submittedText = $request->input("answer_text");
        $submittedAnswer = $request->input("answer");
        $matchingAnswers = $request->input("matching_answers");

        [$isCorrect, $pointsAwarded, $needsReview] = $this->scoreAnswer(
            $question,
            $submittedAnswer,
            $submittedText,
            $submittedOptions,
            $matchingAnswers,
        );

        //update jawaban
        if ($answer) {
            $answer->answer = $submittedAnswer ?? 0;
            $answer->answer_text = $submittedText;
            $answer->answer_options = is_array($submittedOptions)
                ? array_values($submittedOptions)
                : null;
            $answer->matching_answers = $matchingAnswers;
            $answer->is_correct = $isCorrect;
            $answer->points_awarded = $pointsAwarded;
            $answer->needs_manual_review = $needsReview;
            $answer->update();
        }

        return redirect()->back();
    }

    /**
     * endExam
     *
     * @param  mixed $request
     * @return void
     */
    public function endExam(Request $request)
    {
        $grade = Grade::where("exam_id", $request->exam_id)
            ->where("exam_session_id", $request->exam_session_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->firstOrFail();

        $exam_group = ExamGroup::with("exam", "exam_session")
            ->where("id", $request->exam_group_id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        if (!$exam_group) {
            return redirect()
                ->route("student.dashboard")
                ->with("error", "Ujian tidak ditemukan.");
        }

        if ($grade->end_time !== null) {
            return redirect()->route(
                "student.exams.resultExam",
                $exam_group->id,
            );
        }

        // Recalculate remaining time to avoid early finishing before start window
        if ($redirect = $this->guardExamSchedule($exam_group, $grade)) {
            return $redirect;
        }

        $this->finalizeExam($exam_group, $grade);

        //redirect hasil
        return redirect()->route(
            "student.exams.resultExam",
            $request->exam_group_id,
        );
    }

    /**
     * resultExam
     *
     * @param  mixed $id
     * @return void
     */
    public function resultExam($exam_group_id)
    {
        //get exam group with ownership check
        $exam_group = ExamGroup::with(
            "exam.lesson",
            "exam_session",
            "student.classroom",
        )
            ->where("student_id", auth()->guard("student")->user()->id)
            ->where("id", $exam_group_id)
            ->first();

        if (!$exam_group) {
            return redirect()->route("student.dashboard")
                ->with("error", "Hasil ujian tidak ditemukan.");
        }

        //get grade / nilai with ownership check
        $grade = Grade::where("exam_id", $exam_group->exam->id)
            ->where("exam_session_id", $exam_group->exam_session->id)
            ->where("student_id", auth()->guard("student")->user()->id)
            ->first();

        if (!$grade) {
            return redirect()->route("student.dashboard")
                ->with("error", "Data nilai tidak ditemukan.");
        }

        // Get violation summary if anti-cheat was enabled
        $violation_summary = null;
        if ($grade->violation_count > 0) {
            $violation_summary = $grade->getViolationsSummary();
        }

        //return with inertia
        return inertia("Student/Exams/Result", [
            "exam_group" => $exam_group,
            "grade" => $grade,
            "violation_summary" => $violation_summary,
        ]);
    }

    /**
     * Guard jadwal ujian (mulai & selesai). Jika sesi sudah berakhir,
     * ujian otomatis diakhiri dan diarahkan ke hasil.
     */
    private function guardExamSchedule(ExamGroup $exam_group, Grade $grade)
    {
        $error = $this->timerService->validateSessionWindow($exam_group);
        
        if ($error === 'Ujian belum dapat dimulai. Silakan cek jadwal.') {
            return redirect()->route("student.dashboard")->with("error", $error);
        }

        if ($error === 'Sesi ujian telah berakhir.') {
            $this->completionService->finalizeExam(
                $exam_group, 
                $grade, 
                auth()->guard("student")->user()->id
            );
            return redirect()->route("student.exams.resultExam", $exam_group->id);
        }

        return null;
    }

    /**
     * Hitung sisa waktu ujian (ms) - delegated to service
     */
    private function calculateRemainingDurationMs(ExamGroup $exam_group, Grade $grade): int
    {
        return $this->timerService->calculateRemainingDurationMs($exam_group, $grade);
    }

    /**
     * Akhiri ujian dan hitung nilai jika belum selesai - delegated to service
     */
    private function finalizeExam(ExamGroup $exam_group, Grade $grade): void
    {
        $this->completionService->finalizeExam(
            $exam_group,
            $grade,
            auth()->guard("student")->user()->id
        );
    }

    /**
     * Skoring jawaban berdasarkan tipe soal - delegated to service
     */
    private function scoreAnswer(
        Question $question,
        $submittedAnswer,
        ?string $submittedText,
        $submittedOptions,
        $matchingAnswers = null,
    ): array {
        return $this->scoringService->scoreAnswer(
            $question,
            $submittedAnswer,
            $submittedText,
            $submittedOptions,
            $matchingAnswers
        );
    }
}
