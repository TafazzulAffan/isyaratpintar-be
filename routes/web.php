<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// API Documentation Routes
Route::prefix('swagger/docsapi')->group(function () {
    Route::get('/', [DocumentationController::class, 'swagger'])->name('docs.swagger');
    Route::get('/api-docs.json', [DocumentationController::class, 'openapi'])->name('api.openapi');
});

// ✅ FIX PDF ROUTE
Route::get('/pdf/{file}', function ($file) {

    // 🔒 VALIDASI FILE (WAJIB)
    if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $file)) {
        abort(400, 'Invalid file name');
    }

    $path = storage_path('app/public/lessons/pdfs/' . $file);

    if (!file_exists($path)) {
        abort(404, 'File not found');
    }

    return response()->file($path, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="'.$file.'"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',

        // 🔥 CORS (penting untuk Next.js fetch)
        'Access-Control-Allow-Origin' => 'http://localhost:3000',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]);
});

// ✅ HANDLE PREFLIGHT (OPTIONAL)
Route::options('/pdf/{file}', function () {
    return response('', 200, [
        'Access-Control-Allow-Origin' => 'http://localhost:3000',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]);
});
