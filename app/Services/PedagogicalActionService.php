<?php

namespace App\Services;

use App\Models\User;

class PedagogicalActionService
{
    public function __construct(
        private WaspasService $waspasService,
    ) {}

    /**
     * Calculate recommended pedagogical actions for a student.
     *
     * For each defined action, WASPAS is run using the action's specific
     * weights and criteria types against the student's raw criteria.
     * The action with the highest Q score is the recommended one.
     *
     * @param  array<string, float>  $rawCriteria  ['attendance' => 5, 'performance' => 70, ...]
     * @return array{
     *     recommended: array{code: string, label: string, description: string, icon: string, color: string, q_score: float, reasoning: string},
     *     all_actions: list<array{code: string, label: string, q_score: float, wsm: float, wpm: float, rank: int}>,
     *     raw_criteria: array<string, float>,
     * }
     */
    public function calculateForStudent(array $rawCriteria): array
    {
        $actions = config('spk.pedagogical_actions');
        $lambda = (float) config('spk.waspas.lambda', 0.5);
        $benchmarks = collect(config('spk.waspas.criteria'))
            ->mapWithKeys(fn(array $c, string $k) => [$k => $c['benchmark']])
            ->all();

        $actionScores = [];

        foreach ($actions as $code => $action) {
            $weights = $action['weights'];
            $criteriaTypes = $action['criteria_types'];

            // Single alternative (the student) evaluated per action's profile
            $alternatives = ['student' => $rawCriteria];

            $result = $this->waspasService->calculate(
                $alternatives,
                $weights,
                $criteriaTypes,
                $lambda,
                $benchmarks,
            );

            $studentResult = $result['student'] ?? null;

            $actionScores[$code] = [
                'code' => $code,
                'label' => $action['label'],
                'description' => $action['description'],
                'icon' => $action['icon'],
                'color' => $action['color'],
                'q_score' => $studentResult ? round($studentResult['q'], 6) : 0,
                'wsm' => $studentResult ? round($studentResult['wsm'], 6) : 0,
                'wpm' => $studentResult ? round($studentResult['wpm'], 6) : 0,
                'normalized' => $studentResult['normalized'] ?? [],
            ];
        }

        // Sort by Q score descending and assign ranks
        usort($actionScores, fn(array $a, array $b) => $b['q_score'] <=> $a['q_score']);

        $ranked = [];
        foreach ($actionScores as $i => $action) {
            $action['rank'] = $i + 1;
            $ranked[] = $action;
        }

        $recommended = $ranked[0];
        $recommended['reasoning'] = $this->generateReasoning(
            $recommended['code'],
            $rawCriteria,
        );

        return [
            'recommended' => $recommended,
            'all_actions' => $ranked,
            'raw_criteria' => $rawCriteria,
        ];
    }

    /**
     * Generate a human-readable reasoning for why the action was recommended.
     */
    private function generateReasoning(string $actionCode, array $rawCriteria): string
    {
        $attendance = $rawCriteria['attendance'] ?? 0;
        $performance = $rawCriteria['performance'] ?? 0;
        $engagement = $rawCriteria['engagement'] ?? 0;
        $mastery = $rawCriteria['subject_mastery'] ?? 0;

        $benchmarks = config('spk.waspas.criteria');
        $attMax = $benchmarks['attendance']['benchmark']['max'];
        $perfMax = $benchmarks['performance']['benchmark']['max'];
        $engMax = $benchmarks['engagement']['benchmark']['max'];
        $mastMax = $benchmarks['subject_mastery']['benchmark']['max'];

        $attPct = $attMax > 0 ? round($attendance / $attMax * 100) : 0;
        $perfPct = $perfMax > 0 ? round($performance / $perfMax * 100) : 0;
        $engPct = $engMax > 0 ? round($engagement / $engMax * 100) : 0;
        $mastPct = $mastMax > 0 ? round($mastery / $mastMax * 100) : 0;

        return match ($actionCode) {
            'penguatan_materi' => "Siswa aktif belajar (engagement {$engPct}%) namun penguasaan materi masih rendah ({$mastPct}%). "
                . "Materi tambahan dan penjelasan ulang diperlukan untuk memperkuat pemahaman.",

            'remedial_latihan' => "Performa ujian/tugas rendah ({$perfPct}%) dan penguasaan materi belum memadai ({$mastPct}%). "
                . "Latihan ulang dan remedial diperlukan untuk memperbaiki nilai.",

            'bimbingan_individual' => "Kehadiran ({$attPct}%), performa ({$perfPct}%), engagement ({$engPct}%), dan mastery ({$mastPct}%) "
                . "semuanya di bawah standar. Siswa membutuhkan pendekatan personal dari guru.",

            'variasi_metode' => "Siswa cukup hadir ({$attPct}%) namun keterlibatan belajar rendah ({$engPct}%). "
                . "Metode penyampaian saat ini mungkin kurang cocok dan perlu divariasikan.",

            'pengayaan_tantangan' => "Siswa menunjukkan performa sangat baik: kehadiran {$attPct}%, performa {$perfPct}%, "
                . "engagement {$engPct}%, mastery {$mastPct}%. Berikan tantangan lebih tinggi agar tidak stagnan.",

            'monitoring_lanjutan' => "Kondisi siswa relatif stabil (kehadiran {$attPct}%, performa {$perfPct}%, "
                . "engagement {$engPct}%, mastery {$mastPct}%). Cukup pantau perkembangan tanpa intervensi khusus.",

            default => "Tindakan ini direkomendasikan berdasarkan analisis multi-kriteria WASPAS.",
        };
    }
}
