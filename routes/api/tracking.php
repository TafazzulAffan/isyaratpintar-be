<?php

use App\Http\Controllers\Api\User\ActivityTrackingController;
use App\Http\Controllers\Api\User\StudentAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum', 'role:siswa')->group(function () {
    // Activity Tracking Endpoints
    Route::prefix('tracking')->group(function () {
        Route::post('session/start', [ActivityTrackingController::class, 'startSession']);
        Route::post('session/end', [ActivityTrackingController::class, 'endSession']);
        Route::post('page-viewed', [ActivityTrackingController::class, 'trackPageView']);
    });

    // Analytics Endpoints
    Route::prefix('analytics')->group(function () {
        Route::get('activity-log', [StudentAnalyticsController::class, 'activityLog']);
        Route::get('learning-stats', [StudentAnalyticsController::class, 'learningStats']);
        Route::get('session-history', [StudentAnalyticsController::class, 'sessionHistory']);
        Route::get('daily-learning-breakdown', [StudentAnalyticsController::class, 'dailyLearningBreakdown']);
    });

    // Alternative shorter routes for student dashboard
    Route::get('me/activity-log', [StudentAnalyticsController::class, 'activityLog']);
    Route::get('me/learning-stats', [StudentAnalyticsController::class, 'learningStats']);
    Route::get('me/session-history', [StudentAnalyticsController::class, 'sessionHistory']);
});
