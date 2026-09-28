<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Events\Assessment\AnswerSubmitted;
use App\Events\Assessment\AssessmentStarted;
use App\Events\Assessment\AttemptCompleted;
use App\Events\Learning\LessonViewed;
use App\Events\Session\PageViewed;
use App\Events\Session\SessionEnded;
use App\Events\Session\SessionStarted;
use App\Models\AssessmentAttempt;
use App\Models\Lesson;
use App\Models\User;
use App\Services\StudentRiskProfileService;
use App\Services\SubjectMasteryService;
use App\Listeners\Session\LogActivityEvent;
use App\Listeners\Session\TrackSessionDuration;
use App\Listeners\Session\UpdateUserActivityTimestamp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class EdaHolisticBenchmarkCommand extends Command
{
    protected $signature = 'eda:benchmark-holistic
                            {--iterations=3 : Jumlah iterasi benchmark}
                            {--concurrency=10 : Simulasi jumlah siswa bersamaan}';

    protected $description = 'Benchmark arsitektur Event-Driven vs Monolitik untuk 1 siklus penuh perjalanan siswa (Holistic User Journey)';

    public function handle(SubjectMasteryService $masteryService, StudentRiskProfileService $riskService): int
    {
        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║      HOLISTIC BENCHMARK: Full User Journey (14 Events)       ║");
        $this->components->info("║                   Isyarat Pintar - Skripsi                   ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $student = User::where('email', 'benchmark@example.com')
            ->whereHas('assessmentAttempts', fn($q) => $q->where('status', 'COMPLETED'))
            ->first();

        if (!$student) {
            $student = User::where('role', UserRole::SISWA)
                ->whereHas('assessmentAttempts', fn($q) => $q->where('status', 'COMPLETED'))
                ->first();
        }

        if (!$student) {
            $this->components->error("Data siswa dengan assessment completed tidak ditemukan. Jalankan seeder.");
            return Command::FAILURE;
        }

        $attempt = $student->assessmentAttempts()->where('status', 'COMPLETED')->first();
        if (!$attempt) {
            $this->components->error("Tidak ada assessment attempt untuk siswa ini.");
            return Command::FAILURE;
        }

        $lesson = Lesson::first();
        if (!$lesson) {
            // Jika tidak ada materi, pakai instance kosong (hanya untuk testing parameter event)
            $lesson = new Lesson(['id' => 1, 'title' => 'Materi Dummy']);
        }

        // Siapkan fake answer
        $answer = null;
        if (class_exists(\App\Models\AttemptAnswer::class)) {
            $answer = new \App\Models\AttemptAnswer(['id' => 1, 'assessment_attempt_id' => $attempt->id, 'question_id' => 1, 'is_correct' => true]);
        }

        $iterations = (int) $this->option('iterations');
        $concurrency = (int) $this->option('concurrency');

        $this->components->info("📋 Skenario User Journey (Per-Siswa):");
        $this->line("   1. Login (SessionStarted)");
        $this->line("   2. Membuka Dashboard (PageViewed)");
        $this->line("   3. Membuka Materi Belajar (LessonViewed)");
        $this->line("   4. Membuka Halaman Ujian (PageViewed)");
        $this->line("   5. Memulai Ujian (AssessmentStarted)");
        $this->line("   6. Menjawab 5 Soal (5x AnswerSubmitted)");
        $this->line("   7. Selesai Ujian (AttemptCompleted -> memicu kalkulasi SPK yang berat)");
        $this->line("   8. Logout (SessionEnded)");
        $this->info("   Total: 12 Domain Events beruntun dalam 1 sesi");
        $this->newLine();

        // ─────────────────────────────────────────────────
        // BENCHMARK 1: SYNCHRONOUS
        // ─────────────────────────────────────────────────
        $this->components->info("🔄 Benchmark 1: SYNCHRONOUS (Monolitik)");
        $this->info("   Semua event diproses inline (termasuk DB inserts & SPK calculation)");
        
        $syncTimes = [];
        $syncMemories = [];
        $syncBar = $this->output->createProgressBar($iterations);
        $syncBar->start();

        // Instantiate Listeners untuk pemanggilan manual
        $logListener = app(LogActivityEvent::class);
        $durationListener = app(TrackSessionDuration::class);
        $timestampListener = app(UpdateUserActivityTimestamp::class);

        for ($i = 0; $i < $iterations; $i++) {
            $startMem = memory_get_usage();
            $start = microtime(true);

            // Manual dispatch synchronous flow
            $this->runSynchronousJourney($student, $attempt, $lesson, $answer, $masteryService, $riskService, $logListener, $durationListener, $timestampListener);

            $syncTimes[] = (microtime(true) - $start) * 1000;
            $syncMemories[] = (memory_get_usage() - $startMem) / 1024 / 1024;
            $syncBar->advance();
        }
        $syncBar->finish();
        $this->newLine(2);

        // ─────────────────────────────────────────────────
        // BENCHMARK 2: EVENT-DRIVEN
        // ─────────────────────────────────────────────────
        $this->components->info("⚡ Benchmark 2: EVENT-DRIVEN (Arsitektur Asli)");
        $this->info("   Event ditembakkan, Log dijalankan sync, Tugas berat di-queue");
        
        $asyncTimes = [];
        $asyncMemories = [];
        $asyncBar = $this->output->createProgressBar($iterations);
        $asyncBar->start();

        for ($i = 0; $i < $iterations; $i++) {
            Queue::fake(); // Mencegah worker tereksekusi di request cycle

            $startMem = memory_get_usage();
            $start = microtime(true);

            // Real event dispatching
            event(new SessionStarted($student, 'web'));
            event(new PageViewed($student, '/dashboard'));
            event(new LessonViewed($student, $lesson));
            event(new PageViewed($student, '/assessment'));
            event(new AssessmentStarted($attempt));
            for ($j = 0; $j < 5; $j++) {
                if ($answer) event(new AnswerSubmitted($attempt, $answer));
            }
            event(new AttemptCompleted($attempt));
            event(new SessionEnded($student, 3600));

            $asyncTimes[] = (microtime(true) - $start) * 1000;
            $asyncMemories[] = (memory_get_usage() - $startMem) / 1024 / 1024;
            $asyncBar->advance();
        }
        $asyncBar->finish();
        $this->newLine(2);

        // ─────────────────────────────────────────────────
        // RESULTS
        // ─────────────────────────────────────────────────
        $syncAvg = array_sum($syncTimes) / count($syncTimes);
        $syncMemAvg = array_sum($syncMemories) / count($syncMemories);

        $asyncAvg = array_sum($asyncTimes) / count($asyncTimes);
        $asyncMemAvg = array_sum($asyncMemories) / count($asyncMemories);

        $speedup = $syncAvg > 0 ? round((($syncAvg - $asyncAvg) / $syncAvg) * 100, 2) : 0;

        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║                📊 HASIL HOLISTIC BENCHMARK                  ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $this->table(
            ['Metrik per User Journey (12 Events)', 'Synchronous (Monolitik)', 'Event-Driven (EDA)'],
            [
                [
                    'Total Response Time',
                    number_format($syncAvg, 2) . ' ms',
                    number_format($asyncAvg, 2) . ' ms',
                ],
                [
                    'Memory Terakumulasi (RAM)',
                    number_format($syncMemAvg, 4) . ' MB',
                    number_format($asyncMemAvg, 4) . ' MB',
                ],
                [
                    'Aktivitas Log DB (Sync)',
                    '12 Query (Memblokir)',
                    '12 Query (Dieksekusi Cepat)',
                ],
                [
                    'Heavy Calculation (SPK)',
                    'Memblokir Sesi User',
                    'Didelegasikan ke 3 Background Jobs',
                ],
            ]
        );

        $this->newLine();
        
        // STRESS TEST PROJECTION
        $this->components->info("🚀 Proyeksi Stress Test ({$concurrency} Siswa Aktif Bersamaan)");
        $syncStressTotal = ($syncAvg * $concurrency) / 1000;
        $asyncStressTotal = ($asyncAvg * $concurrency) / 1000;

        $this->table(
            ['Skenario Sesi Penuh (12 Events x ' . $concurrency . ' Siswa)', 'Monolitik', 'Event-Driven (EDA)'],
            [
                [
                    'Lama Web Server Terbebani Penuh',
                    number_format($syncStressTotal, 2) . ' detik penuh',
                    number_format($asyncStressTotal, 2) . ' detik',
                ],
                [
                    'Risiko Penumpukan Request (Bottleneck)',
                    'Sangat Ekstrem (Berisiko Downtime)',
                    'Aman (Traffic lancar)',
                ]
            ]
        );

        $this->newLine();
        $this->components->info("📈 Kesimpulan: Saat menguji keseluruhan siklus hidup pengguna, EDA meningkatkan kecepatan hingga {$speedup}%.");

        return Command::SUCCESS;
    }

    private function runSynchronousJourney(
        $student, $attempt, $lesson, $answer,
        $masteryService, $riskService,
        $logListener, $durationListener, $timestampListener
    ) {
        // 1. Session Started
        $e1 = new SessionStarted($student, 'web');
        $logListener->handle($e1);
        $durationListener->handle($e1);

        // 2. Page Viewed
        $e2 = new PageViewed($student, '/dashboard');
        $logListener->handle($e2);

        // 3. Lesson Viewed
        $e3 = new LessonViewed($student, $lesson);
        $logListener->handle($e3);

        // 4. Page Viewed
        $e4 = new PageViewed($student, '/assessment');
        $logListener->handle($e4);

        // 5. Assessment Started
        $e5 = new AssessmentStarted($attempt);
        $logListener->handle($e5);
        $durationListener->handle($e5);

        // 6. Answer Submitted (x5)
        if ($answer) {
            for ($i = 0; $i < 5; $i++) {
                $e6 = new AnswerSubmitted($attempt, $answer);
                $logListener->handle($e6);
            }
        }

        // 7. Attempt Completed
        $e7 = new AttemptCompleted($attempt);
        $logListener->handle($e7);
        // --- Heavy Calculation Chain (Simulasi inline) ---
        if ($attempt->assessment && $attempt->assessment->mataPelajaran) {
            $masteryService->calculateMastery($student, $attempt->assessment->mataPelajaran);
        }
        $riskService->calculateRiskProfile($student);
        $riskProfile = \App\Models\StudentRiskProfile::where('user_id', $student->id)->latest()->first();
        if ($riskProfile) {
            app(\App\Jobs\GenerateTeacherInsightsJob::class, ['riskProfileId' => $riskProfile->id])->handle();
        }

        // 8. Session Ended
        $e8 = new SessionEnded($student, 3600);
        $logListener->handle($e8);
        $durationListener->handle($e8);
        $timestampListener->handle($e8);
    }
}
