<?php

use Illuminate\Support\Facades\Route;

//prefix "admin"
Route::prefix("admin")->group(function () {
    //middleware "auth"
    Route::group(["middleware" => ["auth"]], function () {
        //route dashboard
        Route::get(
            "/dashboard",
            App\Http\Controllers\Admin\DashboardController::class,
        )->name("admin.dashboard");

        // User Management (Admin Only)
        Route::middleware(['admin.only'])->group(function () {
            Route::resource(
                "/users",
                \App\Http\Controllers\Admin\UserController::class,
                ["as" => "admin"]
            );
        });

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

        //route student import
        Route::get("/students/import", [
            \App\Http\Controllers\Admin\StudentController::class,
            "import",
        ])->name("admin.students.import");

        //route student store import
        Route::post("/students/import", [
            \App\Http\Controllers\Admin\StudentController::class,
            "storeImport",
        ])->name("admin.students.storeImport");

        //route toggle block student
        Route::post("/students/{student}/toggle-block", [
            \App\Http\Controllers\Admin\StudentController::class,
            "toggleBlock",
        ])->name("admin.students.toggleBlock");

        //route resource students
        Route::resource(
            "/students",
            \App\Http\Controllers\Admin\StudentController::class,
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

        //route exam preview
        Route::get("/exams/{exam}/preview", [
            \App\Http\Controllers\Admin\ExamController::class,
            "preview",
        ])->name("admin.exams.preview");

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

        //route activity logs cleanup
        Route::delete("/activity-logs-cleanup", [
            \App\Http\Controllers\Admin\ActivityLogController::class,
            "cleanup",
        ])->name("admin.activity-logs.cleanup");

        // Violation Logs (Anti-Cheat)
        Route::get("/violation-logs", [
            \App\Http\Controllers\Admin\ViolationLogController::class,
            "index",
        ])->name("admin.violation-logs.index");

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

        // Duplicate Question Check API
        Route::post("/questions/check-duplicate", [
            \App\Http\Controllers\Admin\ExamController::class,
            "checkDuplicate",
        ])->name("admin.questions.checkDuplicate");

        // PDF Export Routes
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
    });
});

//route homepage (student login)
Route::get("/", function () {
    //cek session admin
    if (auth()->check()) {
        return redirect()->route("admin.dashboard");
    }

    //cek session student
    if (auth()->guard("student")->check()) {
        return redirect()->route("student.dashboard");
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
    });
});
