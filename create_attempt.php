<?php
use Illuminate\Support\Facades\DB;
use App\Models\User;

$student = User::where('email', 'benchmark@example.com')->first();
if (!$student) {
    echo "Student not found!\n";
    exit;
}

$subjectId = DB::table('mata_pelajarans')->insertGetId([
    'name' => 'Math Test Benchmark',
    'created_at' => now(),
    'updated_at' => now(),
]);

$assessmentId = DB::table('assessments')->insertGetId([
    'title' => 'Test Assessment Benchmark',
    'slug' => 'test-assessment-benchmark-' . uniqid(),
    'time_limit' => 30,
    'mata_pelajaran_id' => $subjectId,
    'created_at' => now(),
    'updated_at' => now(),
]);

DB::table('assessment_attempts')->insert([
    'user_id' => $student->id,
    'assessment_id' => $assessmentId,
    'status' => 'COMPLETED',
    'score' => 85,
    'level' => 'Advanced',
    'started_at' => now()->subMinutes(30),
    'completed_at' => now(),
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Attempt created successfully!\n";
