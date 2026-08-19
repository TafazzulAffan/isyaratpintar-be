<?php

use App\Http\Controllers\Api\Admin\SPKController as AdminSPKController;
use App\Http\Controllers\Api\User\SPKController as UserSPKController;
use Illuminate\Support\Facades\Route;

// Admin SPK Routes
Route::middleware('auth:sanctum', 'role:admin,guru')->prefix('admin/spk')->group(function () {
    Route::get('at-risk-students', [AdminSPKController::class, 'atRiskStudents']);
    Route::get('subject-performance', [AdminSPKController::class, 'subjectPerformance']);
    Route::get('teacher-insights', [AdminSPKController::class, 'teacherInsights']);
    Route::get('student/{studentId}/risk-profile', [AdminSPKController::class, 'studentRiskProfile']);
});

// Student SPK Routes
Route::middleware('auth:sanctum', 'role:siswa')->prefix('spk')->group(function () {
    Route::get('subject-mastery', [UserSPKController::class, 'subjectMastery']);
    Route::get('risk-profile', [UserSPKController::class, 'riskProfile']);
    Route::get('recommendations', [UserSPKController::class, 'recommendations']);
    Route::get('strengths-and-weaknesses', [UserSPKController::class, 'strengthsAndWeaknesses']);
});

// Alternative shorter routes for student dashboard
Route::middleware('auth:sanctum', 'role:siswa')->group(function () {
    Route::get('me/spk/subject-mastery', [UserSPKController::class, 'subjectMastery']);
    Route::get('me/spk/risk-profile', [UserSPKController::class, 'riskProfile']);
    Route::get('me/spk/recommendations', [UserSPKController::class, 'recommendations']);
    Route::get('me/spk/strengths-and-weaknesses', [UserSPKController::class, 'strengthsAndWeaknesses']);
});
