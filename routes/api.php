<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ExamController;
use App\Http\Controllers\Api\GradeController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
| All routes are prefixed with /api/v1
| Example: GET /api/v1/exams
*/

Route::prefix('v1')->group(function () {
    // Public routes - strict rate limiting for login
    Route::middleware(['throttle:5,1'])->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes with rate limiting
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Read-only endpoints (requires 'read' ability)
        Route::middleware('ability:read')->group(function () {
            Route::get('/exams', [ExamController::class, 'index']);
            Route::get('/exams/{exam}', [ExamController::class, 'show']);
            Route::get('/exam-sessions', [ExamController::class, 'sessions']);
            Route::get('/grades', [GradeController::class, 'index']);
            Route::get('/grades/{grade}', [GradeController::class, 'show']);
            Route::get('/grades-statistics', [GradeController::class, 'statistics']);
            Route::get('/students', [StudentController::class, 'index']);
            Route::get('/students/{student}', [StudentController::class, 'show']);
        });

        // Write endpoints (requires 'write' ability)
        Route::middleware(['ability:write', 'throttle:30,1'])->group(function () {
            Route::post('/students', [StudentController::class, 'store']);
            Route::put('/students/{student}', [StudentController::class, 'update']);
            Route::delete('/students/{student}', [StudentController::class, 'destroy']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| Legacy Routes (Backward Compatibility)
|--------------------------------------------------------------------------
| Redirect old /api/* routes to /api/v1/* for backward compatibility
| Will be removed in future version
*/

Route::middleware(['api'])->group(function () {
    Route::any('/{any}', function ($any) {
        return redirect("/api/v1/{$any}", 301);
    })->where('any', '^(?!v1).*$');
});
