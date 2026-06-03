<?php

use App\Http\Controllers\LessonController;
use App\Http\Controllers\MataPelajaranController;
use App\Http\Controllers\UserProgressController;
use App\Http\Controllers\UserResumeController;
use Illuminate\Support\Facades\Route;

Route::get('/mata-pelajarans', [MataPelajaranController::class, 'index']);
Route::get('/lessons', [LessonController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/mata-pelajarans/all', [MataPelajaranController::class, 'getAll']);
    Route::get('/mata-pelajarans/{mataPelajaran}', [MataPelajaranController::class, 'show']);
    Route::get('/lessons/all', [LessonController::class, 'getAll']);
    Route::get('/lessons/{slug}', [LessonController::class, 'show']);
    Route::get('/lessons/completed', [LessonController::class, 'getCompleted']);
    Route::get('/mata-pelajarans/{mataPelajaran}/lessons', [LessonController::class, 'byMataPelajaran']);

    // User Resume Routes - resumes per lesson
    Route::get('/user/resumes', [UserResumeController::class, 'index']);
    Route::post('/user/resumes', [UserResumeController::class, 'store']);
    Route::get('/user/resumes/{id}', [UserResumeController::class, 'show']);
    Route::put('/user/resumes/{id}', [UserResumeController::class, 'update']);
    Route::delete('/user/resumes/{id}', [UserResumeController::class, 'destroy']);

    Route::middleware('role:admin,guru')->group(function () {
    Route::post('/mata-pelajarans', [MataPelajaranController::class, 'store']);
    Route::put('/mata-pelajarans/{mataPelajaran}', [MataPelajaranController::class, 'update']);
    Route::delete('/mata-pelajarans/{mataPelajaran}', [MataPelajaranController::class, 'destroy']);

    Route::post('/lessons', [LessonController::class, 'store']);
    Route::post('/lessons/{slug}', [LessonController::class, 'update']); // Use POST for form-data/file upload
    Route::put('/lessons/{slug}', [LessonController::class, 'update']); // Use PUT for JSON body only
    Route::delete('/lessons/{slug}', [LessonController::class, 'destroy']);
    
    // Resume endpoint - add or update resume text
    Route::put('/lessons/{slug}/resume', [LessonController::class, 'updateResume']);
    
    // File upload endpoint - upload or update lesson file (any format)
    Route::post('/lessons/{slug}/upload-file', [LessonController::class, 'uploadFile']);
    });

    Route::post('/lessons/{lesson}/complete', [UserProgressController::class, 'markCompleted']);
    Route::get('/user/progress', [UserProgressController::class, 'myProgress']);
});
