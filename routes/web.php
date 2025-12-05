<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Offline page for PWA
Route::get('/offline', fn() => Inertia::render('Offline'))->name('offline');

//prefix "admin"
Route::prefix("admin")->group(function () {
    //middleware "auth"
    Route::group(["middleware" => ["auth"]], function () {
        //route dashboard
        Route::get(
            "/dashboard",
            App\Http\Controllers\Admin\DashboardController::class,
        )->name("admin.dashboard");

        // Profile
        Route::get("/profile", [\App\Http\Controllers\Admin\ProfileController::class, "index"])->name("admin.profile.index");
        Route::put("/profile", [\App\Http\Controllers\Admin\ProfileController::class, "update"])->name("admin.profile.update");
        Route::put("/profile/password", [\App\Http\Controllers\Admin\ProfileController::class, "updatePassword"])->name("admin.profile.password");
        Route::post("/profile/photo", [\App\Http\Controllers\Admin\ProfileController::class, "updatePhoto"])->name("admin.profile.photo");
        Route::get("/profile/2fa/setup", [\App\Http\Controllers\Admin\ProfileController::class, "setup2FA"])->name("admin.profile.2fa.setup");
        Route::post("/profile/2fa/enable", [\App\Http\Controllers\Admin\ProfileController::class, "enable2FA"])->name("admin.profile.2fa.enable");
        Route::post("/profile/2fa/disable", [\App\Http\Controllers\Admin\ProfileController::class, "disable2FA"])->name("admin.profile.2fa.disable");
        Route::post("/profile/2fa/regenerate", [\App\Http\Controllers\Admin\ProfileController::class, "regenerateCodes"])->name("admin.profile.2fa.regenerate");

        // ============================================
        // ADMIN ONLY ROUTES (User & Student Management)
        // ============================================
        Route::middleware(['admin.only'])->group(function () {
            // User Management
            Route::resource(
                "/users",
                \App\Http\Controllers\Admin\UserController::class,
                ["as" => "admin"]
            );

            // Student Management (sensitive data)
            Route::get("/students/import", [
                \App\Http\Controllers\Admin\StudentController::class,
                "import",
            ])->name("admin.students.import");

            Route::post("/students/import", [
                \App\Http\Controllers\Admin\StudentController::class,
                "storeImport",
            ])->name("admin.students.storeImport");

            // Bulk Photo Upload
            Route::get("/students/bulk-photo", [
                \App\Http\Controllers\Admin\StudentController::class,
                "bulkPhotoUpload",
            ])->name("admin.students.bulkPhotoUpload");

            Route::post("/students/bulk-photo", [
                \App\Http\Controllers\Admin\StudentController::class,
                "processBulkPhotoUpload",
            ])->name("admin.students.bulkPhotoUpload.process");

            Route::post("/students/{student}/toggle-block", [
                \App\Http\Controllers\Admin\StudentController::class,
                "toggleBlock",
            ])->name("admin.students.toggleBlock");

            // Bulk Password Reset
            Route::get("/students-bulk-password-reset", [
                \App\Http\Controllers\Admin\StudentController::class,
                "bulkPasswordReset",
            ])->name("admin.students.bulkPasswordReset");

            Route::post("/students-bulk-password-reset", [
                \App\Http\Controllers\Admin\StudentController::class,
                "executeBulkPasswordReset",
            ])->name("admin.students.executeBulkPasswordReset");

            Route::get("/students-by-classroom/{classroom}", [
                \App\Http\Controllers\Admin\StudentController::class,
                "getByClassroom",
            ])->name("admin.students.byClassroom");

            Route::resource(
                "/students",
                \App\Http\Controllers\Admin\StudentController::class,
                ["as" => "admin"],
            );

            // Activity Logs Cleanup (destructive action)
            Route::delete("/activity-logs-cleanup", [
                \App\Http\Controllers\Admin\ActivityLogController::class,
                "cleanup",
            ])->name("admin.activity-logs.cleanup");

            // Maintenance Mode
            Route::get("/maintenance", [
                \App\Http\Controllers\Admin\MaintenanceController::class,
                "index",
            ])->name("admin.maintenance.index");

            Route::post("/maintenance/toggle", [
                \App\Http\Controllers\Admin\MaintenanceController::class,
                "toggle",
            ])->name("admin.maintenance.toggle");

            // Cleanup Old Data
            Route::get("/cleanup", [
                \App\Http\Controllers\Admin\CleanupController::class,
                "index",
            ])->name("admin.cleanup.index");

            Route::post("/cleanup", [
                \App\Http\Controllers\Admin\CleanupController::class,
                "cleanup",
            ])->name("admin.cleanup.run");
        });

        // ============================================
        // ADMIN & GURU ROUTES (Teaching related)
        // ============================================
        //route resource lessons
        Route::resource(
            "/lessons",
            \App\Http\Controllers\Admin\LessonController::class,
            ["as" => "admin"],
        );

        //route resource classrooms
        Route::resource(
            "/classrooms",
            \App\Http\Controllers\Admin\ClassroomController::class,
            ["as" => "admin"],
        );

        //route resource rooms
        Route::resource(
            "/rooms",
            \App\Http\Controllers\Admin\RoomController::class,
            ["as" => "admin"],
        );

        //route resource exams
        Route::resource(
            "/exams",
            \App\Http\Controllers\Admin\ExamController::class,
            ["as" => "admin"],
        );

        //custom route for create question exam
        Route::get("/exams/{exam}/questions/create", [
            \App\Http\Controllers\Admin\ExamController::class,
            "createQuestion",
        ])->name("admin.exams.createQuestion");

        //custom route for store question exam
        Route::post("/exams/{exam}/questions/store", [
            \App\Http\Controllers\Admin\ExamController::class,
            "storeQuestion",
        ])->name("admin.exams.storeQuestion");

        //custom route for edit question exam
        Route::get("/exams/{exam}/questions/{question}/edit", [
            \App\Http\Controllers\Admin\ExamController::class,
            "editQuestion",
        ])->name("admin.exams.editQuestion");

        //custom route for update question exam
        Route::put("/exams/{exam}/questions/{question}/update", [
            \App\Http\Controllers\Admin\ExamController::class,
            "updateQuestion",
        ])->name("admin.exams.updateQuestion");

        //custom route for destroy question exam
        Route::delete("/exams/{exam}/questions/{question}/destroy", [
            \App\Http\Controllers\Admin\ExamController::class,
            "destroyQuestion",
        ])->name("admin.exams.destroyQuestion");

        // Bulk question operations
        Route::post("/exams/{exam}/questions/bulk-update-points", [
            \App\Http\Controllers\Admin\ExamController::class,
            "bulkUpdatePoints",
        ])->name("admin.exams.bulkUpdatePoints");

        Route::delete("/exams/{exam}/questions/bulk-delete", [
            \App\Http\Controllers\Admin\ExamController::class,
            "bulkDeleteQuestions",
        ])->name("admin.exams.bulkDeleteQuestions");

        //route exam preview
        Route::get("/exams/{exam}/preview", [
            \App\Http\Controllers\Admin\ExamController::class,
            "preview",
        ])->name("admin.exams.preview");

        //route exam duplicate
        Route::post("/exams/{exam}/duplicate", [
            \App\Http\Controllers\Admin\ExamController::class,
            "duplicate",
        ])->name("admin.exams.duplicate");

        //route item analysis
        Route::get("/exams/{exam}/analysis", [
            \App\Http\Controllers\Admin\ItemAnalysisController::class,
            "show",
        ])->name("admin.exams.analysis");

        //route question import
        Route::get("/exams/{exam}/questions/import", [
            \App\Http\Controllers\Admin\ExamController::class,
            "import",
        ])->name("admin.exam.questionImport");

        //route question store import
        Route::post("/exams/{exam}/questions/import", [
            \App\Http\Controllers\Admin\ExamController::class,
            "storeImport",
        ])->name("admin.exam.questionStoreImport");

        //route resource exam_sessions
        Route::resource(
            "/exam_sessions",
            \App\Http\Controllers\Admin\ExamSessionController::class,
            ["as" => "admin"],
        );

        //custom route for enrolle create
        Route::get("/exam_sessions/{exam_session}/enrolle/create", [
            \App\Http\Controllers\Admin\ExamSessionController::class,
            "createEnrolle",
        ])->name("admin.exam_sessions.createEnrolle");

        //custom route for enrolle store
        Route::post("/exam_sessions/{exam_session}/enrolle/store", [
            \App\Http\Controllers\Admin\ExamSessionController::class,
            "storeEnrolle",
        ])->name("admin.exam_sessions.storeEnrolle");

        //custom route for enrolle destroy
        Route::delete(
            "/exam_sessions/{exam_session}/enrolle/{exam_group}/destroy",
            [
                \App\Http\Controllers\Admin\ExamSessionController::class,
                "destroyEnrolle",
            ],
        )->name("admin.exam_sessions.destroyEnrolle");

        // Bulk enrollment routes
        Route::post("/exam_sessions/{exam_session}/bulk-enroll", [
            \App\Http\Controllers\Admin\ExamSessionController::class,
            "bulkEnrollClass",
        ])->name("admin.exam_sessions.bulkEnroll");

        Route::delete("/exam_sessions/{exam_session}/bulk-unenroll", [
            \App\Http\Controllers\Admin\ExamSessionController::class,
            "bulkUnenrollClass",
        ])->name("admin.exam_sessions.bulkUnenroll");

        // Attendance routes
        Route::get("/exam_sessions/{exam_session}/attendance", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "show",
        ])->name("admin.exam_sessions.attendance");

        Route::get("/exam_sessions/{exam_session}/attendance/qr", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "getQrCode",
        ])->name("admin.exam_sessions.attendance.qr");

        Route::post("/exam_sessions/{exam_session}/attendance/regenerate-token", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "regenerateToken",
        ])->name("admin.exam_sessions.attendance.regenerateToken");

        Route::post("/exam_sessions/{exam_session}/attendance/toggle", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "toggleRequirement",
        ])->name("admin.exam_sessions.attendance.toggle");

        Route::post("/exam_sessions/{exam_session}/attendance/manual-checkin", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "manualCheckIn",
        ])->name("admin.exam_sessions.attendance.manualCheckin");

        Route::get("/exam_sessions/{exam_session}/attendance/list", [
            \App\Http\Controllers\Admin\AttendanceController::class,
            "getAttendanceList",
        ])->name("admin.exam_sessions.attendance.list");

        // Exam Cards routes
        Route::get("/exam_sessions/{exam_session}/cards", [
            \App\Http\Controllers\Admin\ExamCardController::class,
            "preview",
        ])->name("admin.exam_sessions.cards.preview");

        Route::get("/exam_sessions/{exam_session}/cards/print", [
            \App\Http\Controllers\Admin\ExamCardController::class,
            "print",
        ])->name("admin.exam_sessions.cards.print");

        Route::get("/exam_sessions/{exam_session}/cards/print/{student}", [
            \App\Http\Controllers\Admin\ExamCardController::class,
            "printSingle",
        ])->name("admin.exam_sessions.cards.printSingle");

        //route index reports
        Route::get("/reports", [
            \App\Http\Controllers\Admin\ReportController::class,
            "index",
        ])->name("admin.reports.index");

        //route index reports filter
        Route::get("/reports/filter", [
            \App\Http\Controllers\Admin\ReportController::class,
            "filter",
        ])->name("admin.reports.filter");

        //route index reports export
        Route::get("/reports/export", [
            \App\Http\Controllers\Admin\ReportController::class,
            "export",
        ])->name("admin.reports.export");

        //route activity logs
        Route::get("/activity-logs", [
            \App\Http\Controllers\Admin\ActivityLogController::class,
            "index",
        ])->name("admin.activity-logs.index");

        //route activity logs show
        Route::get("/activity-logs/{activityLog}", [
            \App\Http\Controllers\Admin\ActivityLogController::class,
            "show",
        ])->name("admin.activity-logs.show");

        //route activity logs stats (API)
        Route::get("/activity-logs-stats", [
            \App\Http\Controllers\Admin\ActivityLogController::class,
            "stats",
        ])->name("admin.activity-logs.stats");

        //route activity logs export
        Route::get("/activity-logs-export", [
            \App\Http\Controllers\Admin\ActivityLogController::class,
            "export",
        ])->name("admin.activity-logs.export");

        // Violation Logs (Anti-Cheat)
        Route::get("/violation-logs", [
            \App\Http\Controllers\Admin\ViolationLogController::class,
            "index",
        ])->name("admin.violation-logs.index");

        Route::get("/violation-logs/{violation}/snapshot", [
            \App\Http\Controllers\Admin\ViolationLogController::class,
            "snapshot",
        ])->name("admin.violation-logs.snapshot");

        // Analytics & Statistics
        Route::get("/analytics", [
            \App\Http\Controllers\Admin\AnalyticsController::class,
            "index",
        ])->name("admin.analytics.index");

        Route::get("/analytics/exam/{exam}", [
            \App\Http\Controllers\Admin\AnalyticsController::class,
            "examDetail",
        ])->name("admin.analytics.exam");

        Route::get("/analytics/students", [
            \App\Http\Controllers\Admin\AnalyticsController::class,
            "studentPerformance",
        ])->name("admin.analytics.students");

        // Notifications
        Route::get("/notifications", [\App\Http\Controllers\Admin\NotificationController::class, "index"])->name("admin.notifications.index");
        Route::get("/notifications/unread", [\App\Http\Controllers\Admin\NotificationController::class, "unread"])->name("admin.notifications.unread");
        Route::post("/notifications/mark-read", [\App\Http\Controllers\Admin\NotificationController::class, "markAsRead"])->name("admin.notifications.markAsRead");
        Route::delete("/notifications/{id}", [\App\Http\Controllers\Admin\NotificationController::class, "destroy"])->name("admin.notifications.destroy");
        Route::delete("/notifications", [\App\Http\Controllers\Admin\NotificationController::class, "destroyAll"])->name("admin.notifications.destroyAll");

        // Question Categories
        Route::resource(
            "/question-categories",
            \App\Http\Controllers\Admin\QuestionCategoryController::class,
            ["as" => "admin"]
        );

        // Question Bank
        Route::resource(
            "/question-bank",
            \App\Http\Controllers\Admin\QuestionBankController::class,
            ["as" => "admin"]
        );

        // Import from bank to exam
        Route::post("/exams/{exam}/import-from-bank", [
            \App\Http\Controllers\Admin\QuestionBankController::class,
            "importToExam",
        ])->name("admin.exams.importFromBank");

        // API: Get questions for selection
        Route::get("/question-bank-list", [
            \App\Http\Controllers\Admin\QuestionBankController::class,
            "getQuestions",
        ])->name("admin.question-bank.list");

        // Time Extension
        Route::get("/time-extension", [
            \App\Http\Controllers\Admin\TimeExtensionController::class,
            "index",
        ])->name("admin.time-extension.index");

        Route::post("/time-extension/{grade}", [
            \App\Http\Controllers\Admin\TimeExtensionController::class,
            "extend",
        ])->name("admin.time-extension.extend");

        // Exam Pause/Resume
        Route::get("/exam-pause", [
            \App\Http\Controllers\Admin\ExamPauseController::class,
            "index",
        ])->name("admin.exam-pause.index");

        Route::post("/exam-pause/{grade}", [
            \App\Http\Controllers\Admin\ExamPauseController::class,
            "pause",
        ])->name("admin.exam-pause.pause");

        Route::post("/exam-resume/{grade}", [
            \App\Http\Controllers\Admin\ExamPauseController::class,
            "resume",
        ])->name("admin.exam-pause.resume");

        Route::post("/exam-pause-all/{examSession}", [
            \App\Http\Controllers\Admin\ExamPauseController::class,
            "pauseAll",
        ])->name("admin.exam-pause.pauseAll");

        Route::post("/exam-resume-all/{examSession}", [
            \App\Http\Controllers\Admin\ExamPauseController::class,
            "resumeAll",
        ])->name("admin.exam-pause.resumeAll");

        // Duplicate Question Check API
        Route::post("/questions/check-duplicate", [
            \App\Http\Controllers\Admin\ExamController::class,
            "checkDuplicate",
        ])->name("admin.questions.checkDuplicate");

        // AI Question Generator
        Route::get("/ai-generator", [
            \App\Http\Controllers\Admin\AIQuestionController::class,
            "index",
        ])->name("admin.ai.index");

        Route::post("/ai/generate-questions", [
            \App\Http\Controllers\Admin\AIQuestionController::class,
            "generate",
        ])->name("admin.ai.generateQuestions");

        Route::post("/exams/{exam}/ai-save-questions", [
            \App\Http\Controllers\Admin\AIQuestionController::class,
            "saveToExam",
        ])->name("admin.ai.saveToExam");

        // Plagiarism Detection
        Route::get("/plagiarism", [
            \App\Http\Controllers\Admin\PlagiarismController::class,
            "index",
        ])->name("admin.plagiarism.index");

        // PDF Export Routes (rate limited to prevent DoS)
        Route::middleware('throttle:10,1')->group(function () {
            Route::get("/export/grade/{grade}/pdf", [
                \App\Http\Controllers\Admin\ExportController::class,
                "exportGradePdf",
            ])->name("admin.export.grade.pdf");

            Route::get("/export/exam/{exam}/pdf", [
                \App\Http\Controllers\Admin\ExportController::class,
                "exportExamResultsPdf",
            ])->name("admin.export.exam.pdf");

            Route::get("/export/student/{student}/pdf", [
                \App\Http\Controllers\Admin\ExportController::class,
                "exportStudentReportPdf",
            ])->name("admin.export.student.pdf");
        });

        // Essay Grading Routes
        Route::get("/essay-grading", [
            \App\Http\Controllers\Admin\EssayGradingController::class,
            "index",
        ])->name("admin.essay-grading.index");

        Route::post("/essay-grading/{answer}", [
            \App\Http\Controllers\Admin\EssayGradingController::class,
            "grade",
        ])->name("admin.essay-grading.grade");

        Route::post("/essay-grading-bulk", [
            \App\Http\Controllers\Admin\EssayGradingController::class,
            "bulkGrade",
        ])->name("admin.essay-grading.bulk");

        // Real-time Exam Monitor
        Route::get("/monitor", [\App\Http\Controllers\Admin\ExamMonitorController::class, "index"])->name("admin.monitor.index");
        Route::get("/monitor/{examSession}", [\App\Http\Controllers\Admin\ExamMonitorController::class, "show"])->name("admin.monitor.show");
        Route::get("/monitor/{examSession}/participants", [\App\Http\Controllers\Admin\ExamMonitorController::class, "participants"])->name("admin.monitor.participants");
        Route::get("/monitor/{examSession}/violations", [\App\Http\Controllers\Admin\ExamMonitorController::class, "violations"])->name("admin.monitor.violations");

        // Backup Management (Admin Only)
        Route::middleware(['admin.only'])->group(function () {
            Route::get("/backup", [\App\Http\Controllers\Admin\BackupController::class, "index"])->name("admin.backup.index");
            Route::post("/backup", [\App\Http\Controllers\Admin\BackupController::class, "create"])->name("admin.backup.create");
            Route::get("/backup/{filename}/download", [\App\Http\Controllers\Admin\BackupController::class, "download"])->name("admin.backup.download");
            Route::delete("/backup/{filename}", [\App\Http\Controllers\Admin\BackupController::class, "destroy"])->name("admin.backup.destroy");
            Route::post("/backup/cleanup", [\App\Http\Controllers\Admin\BackupController::class, "cleanup"])->name("admin.backup.cleanup");
        });

        // Question Version History
        Route::get("/questions/{question}/versions", [\App\Http\Controllers\Admin\QuestionVersionController::class, "index"])->name("admin.questions.versions");
        Route::post("/questions/{question}/restore/{version}", [\App\Http\Controllers\Admin\QuestionVersionController::class, "restore"])->name("admin.questions.restore");
    });

    // 2FA Challenge (outside main auth middleware)
    Route::get("/two-factor/challenge", [\App\Http\Controllers\Admin\TwoFactorChallengeController::class, "show"])->name("admin.two-factor.challenge")->middleware('auth');
    Route::post("/two-factor/challenge", [\App\Http\Controllers\Admin\TwoFactorChallengeController::class, "verify"])->name("admin.two-factor.verify")->middleware('auth');
});

//route homepage (student login)
Route::get("/", function () {
    //cek session student dulu (prioritas)
    if (auth()->guard("student")->check()) {
        return redirect()->route("student.dashboard");
    }

    //cek session admin
    if (auth()->check()) {
        return redirect()->route("admin.dashboard");
    }

    //return view login
    return \Inertia\Inertia::render("Student/Login/Index", [
        'turnstileSiteKey' => config('services.turnstile.site_key'),
    ]);
});

//override fortify login with turnstile middleware
Route::post('/admin/login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest:web', 'turnstile'])
    ->name('login.store');

//redirect /login to admin login
Route::redirect('/login', '/admin/login');

//login students
Route::post(
    "/students/login",
    \App\Http\Controllers\Student\LoginController::class,
)->middleware('turnstile')->name("student.login");

//prefix "student"
Route::prefix("student")->group(function () {
    //middleware "student"
    Route::group(["middleware" => "student"], function () {
        //route dashboard
        Route::get(
            "/dashboard",
            App\Http\Controllers\Student\DashboardController::class,
        )->name("student.dashboard");

        //route logout student
        Route::post(
            "/logout",
            App\Http\Controllers\Student\LogoutController::class,
        )->name("student.logout");

        //route student profile
        Route::get("/profile", [
            App\Http\Controllers\Student\ProfileController::class,
            "index",
        ])->name("student.profile");

        Route::put("/profile", [
            App\Http\Controllers\Student\ProfileController::class,
            "update",
        ])->name("student.profile.update");

        Route::put("/profile/password", [
            App\Http\Controllers\Student\ProfileController::class,
            "updatePassword",
        ])->name("student.profile.password");

        //route exam confirmation
        Route::get("/exam-confirmation/{id}", [
            App\Http\Controllers\Student\ExamController::class,
            "confirmation",
        ])->name("student.exams.confirmation");

        // Attendance check-in routes
        Route::post("/checkin/qr", [
            App\Http\Controllers\Student\CheckinController::class,
            "qrCheckin",
        ])->name("student.checkin.qr");

        Route::post("/checkin/token", [
            App\Http\Controllers\Student\CheckinController::class,
            "tokenCheckin",
        ])->name("student.checkin.token");

        //route exam start
        Route::get("/exam-start/{id}", [
            App\Http\Controllers\Student\ExamController::class,
            "startExam",
        ])->name("student.exams.startExam");

        //route exam retry (remedial)
        Route::get("/exam-retry/{id}", [
            App\Http\Controllers\Student\ExamController::class,
            "retryExam",
        ])->name("student.exams.retryExam");

        //route exam show
        Route::get("/exam/{id}/{page}", [
            App\Http\Controllers\Student\ExamController::class,
            "show",
        ])->name("student.exams.show");

        //route exam update duration
        Route::put("/exam-duration/update/{grade_id}", [
            App\Http\Controllers\Student\ExamController::class,
            "updateDuration",
        ])->name("student.exams.update_duration");

        //route answer question
        Route::post("/exam-answer", [
            App\Http\Controllers\Student\ExamController::class,
            "answerQuestion",
        ])->name("student.exams.answerQuestion");

        //route exam end
        Route::post("/exam-end", [
            App\Http\Controllers\Student\ExamController::class,
            "endExam",
        ])->name("student.exams.endExam");

        //route exam result
        Route::get("/exam-result/{exam_group_id}", [
            App\Http\Controllers\Student\ExamController::class,
            "resultExam",
        ])->name("student.exams.resultExam");

        //route anti-cheat violation (rate limited: 30 per minute)
        Route::post("/anticheat/violation", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "recordViolation",
        ])->middleware('throttle:30,1')->name("student.anticheat.violation");

        //route anti-cheat batch violations (rate limited: 10 per minute)
        Route::post("/anticheat/violations", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "recordBatchViolations",
        ])->middleware('throttle:10,1')->name("student.anticheat.violations");

        //route anti-cheat status (rate limited: 60 per minute)
        Route::get("/anticheat/status", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "getViolationStatus",
        ])->middleware('throttle:60,1')->name("student.anticheat.status");

        //route anti-cheat config (rate limited: 20 per minute)
        Route::get("/anticheat/config/{examId}", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "getConfig",
        ])->middleware('throttle:20,1')->name("student.anticheat.config");

        //route anti-cheat heartbeat (rate limited: 60 per minute)
        Route::post("/anticheat/heartbeat", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "heartbeat",
        ])->middleware('throttle:60,1')->name("student.anticheat.heartbeat");

        //route anti-cheat server time (rate limited: 30 per minute)
        Route::get("/anticheat/server-time", [
            App\Http\Controllers\Student\AntiCheatController::class,
            "serverTime",
        ])->middleware('throttle:30,1')->name("student.anticheat.serverTime");
    });
});

// Telegram Webhook (no CSRF)
Route::post('/telegram/webhook', [App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
    ->name('telegram.webhook');
