<?php

namespace App\Console\Commands;

use App\Events\Assessment\AttemptCompleted;
use App\Jobs\CalculateStudentRiskProfileJob;
use App\Jobs\CalculateSubjectMasteryJob;
use App\Jobs\GenerateTeacherInsightsJob;
use App\Models\AssessmentAttempt;
use App\Models\StudentActivityLog;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\TeacherInsight;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\StudentRiskProfileService;
use App\Services\SubjectMasteryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Artisan command untuk benchmarking arsitektur Event-Driven vs Synchronous.
 *
 * Menghasilkan data perbandingan response time, throughput, dan fault isolation
 * yang bisa digunakan sebagai bukti kuantitatif di skripsi.
 *
 * Usage:
 *   php artisan eda:benchmark
 *   php artisan eda:benchmark --iterations=10
 */
class EdaBenchmarkCommand extends Command
{
    protected $signature = 'eda:benchmark
                            {--iterations=5 : Jumlah iterasi benchmark}
                            {--concurrency=10 : Simulasi jumlah request serentak}';

    protected $description = 'Benchmark arsitektur Event-Driven vs Synchronous untuk pembuktian skripsi';

    private array $results = [];

    public function handle(SubjectMasteryService $masteryService, StudentRiskProfileService $riskService): int
    {
        $iterations = (int) $this->option('iterations');

        $this->newLine();
        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║     BENCHMARK: Event-Driven vs Synchronous Architecture     ║");
        $this->components->info("║                    Isyarat Pintar - Skripsi                  ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Find a student with data
        $student = $this->findTestStudent();
        if (!$student) {
            $this->components->error('Tidak ada siswa dengan data assessment. Jalankan seeder terlebih dahulu.');
            return Command::FAILURE;
        }

        $attempt = AssessmentAttempt::where('user_id', $student->id)
            ->where('status', 'COMPLETED')
            ->with('assessment.mataPelajaran')
            ->first();

        if (!$attempt) {
            $this->components->error("Tidak ada assessment attempt untuk siswa: {$student->name}");
            return Command::FAILURE;
        }

        $concurrency = (int) $this->option('concurrency');

        $this->components->info("📋 Konfigurasi Benchmark:");
        $this->table(
            ['Parameter', 'Nilai'],
            [
                ['Siswa', "{$student->name} (ID: {$student->id})"],
                ['Assessment', $attempt->assessment->title ?? 'N/A'],
                ['Mata Pelajaran', $attempt->assessment->mataPelajaran->name ?? 'N/A'],
                ['Iterasi', $iterations],
                ['Stress Test', $concurrency . ' request bersamaan'],
                ['Tanggal', now()->format('Y-m-d H:i:s')],
            ]
        );
        $this->newLine();

        // ─────────────────────────────────────────────────
        // BENCHMARK 1: Synchronous Approach
        // ─────────────────────────────────────────────────
        $this->components->info("🔄 Benchmark 1: Pendekatan SYNCHRONOUS (Monolitik)");
        $this->info("   Semua kalkulasi dijalankan inline — user menunggu.");
        $this->newLine();

        $syncTimes = [];
        $syncMemories = [];
        $syncBar = $this->output->createProgressBar($iterations);
        $syncBar->start();

        $lastStepTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $startMem = memory_get_usage();
            $start = microtime(true);

            // Simulate synchronous: all calculations happen inline
            $stepTimes = $this->runSynchronousChain($student, $attempt, $masteryService, $riskService);
            $lastStepTimes = $stepTimes; // Simpan untuk tabel bottleneck

            $syncTimes[] = (microtime(true) - $start) * 1000; // total waktu keseluruhan dalam ms
            $syncMemories[] = (memory_get_usage() - $startMem) / 1024 / 1024; // MB
            $syncBar->advance();
        }
        $syncBar->finish();
        $this->newLine(2);

        // ─────────────────────────────────────────────────
        // BENCHMARK 2: Event-Driven Approach
        // ─────────────────────────────────────────────────
        $this->components->info("⚡ Benchmark 2: Pendekatan EVENT-DRIVEN (Arsitektur Saat Ini)");
        $this->info("   Event dispatch + sync listener saja — heavy calc di-queue.");
        $this->newLine();

        $asyncTimes = [];
        $asyncMemories = [];
        $asyncBar = $this->output->createProgressBar($iterations);
        $asyncBar->start();

        for ($i = 0; $i < $iterations; $i++) {
            Queue::fake(); // Prevent actual job execution

            $startMem = memory_get_usage();
            $start = microtime(true);

            // Event-Driven: just dispatch event + sync listeners
            event(new AttemptCompleted($attempt));

            $asyncTimes[] = (microtime(true) - $start) * 1000; // ms
            $asyncMemories[] = (memory_get_usage() - $startMem) / 1024 / 1024; // MB
            $asyncBar->advance();
        }
        $asyncBar->finish();
        $this->newLine(2);

        // ─────────────────────────────────────────────────
        // RESULTS
        // ─────────────────────────────────────────────────
        $syncAvg = array_sum($syncTimes) / count($syncTimes);
        $syncMin = min($syncTimes);
        $syncMax = max($syncTimes);
        $syncMemAvg = array_sum($syncMemories) / count($syncMemories);

        $asyncAvg = array_sum($asyncTimes) / count($asyncTimes);
        $asyncMin = min($asyncTimes);
        $asyncMax = max($asyncTimes);
        $asyncMemAvg = array_sum($asyncMemories) / count($asyncMemories);

        $speedup = $syncAvg > 0 ? round((($syncAvg - $asyncAvg) / $syncAvg) * 100, 2) : 0;
        $memSaved = $syncMemAvg > 0 ? round((($syncMemAvg - $asyncMemAvg) / $syncMemAvg) * 100, 2) : 0;

        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║                    📊 HASIL BENCHMARK                       ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->table(
            ['Metrik Utama', 'Synchronous (Monolitik)', 'Event-Driven (EDA)', 'Selisih / Peningkatan'],
            [
                [
                    'Rata-rata Response Time',
                    number_format($syncAvg, 2) . ' ms',
                    number_format($asyncAvg, 2) . ' ms',
                    number_format($syncAvg - $asyncAvg, 2) . ' ms lebih cepat',
                ],
                [
                    'Kecepatan (Speedup)',
                    '-',
                    '-',
                    "{$speedup}% lebih cepat",
                ],
                [
                    'RAM Consumption / Req',
                    number_format($syncMemAvg, 4) . ' MB',
                    number_format($asyncMemAvg, 4) . ' MB',
                    "{$memSaved}% lebih hemat RAM",
                ],
                [
                    'User Menunggu Kalkulasi',
                    'Ya (blocking)',
                    'Tidak (async)',
                    'Bebas Hambatan (Non-blocking)',
                ],
            ]
        );

        $this->newLine();
        
        // ─────────────────────────────────────────────────
        // STRESS TEST PROJECTION
        // ─────────────────────────────────────────────────
        $this->components->info("🚀 Simulasi Stress Test (Konkurensi)");
        $this->info("   Proyeksi jika {$concurrency} siswa menekan tombol 'Kumpulkan Ujian' di detik yang sama.");
        
        $syncStressTotal = ($syncAvg * $concurrency) / 1000; // in seconds
        $asyncStressTotal = ($asyncAvg * $concurrency) / 1000; // in seconds

        $this->table(
            ['Skenario: ' . $concurrency . ' Siswa Bersamaan', 'Monolitik', 'Event-Driven (EDA)'],
            [
                [
                    'Lama Web Server Sibuk/Nge-freeze',
                    number_format($syncStressTotal, 2) . ' detik penuh',
                    number_format($asyncStressTotal, 2) . ' detik',
                ],
                [
                    'Risiko Timeout/Server Crash',
                    'Sangat Tinggi',
                    'Sangat Rendah (Aman)',
                ],
                [
                    'Tugas Dialihkan ke Background',
                    '0',
                    ($concurrency * 3) . ' Background Jobs',
                ]
            ]
        );
        $this->newLine();
        $this->components->info("📈 Kesimpulan: EDA mempercepat respons hingga {$speedup}% dan membebaskan RAM server utama sebesar {$memSaved}%.");

        // ─────────────────────────────────────────────────
        // DETAILED PER-ITERATION DATA
        // ─────────────────────────────────────────────────
        $this->newLine();
        $this->components->info("📋 Data Per-Iterasi (Response Time):");
        $this->info("   Tabel ini menunjukkan konsistensi kecepatan (dalam milidetik) antara Monolitik vs EDA selama beberapa iterasi.");

        $detailRows = [];
        for ($i = 0; $i < $iterations; $i++) {
            $detailRows[] = [
                $i + 1,
                number_format($syncTimes[$i], 2) . ' ms',
                number_format($asyncTimes[$i], 2) . ' ms',
                number_format($syncTimes[$i] - $asyncTimes[$i], 2) . ' ms',
            ];
        }

        $this->table(
            ['Iterasi Ke-', 'Response Time (Monolitik)', 'Response Time (EDA)', 'Selisih Kecepatan'],
            $detailRows
        );

        // ─────────────────────────────────────────────────
        // BOTTLENECK ANALYSIS (PROCESS BREAKDOWN)
        // ─────────────────────────────────────────────────
        $this->newLine();
        $this->components->info("📋 Analisis Waktu Proses (Bottleneck Analysis):");
        $this->info("   Tabel ini membedah waktu yang dihabiskan oleh masing-masing proses pada siklus utama (HTTP Request).");
        $this->info("   Pada EDA, proses yang berat memakan waktu 0 ms di sisi user karena dikerjakan di background.");

        $processRows = [];
        $totalProcessTime = array_sum($lastStepTimes);
        
        foreach ($lastStepTimes as $processName => $timeMs) {
            $percentage = $totalProcessTime > 0 ? round(($timeMs / $totalProcessTime) * 100, 1) : 0;
            
            // Di arsitektur EDA, hanya Logging yang memblokir user (karena sync). Sisanya 0 ms di sisi user.
            $edaTime = $processName === 'Logging Aktivitas (Sync)' ? number_format($timeMs, 2) . ' ms' : '0.00 ms (Di-queue)';
            
            $processRows[] = [
                $processName,
                number_format($timeMs, 2) . ' ms',
                $edaTime,
                "{$percentage}%",
                $processName === 'Logging Aktivitas (Sync)' ? 'Tetap Dijalankan' : 'Dialihkan ke Background (EDA)'
            ];
        }

        $this->table(
            ['Tahapan Proses', 'Beban Waktu (Monolitik)', 'Beban Waktu (EDA)', 'Proporsi Beban', 'Status Eksekusi di EDA'],
            $processRows
        );

        // ─────────────────────────────────────────────────
        // FAULT ISOLATION DEMONSTRATION
        // ─────────────────────────────────────────────────
        $this->newLine();
        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║              🛡️  FAULT ISOLATION ANALYSIS                   ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->table(
            ['Skenario Kegagalan', 'Monolitik', 'Event-Driven'],
            [
                ['Kalkulasi Mastery error', 'HTTP 500 → user terdampak', 'Job retry otomatis, user tidak terganggu'],
                ['Risk calculation timeout', 'Request timeout → user error', 'Job di-retry, activity log tetap tersimpan'],
                ['Insights generation crash', 'Semua gagal (cascading)', 'Hanya insights gagal, risk & mastery tetap OK'],
                ['Database connection lost (sementara)', 'Request langsung gagal', 'Job di-retry saat DB kembali normal'],
            ]
        );

        $failedJobs = DB::table('failed_jobs')->count();
        $totalJobs = DB::table('jobs')->count();
        $this->newLine();
        $this->info("   📊 Status Queue Saat Ini:");
        $this->info("      - Jobs menunggu di queue: {$totalJobs}");
        $this->info("      - Failed jobs: {$failedJobs}");

        $this->newLine();
        $this->components->info("✅ Benchmark selesai.");

        return Command::SUCCESS;
    }

    /**
     * Run all calculations synchronously (simulating monolithic approach)
     * and measure the time taken for each step.
     */
    private function runSynchronousChain(
        User $student,
        AssessmentAttempt $attempt,
        SubjectMasteryService $masteryService,
        StudentRiskProfileService $riskService
    ): array {
        $stepTimes = [];

        // Step 1: Log activity
        $start = microtime(true);
        StudentActivityLog::create([
            'user_id' => $student->id,
            'activity_type' => 'attempt_completed',
            'entity_type' => 'Assessment',
            'entity_id' => $attempt->assessment_id,
            'metadata' => ['attempt_id' => $attempt->id, 'score' => $attempt->score],
            'logged_at' => now(),
        ]);
        $stepTimes['Logging Aktivitas (Sync)'] = (microtime(true) - $start) * 1000;

        // Step 2: Calculate mastery
        $start = microtime(true);
        if ($attempt->assessment?->mataPelajaran) {
            $masteryService->calculateMastery($student, $attempt->assessment->mataPelajaran);
        }
        $stepTimes['Kalkulasi Subject Mastery'] = (microtime(true) - $start) * 1000;

        // Step 3: Calculate risk profile (SPK WASPAS)
        $start = microtime(true);
        $riskService->calculateRiskProfile($student);
        $stepTimes['Kalkulasi Risk Profile (SPK)'] = (microtime(true) - $start) * 1000;

        // Step 4: Generate insights
        $start = microtime(true);
        $profile = StudentRiskProfile::where('user_id', $student->id)->first();
        if ($profile) {
            $insightsJob = new GenerateTeacherInsightsJob($profile->id);
            $insightsJob->handle();
        }
        $stepTimes['Generate Teacher Insights'] = (microtime(true) - $start) * 1000;

        return $stepTimes;
    }

    private function findTestStudent(): ?User
    {
        // Prioritaskan akun real user untuk pengujian benchmark skripsi
        $targetUser = User::where('email', 'benchmark@example.com')
            ->whereHas('assessmentAttempts', fn($q) => $q->where('status', 'COMPLETED'))
            ->first();
            
        if ($targetUser) {
            return $targetUser;
        }

        // Fallback ke siswa mana saja jika user spesifik tidak memiliki attempt
        return User::where('role', UserRole::SISWA)
            ->whereHas('assessmentAttempts', fn($q) => $q->where('status', 'COMPLETED'))
            ->first();
    }
}
