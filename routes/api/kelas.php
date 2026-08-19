<?php

use App\Http\Controllers\KelasController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::post('/kelas', [KelasController::class, 'store'])->middleware('role:admin,guru');
    Route::get('/kelas/{kelas}', [KelasController::class, 'show']);
    Route::put('/kelas/{kelas}', [KelasController::class, 'update'])->middleware('role:admin,guru');
    Route::delete('/kelas/{kelas}', [KelasController::class, 'destroy'])->middleware('role:admin,guru');

    Route::post('/kelas/{kelas}/siswa', [KelasController::class, 'addStudent'])->middleware('role:admin,guru');
    Route::delete('/kelas/{kelas}/siswa/{user}', [KelasController::class, 'removeStudent'])->middleware('role:admin,guru');

    Route::get('/kelas/{kelas}/materi', [KelasController::class, 'materi']);
    Route::get('/kelas/{kelas}/tugas', [KelasController::class, 'tugas']);
    Route::get('/kelas/{kelas}/ujian', [KelasController::class, 'ujian']);
});
