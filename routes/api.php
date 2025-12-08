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

        // Read-only endpoints
        Route::get('/exams', [ExamController::class, 'index']);
        Route::get('/exams/{exam}', [ExamController::class, 'show']);
        Route::get('/exam-sessions', [ExamController::class, 'sessions']);
        Route::get('/grades', [GradeController::class, 'index']);
        Route::get('/grades/{grade}', [GradeController::class, 'show']);
        Route::get('/grades-statistics', [GradeController::class, 'statistics']);

        // Admin only - stricter rate limit for write operations
        Route::middleware(['ability:admin', 'throttle:30,1'])->group(function () {
            Route::apiResource('students', StudentController::class);
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
