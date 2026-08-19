<?php

namespace App\Console\Commands;

use App\Services\PedagogicalActionService;
use App\Services\WaspasService;
use Illuminate\Console\Command;

class SpkWaspasTestCommand extends Command
{
    protected $signature = 'spk:test {--student= : Test specific student (ahmad,budi,citra,dewi,eko)} {--stage= : Test specific stage (1=risk, 2=pedagogical, all)}';

    protected $description = 'Menjalankan Simulasi SPK WASPAS dan Menampilkan Hasil Perhitungan Sistem';

    public function handle(WaspasService $waspasService, PedagogicalActionService $actionService): int
    {
        $this->newLine();
        $this->components->info('╔══════════════════════════════════════════════════════════════╗');
        $this->components->info('║    SIMULASI SISTEM PENDUKUNG KEPUTUSAN WASPAS                ║');
        $this->components->info('║    IsyaratPintar — Learning Management System                ║');
        $this->components->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $stage = $this->option('stage') ?? 'all';
        $studentFilter = $this->option('student');

        // ========================================
        // DATA UJI
        // ========================================
        $testStudents = $this->getTestStudents();

        if ($studentFilter) {
            $studentFilter = strtolower($studentFilter);
            if (!isset($testStudents[$studentFilter])) {
                $this->error("Siswa '{$studentFilter}' tidak ditemukan. Pilih: " . implode(', ', array_keys($testStudents)));
                return 1;
            }
            $testStudents = [$studentFilter => $testStudents[$studentFilter]];
        }

        // ========================================
        // KONFIGURASI DARI config/spk.php
        // ========================================
        $config = config('spk.waspas');
        $lambda = (float) $config['lambda'];
        $criteriaConfig = $config['criteria'];

        $weights = collect($criteriaConfig)->mapWithKeys(
            fn(array $c, string $k) => [$k => $c['weight']]
        )->all();

        $criteriaTypes = collect($criteriaConfig)->mapWithKeys(
            fn(array $c, string $k) => [$k => $c['type']]
        )->all();

        $benchmarks = collect($criteriaConfig)->mapWithKeys(
            fn(array $c, string $k) => [$k => $c['benchmark']]
        )->all();

        $this->displayConfig($weights, $benchmarks, $lambda);

        // ========================================
        // TAHAP 1: PROFIL RISIKO (WASPAS)
        // ========================================
        if ($stage === 'all' || $stage === '1') {
            $this->runStage1($waspasService, $testStudents, $weights, $criteriaTypes, $benchmarks, $lambda);
        }

        // ========================================
        // TAHAP 2: REKOMENDASI TINDAKAN PEDAGOGIS
        // ========================================
        if ($stage === 'all' || $stage === '2') {
            $this->runStage2($actionService, $testStudents);
        }

        $this->newLine();
        $this->components->info('🎉 SIMULASI SELESAI!');
        $this->newLine();

        return 0;
    }

    // ============================================================
    // DATA UJI
    // ============================================================

    private function getTestStudents(): array
    {
        return [
            'ahmad' => [
                'name' => 'Ahmad',
                'raw' => ['attendance' => 5, 'performance' => 72, 'engagement' => 420, 'subject_mastery' => 65],
                'profile' => 'Siswa rata-rata — engagement rendah',
            ],
            'budi' => [
                'name' => 'Budi',
                'raw' => ['attendance' => 2, 'performance' => 45, 'engagement' => 90, 'subject_mastery' => 35],
                'profile' => 'Semua aspek rendah',
            ],
            'citra' => [
                'name' => 'Citra',
                'raw' => ['attendance' => 7, 'performance' => 88, 'engagement' => 1200, 'subject_mastery' => 82],
                'profile' => 'Performa dan kehadiran baik',
            ],
            'dewi' => [
                'name' => 'Dewi',
                'raw' => ['attendance' => 3, 'performance' => 55, 'engagement' => 650, 'subject_mastery' => 48],
                'profile' => 'Hadir minim, engagement sedang',
            ],
            'eko' => [
                'name' => 'Eko',
                'raw' => ['attendance' => 6, 'performance' => 92, 'engagement' => 1500, 'subject_mastery' => 90],
                'profile' => 'Semua aspek tinggi',
            ],
        ];
    }


    // ============================================================
    // DISPLAY CONFIG
    // ============================================================

    private function displayConfig(array $weights, array $benchmarks, float $lambda): void
    {
        $this->components->twoColumnDetail('<fg=cyan;options=bold>Parameter</>', '<fg=cyan;options=bold>Nilai</>');
        $this->components->twoColumnDetail('Metode', 'WASPAS');
        $this->components->twoColumnDetail('Lambda (λ)', (string) $lambda);
        $this->components->twoColumnDetail('Jumlah Kriteria', (string) count($weights));
        $this->newLine();

        $this->info('  📋 Kriteria & Bobot:');
        $headers = ['Kriteria', 'Bobot', 'Tipe', 'Benchmark Max'];
        $rows = [];
        foreach (config('spk.waspas.criteria') as $key => $c) {
            $rows[] = [$key, $c['weight'], strtoupper($c['type']), $c['benchmark']['max']];
        }
        $this->table($headers, $rows);
        $this->newLine();
    }

    // ============================================================
    // TAHAP 1: PROFIL RISIKO
    // ============================================================

    private function runStage1(
        WaspasService $waspasService,
        array $testStudents,
        array $weights,
        array $criteriaTypes,
        array $benchmarks,
        float $lambda,
    ): void {
        $this->components->info('═══════════════════════════════════════════');
        $this->components->info('  TAHAP 1: PERHITUNGAN PROFIL RISIKO SISWA');
        $this->components->info('═══════════════════════════════════════════');
        $this->newLine();

        // Tampilkan data uji
        $this->info('  📊 Data Uji (Matriks Keputusan):');
        $headers = ['Siswa', 'Attendance', 'Performance', 'Engagement', 'Mastery'];
        $rows = [];
        foreach ($testStudents as $student) {
            $r = $student['raw'];
            $rows[] = [$student['name'], $r['attendance'], $r['performance'], $r['engagement'], $r['subject_mastery']];
        }
        $this->table($headers, $rows);

        // Jalankan WASPAS via service
        $alternatives = [];
        foreach ($testStudents as $key => $student) {
            $alternatives[$key] = $student['raw'];
        }

        $results = $waspasService->calculate(
            $alternatives,
            $weights,
            $criteriaTypes,
            $lambda,
            $benchmarks,
        );

        // ---- a. Hasil Normalisasi ----
        $this->newLine();
        $this->components->info('  🔢 a. Hasil Normalisasi Kriteria');
        $this->line('  ─────────────────────────────────────────');

        $normHeaders = ['Siswa', 'Kriteria', 'Nilai Ternormalisasi Sistem'];
        $normRows = [];

        foreach ($testStudents as $key => $student) {
            $systemNorm = $results[$key]['normalized'] ?? [];
            foreach (['attendance', 'performance', 'engagement', 'subject_mastery'] as $criterion) {
                $actual = round($systemNorm[$criterion] ?? 0, 4);

                $normRows[] = [
                    $student['name'],
                    $criterion,
                    number_format($actual, 4),
                ];
            }
        }
        $this->table($normHeaders, $normRows);

        // ---- b. Hasil WSM ----
        $this->newLine();
        $this->components->info('  📐 b. Hasil Perhitungan WSM');
        $this->line('  ─────────────────────────────────────────');

        $wsmHeaders = ['Siswa', 'WSM Sistem'];
        $wsmRows = [];

        foreach ($testStudents as $key => $student) {
            $wsmRows[] = [
                $student['name'],
                number_format($results[$key]['wsm'] ?? 0, 4),
            ];
        }
        $this->table($wsmHeaders, $wsmRows);

        // ---- c. Hasil WPM ----
        $this->newLine();
        $this->components->info('  📐 c. Hasil Perhitungan WPM');
        $this->line('  ─────────────────────────────────────────');

        $wpmHeaders = ['Siswa', 'WPM Sistem'];
        $wpmRows = [];

        foreach ($testStudents as $key => $student) {
            $wpmRows[] = [
                $student['name'],
                number_format($results[$key]['wpm'] ?? 0, 4),
            ];
        }
        $this->table($wpmHeaders, $wpmRows);

        // ---- d. Hasil Skor Q ----
        $this->newLine();
        $this->components->info('  🎯 d. Hasil Nilai Preferensi Q (λ = ' . $lambda . ')');
        $this->line('  ─────────────────────────────────────────');

        $qHeaders = ['Siswa', 'Q Sistem'];
        $qRows = [];

        foreach ($testStudents as $key => $student) {
            $qRows[] = [
                $student['name'],
                number_format($results[$key]['q'] ?? 0, 4),
            ];
        }
        $this->table($qHeaders, $qRows);

        // ---- e. Hasil Skor Risiko & Klasifikasi & Ranking ----
        $this->newLine();
        $this->components->info('  ⚠️  e. Hasil Skor Risiko, Klasifikasi, dan Ranking');
        $this->line('  ─────────────────────────────────────────');

        $riskLevels = config('spk.risk_levels');
        $riskHeaders = ['Rank', 'Siswa', 'Skor Q Sistem', 'Risk Score Sistem', 'Level Sistem'];
        
        $systemRanking = collect($results)
            ->sortBy('rank')
            ->keys()
            ->values()
            ->all();

        $riskRows = [];

        foreach ($systemRanking as $index => $key) {
            $studentName = $testStudents[$key]['name'] ?? $key;
            $qSystem = $results[$key]['q'] ?? 0;
            $riskScoreSystem = round((1 - $qSystem) * 100, 2);
            $riskLevelSystem = $this->determineRiskLevel($riskScoreSystem, $riskLevels);

            $levelEmoji = match ($riskLevelSystem) {
                'critical' => '🔴',
                'high' => '🟠',
                'medium' => '🟡',
                'low' => '🟢',
                default => '⚪',
            };

            $medal = match ($index) {
                0 => '🥇',
                1 => '🥈',
                2 => '🥉',
                default => '  ',
            };

            $riskRows[] = [
                $medal . ' ' . ($index + 1),
                $studentName,
                number_format($qSystem, 4),
                number_format($riskScoreSystem, 2),
                $levelEmoji . ' ' . strtoupper($riskLevelSystem),
            ];
        }
        $this->table($riskHeaders, $riskRows);
    }

    // ============================================================
    // TAHAP 2: REKOMENDASI TINDAKAN PEDAGOGIS
    // ============================================================

    private function runStage2(PedagogicalActionService $actionService, array $testStudents): void
    {
        $this->newLine();
        $this->components->info('═══════════════════════════════════════════════════════');
        $this->components->info('  TAHAP 2: PERHITUNGAN REKOMENDASI TINDAKAN PEDAGOGIS');
        $this->components->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        foreach ($testStudents as $key => $student) {
            $this->info("  👤 Siswa: {$student['name']} ({$student['profile']})");
            $this->line("     Data: att={$student['raw']['attendance']}, perf={$student['raw']['performance']}, eng={$student['raw']['engagement']}, mast={$student['raw']['subject_mastery']}");
            $this->newLine();

            $result = $actionService->calculateForStudent($student['raw']);

            // Tampilkan semua skor tindakan
            $actionHeaders = ['Rank', 'Tindakan', 'WSM Sistem', 'WPM Sistem', 'Skor Q Sistem'];
            $actionRows = [];

            foreach ($result['all_actions'] as $action) {
                $medal = match ($action['rank']) {
                    1 => '🥇',
                    2 => '🥈',
                    3 => '🥉',
                    default => '  ',
                };

                $label = $action['rank'] === 1
                    ? "<fg=green;options=bold>{$action['label']}</>"
                    : $action['label'];

                $actionRows[] = [
                    $medal . ' ' . $action['rank'],
                    $label,
                    number_format($action['wsm'], 6),
                    number_format($action['wpm'], 6),
                    number_format($action['q_score'], 6),
                ];
            }
            $this->table($actionHeaders, $actionRows);

            $recommended = $result['recommended'];
            $this->line("     <fg=cyan>Rekomendasi:</> <fg=yellow;options=bold>{$recommended['label']}</> (Q = {$recommended['q_score']})");
            $this->line("     <fg=cyan>Reasoning:</> {$recommended['reasoning']}");

            $this->newLine();
            $this->line('  ─────────────────────────────────────────────────────');
            $this->newLine();
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function determineRiskLevel(float $score, array $levels): string
    {
        return match (true) {
            $score >= $levels['critical'] => 'critical',
            $score >= $levels['high'] => 'high',
            $score >= $levels['medium'] => 'medium',
            default => 'low',
        };
    }
}
