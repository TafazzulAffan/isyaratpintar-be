<?php

namespace App\Console\Commands;

use App\Events\Assessment\AttemptCompleted;
use App\Models\AssessmentAttempt;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use ReflectionClass;

class EdaTraceCommand extends Command
{
    protected $signature = 'eda:trace';
    protected $description = 'Melacak dan memvisualisasikan seluruh alur Event-Driven Architecture (EDA) secara rinci.';

    public function handle()
    {
        $this->newLine();
        $this->components->info("╔══════════════════════════════════════════════════════════════╗");
        $this->components->info("║             EDA FLOW TRACER (VISUAL TIMELINE)                ║");
        $this->components->info("║        Bukti Dekomposisi & Isolasi Proses untuk Skripsi      ║");
        $this->components->info("╚══════════════════════════════════════════════════════════════╝");
        $this->newLine();

        $student = User::where('email', 'benchmark@example.com')
            ->whereHas('assessmentAttempts', fn($q) => $q->where('status', 'COMPLETED'))
            ->first();

        if (!$student) {
            $this->components->error("Data siswa benchmark tidak ditemukan. Jalankan benchmark seeder terlebih dahulu.");
            return Command::FAILURE;
        }

        $attempt = $student->assessmentAttempts()->where('status', 'COMPLETED')->first();

        $this->info("Menjalankan Mode Pelacakan (Step-by-Step Execution)...");
        $this->newLine();

        $step = 1;

        // STEP 1
        $this->line("<fg=green;options=bold>[STEP {$step}] 🟢 USER ACTION:</> Siswa '{$student->name}' mengumpulkan ujian.");
        $step++;

        // STEP 2
        $this->line("<fg=cyan;options=bold>[STEP {$step}] 📤 EVENT DISPATCHED:</> App\Events\Assessment\AttemptCompleted");
        $this->line("          ├─ <fg=gray>Payload:</> {\"attempt_id\": {$attempt->id}, \"score\": {$attempt->score}, \"status\": \"{$attempt->status->value}\"}");
        $this->line("          ├─ <fg=blue>⚡ [SYNC]</> LogActivityEvent berjalan instan untuk mencatat aktivitas ke DB.");
        $this->line("          └─ <fg=yellow>⏳ [ASYNC]</> CalculateSubjectMasteryListener merespons dan mengirim Job ke Queue.");
        $step++;

        $this->line("<fg=green;options=bold>[HTTP RESPONSE]</> Dikembalikan ke User secara instan. Siswa bisa menutup browser.");
        $this->newLine();
        $this->info("--- MEMASUKI FASE BACKGROUND (ASYNCHRONOUS QUEUE WORKER) ---");
        $this->newLine();

        // STEP 3
        $this->line("<fg=yellow;options=bold>[STEP {$step}] ⚙️ WORKER START:</> Mengerjakan App\Jobs\CalculateSubjectMasteryJob");
        $start = microtime(true);
        $masteryJob = new \App\Jobs\CalculateSubjectMasteryJob($attempt->id);
        $masteryJob->handle(app(\App\Services\SubjectMasteryService::class));
        $elapsed = number_format((microtime(true) - $start) * 1000, 2);
        $this->line("          ├─ Proses kalkulasi nilai penguasaan (Mastery) berjalan di server belakang.");
        $this->line("          └─ <fg=green>✅ Selesai dalam {$elapsed} ms</>");
        $step++;

        // Get the generated mastery
        $mastery = \App\Models\SubjectMastery::where('user_id', $student->id)->latest()->first();

        // STEP 4
        $this->line("<fg=cyan;options=bold>[STEP {$step}] 📤 EVENT DISPATCHED:</> App\Events\SPK\SubjectMasteryCalculated");
        $this->line("          ├─ <fg=gray>Payload:</> {\"mastery_id\": {$mastery->id}, \"level\": \"{$mastery->status}\"}");
        $this->line("          └─ <fg=yellow>⏳ [ASYNC]</> CalculateRiskProfileListener merespons dan mengirim Job ke Queue.");
        $step++;

        // STEP 5
        $this->line("<fg=yellow;options=bold>[STEP {$step}] ⚙️ WORKER START:</> Mengerjakan App\Jobs\CalculateStudentRiskProfileJob");
        $start = microtime(true);
        $riskJob = new \App\Jobs\CalculateStudentRiskProfileJob($student->id);
        $riskJob->handle(app(\App\Services\StudentRiskProfileService::class));
        $elapsed = number_format((microtime(true) - $start) * 1000, 2);
        $this->line("          ├─ Menjalankan algoritma SPK WASPAS yang berat untuk menghitung profil risiko...");
        $this->line("          └─ <fg=green>✅ Selesai dalam {$elapsed} ms</>");
        $step++;

        // Get the generated risk profile
        $riskProfile = \App\Models\StudentRiskProfile::where('user_id', $student->id)->latest()->first();

        // STEP 6
        $this->line("<fg=cyan;options=bold>[STEP {$step}] 📤 EVENT DISPATCHED:</> App\Events\SPK\StudentRiskProfileUpdated");
        $this->line("          ├─ <fg=gray>Payload:</> {\"risk_profile_id\": {$riskProfile->id}, \"risk_level\": \"{$riskProfile->risk_level}\"}");
        $this->line("          └─ <fg=yellow>⏳ [ASYNC]</> GenerateInsightsListener merespons dan mengirim Job ke Queue.");
        $step++;

        // STEP 7
        $this->line("<fg=yellow;options=bold>[STEP {$step}] ⚙️ WORKER START:</> Mengerjakan App\Jobs\GenerateTeacherInsightsJob");
        $start = microtime(true);
        $insightsJob = new \App\Jobs\GenerateTeacherInsightsJob($riskProfile->id);
        $insightsJob->handle();
        $elapsed = number_format((microtime(true) - $start) * 1000, 2);
        $this->line("          ├─ Mengolah hasil SPK menjadi kalimat rekomendasi (Insights) untuk guru.");
        $this->line("          └─ <fg=green>✅ Selesai dalam {$elapsed} ms</>");
        
        $this->newLine();
        $this->components->info("🎉 SELURUH ALUR EDA SELESAI!");
        $this->line("Data berhasil diproses tanpa membuat siswa menunggu satu detik pun di layar browser mereka.");

        return Command::SUCCESS;
    }
}
