<?php

use App\Http\Controllers\Api\Admin\AssessmentController as AdminAssessmentController;
use App\Http\Controllers\Api\Admin\OptionController;
use App\Http\Controllers\Api\Admin\QuestionController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\User\AssessmentController;
use Illuminate\Support\Facades\Route;

// Public assessment browsing endpoints
Route::prefix('assessments')->controller(AssessmentController::class)->group(function () {
    Route::get('/', 'index');
    Route::get('/mata-pelajaran/{id}', 'byMataPelajaran');
    Route::get('/{slug}', 'show');
});

Route::middleware('auth:sanctum')->group(function () {
    // Student assessment attempt endpoints
    Route::prefix('assessments')->controller(AssessmentController::class)->group(function () {
        Route::post('/{id}/start', 'start');
        Route::post('/{attemptId}/answers', 'submitAnswer');
        Route::post('/{attemptId}/finish', 'finishAttempt');
    });

    // Results endpoints (unified for both user and admin)
    Route::prefix('results')->controller(ResultController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{attemptId}', 'show');
    });

    // Admin/Guru endpoints
    Route::middleware('role:admin,guru')->group(function () {
        // Assessment CRUD
        Route::controller(AdminAssessmentController::class)->group(function () {
            Route::post('/assessments', 'store');
            Route::put('/assessments/{id}', 'update');
            Route::delete('/assessments/{id}', 'destroy');
        });

        // Question CRUD
        Route::controller(QuestionController::class)->group(function () {
            Route::post('/assessments/{assessmentId}/questions', 'store');
            Route::put('/questions/{id}', 'update');
            Route::delete('/questions/{id}', 'destroy');
        });

        // Option CRUD
        Route::controller(OptionController::class)->group(function () {
            Route::post('/questions/{questionId}/options', 'store');
            Route::put('/options/{id}', 'update');
            Route::delete('/options/{id}', 'destroy');
        });
    });
});
