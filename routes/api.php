<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API routes (non-versioned)
require __DIR__ . '/api/auth.php';
require __DIR__ . '/api/learning.php';
require __DIR__ . '/api/file.php';
require __DIR__ . '/api/users.php';
require __DIR__ . '/api/pbl.php';
require __DIR__ . '/api/assessments.php';
require __DIR__ . '/api/tracking.php';
require __DIR__ . '/api/spk.php';
require __DIR__ . '/api/kelas.php';