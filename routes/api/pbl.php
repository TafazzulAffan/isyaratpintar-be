<?php

use App\Http\Controllers\Api\Admin\CaseSectionController;
use App\Http\Controllers\Api\Admin\CaseSectionItemController;
use App\Http\Controllers\Api\Admin\ImageUploadController;
use App\Http\Controllers\Api\Admin\PblCaseController;
use App\Http\Controllers\Api\User\CaseSubmissionController;
use App\Http\Controllers\Api\User\PblCaseUserController;
use Illuminate\Support\Facades\Route;

// All authenticated routes (authorization check in controller)
Route::middleware('auth:sanctum')->group(function () {
    // PBL Cases - All users can GET
    Route::get('/pbl-cases', [PblCaseUserController::class, 'index']);
    Route::get('/pbl-cases/mata-pelajaran/{id}', [PblCaseUserController::class, 'byMataPelajaran']);
    Route::get('/pbl-cases/{pblCase}', [PblCaseUserController::class, 'show']);
    
    // PBL Cases CRUD - Only admin/guru
    Route::post('/pbl-cases', [PblCaseController::class, 'store']);
    Route::put('/pbl-cases/{pblCase}', [PblCaseController::class, 'update']);
    Route::delete('/pbl-cases/{pblCase}', [PblCaseController::class, 'destroy']);

    // Case Sections - Admin/Guru only
    Route::post('/pbl-cases/{pblCase}/sections', [CaseSectionController::class, 'store']);
    Route::get('/pbl-cases/{pblCase}/sections', [CaseSectionController::class, 'index']);
    Route::put('/pbl-sections/{caseSection}', [CaseSectionController::class, 'update']);
    Route::delete('/pbl-sections/{caseSection}', [CaseSectionController::class, 'destroy']);

    // Case Section Items - Admin/Guru only
    Route::post('/pbl-sections/{caseSection}/items', [CaseSectionItemController::class, 'store']);
    Route::put('/pbl-items/{caseSectionItem}', [CaseSectionItemController::class, 'update']);
    Route::delete('/pbl-items/{caseSectionItem}', [CaseSectionItemController::class, 'destroy']);

    // Case Submissions - File upload
    Route::post('/pbl-submissions', [CaseSubmissionController::class, 'store']);
    Route::get('/pbl-submissions', [CaseSubmissionController::class, 'getUserSubmissions']);
    Route::get('/pbl-submissions/debug/list', [CaseSubmissionController::class, 'debugList']);
    Route::get('/pbl-submissions/me', [CaseSubmissionController::class, 'mySubmissions']);
    Route::put('/pbl-submissions/{caseSubmission}/grade', [CaseSubmissionController::class, 'grade']);

    // Image Upload - Admin/Guru only
    Route::post('/upload-image', [ImageUploadController::class, 'upload']);
});
