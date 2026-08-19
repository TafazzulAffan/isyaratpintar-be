<?php

namespace App\Services;

use App\Events\SPK\StudentNeedsAttention;
use App\Models\AssessmentAttempt;
use App\Models\StudentActivityLog;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;

class StudentRiskProfileService
{
    public function __construct(
        private ActivityTrackingService $activityService,
        private SubjectMasteryService $masteryService,
        private WaspasService $waspasService,
        private PedagogicalActionService $actionService,
    ) {}

    /**
     * Calculate risk profile for a student using WASPAS.
     * Recalculates all students to ensure correct matrix normalization.
     */
    public function calculateRiskProfile(User $student): StudentRiskProfile
    {
        $profiles = $this->calculateAllRiskProfiles();

        return $profiles[$student->id]
            ?? $this->buildEmptyProfile($student);
    }

    /**
     * Calculate WASPAS-based risk profiles for all students.
     *
     * @return array<int, StudentRiskProfile>
     */
    public function calculateAllRiskProfiles(): array
    {
        $students = User::where('role', UserRole::SISWA)->get();
        $config = config('spk.waspas');
        $criteriaConfig = $config['criteria'];

        $weights = collect($criteriaConfig)->mapWithKeys(
            fn (array $criterion, string $key) => [$key => $criterion['weight']]
        )->all();

        $criteriaTypes = collect($criteriaConfig)->mapWithKeys(
            fn (array $criterion, string $key) => [$key => $criterion['type']]
        )->all();

        $benchmarks = collect($criteriaConfig)->mapWithKeys(
            fn (array $criterion, string $key) => [$key => $criterion['benchmark']]
        )->all();

        $alternatives = [];
        $rawCriteriaByStudent = [];

        foreach ($students as $student) {
            $rawCriteria = $this->collectRawCriteria($student);
            $alternatives[(string) $student->id] = $rawCriteria;
            $rawCriteriaByStudent[$student->id] = $rawCriteria;
        }

        $waspasResults = $this->waspasService->calculate(
            $alternatives,
            $weights,
            $criteriaTypes,
            (float) $config['lambda'],
            $benchmarks,
        );

        $profiles = [];

        foreach ($students as $student) {
            $result = $waspasResults[(string) $student->id] ?? null;

            if ($result === null) {
                $profiles[$student->id] = $this->buildEmptyProfile($student);
                continue;
            }

            $profiles[$student->id] = $this->persistProfile($student, $result, $rawCriteriaByStudent[$student->id]);
        }

        return $profiles;
    }

    /**
     * @return array<string, float>
     */
    private function collectRawCriteria(User $student): array
    {
        return [
            'attendance' => $this->getRawAttendanceScore($student),
            'performance' => $this->getRawPerformanceScore($student),
            'engagement' => $this->getRawEngagementScore($student),
            'subject_mastery' => $this->getRawSubjectMasteryScore($student),
        ];
    }

    /**
     * Active learning days in the last 7 days (benefit: higher is better).
     */
    private function getRawAttendanceScore(User $student): float
    {
        return (float) StudentActivityLog::forUser($student->id)
            ->where('logged_at', '>=', now()->subDays(7))
            ->count(DB::raw('DISTINCT DATE(logged_at)'));
    }

    /**
     * Average assessment score from last 10 completed attempts (benefit).
     */
    private function getRawPerformanceScore(User $student): float
    {
        $attempts = AssessmentAttempt::where('user_id', $student->id)
            ->where('status', 'COMPLETED')
            ->latest('completed_at')
            ->limit(10)
            ->get();

        $tasks = \App\Models\CaseSubmission::where('user_id', $student->id)
            ->whereNotNull('score')
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        $combined = $attempts->map(fn($item) => ['score' => $item->score, 'date' => $item->completed_at])
            ->concat($tasks->map(fn($item) => ['score' => $item->score, 'date' => $item->submitted_at]))
            ->sortByDesc('date')
            ->take(10);

        if ($combined->isEmpty()) {
            return 0.0;
        }

        return (float) $combined->avg('score');
    }

    /**
     * Total learning minutes in the last 30 days (benefit).
     */
    private function getRawEngagementScore(User $student): float
    {
        return (float) intval(
            $this->activityService->getTotalLearningTime($student, 30) / 60
        );
    }

    /**
     * Overall subject mastery percentage (benefit).
     */
    private function getRawSubjectMasteryScore(User $student): float
    {
        return (float) $this->masteryService->getOverallMastery($student);
    }

    /**
     * @param  array{wsm: float, wpm: float, q: float, normalized: array<string, float>, rank: int}  $waspasResult
     * @param  array<string, float>  $rawCriteria
     */
    private function persistProfile(User $student, array $waspasResult, array $rawCriteria): StudentRiskProfile
    {
        $normalized = $waspasResult['normalized'];
        $q = $waspasResult['q'];

        $attendanceRisk = $this->criterionToRisk($normalized['attendance'] ?? 0);
        $performanceRisk = $this->criterionToRisk($normalized['performance'] ?? 0);
        $engagementRisk = $this->criterionToRisk($normalized['engagement'] ?? 0);
        $subjectMasteryRisk = $this->criterionToRisk($normalized['subject_mastery'] ?? 0);

        $overallRiskScore = round((1 - $q) * 100, 2);
        $riskLevel = $this->determineRiskLevel($overallRiskScore);
        $strugglingSubjects = $this->getStrugglingSubjects($student);

        // Calculate recommended pedagogical action using WASPAS
        $actionResult = $this->actionService->calculateForStudent($rawCriteria);

        $recommendations = $this->generateRecommendations($student, $strugglingSubjects, $attendanceRisk, $performanceRisk, $engagementRisk);

        $profile = StudentRiskProfile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'attendance_risk' => $attendanceRisk,
                'performance_risk' => $performanceRisk,
                'engagement_risk' => $engagementRisk,
                'subject_mastery_risk' => $subjectMasteryRisk,
                'overall_risk_score' => $overallRiskScore,
                'wsm_score' => $waspasResult['wsm'],
                'wpm_score' => $waspasResult['wpm'],
                'waspas_rank' => $waspasResult['rank'],
                'risk_level' => $riskLevel,
                'struggling_subjects' => $strugglingSubjects,
                'recommendations' => array_merge($recommendations, [
                    '_waspas' => [
                        'method' => 'WASPAS',
                        'lambda' => config('spk.waspas.lambda'),
                        'raw_criteria' => $rawCriteria,
                        'normalized_criteria' => $normalized,
                        'weights' => collect(config('spk.waspas.criteria'))
                            ->mapWithKeys(fn (array $c, string $k) => [$k => $c['weight']])
                            ->all(),
                    ],
                    '_action_recommendation' => $actionResult,
                ]),
                'calculated_at' => now(),
            ]
        );

        if ($riskLevel === 'critical' || $riskLevel === 'high') {
            event(new StudentNeedsAttention(
                $student,
                [
                    'risk_score' => $overallRiskScore,
                    'waspas_score' => $q,
                    'waspas_rank' => $waspasResult['rank'],
                    'recommended_action' => $actionResult['recommended']['code'] ?? null,
                    'attendance_issue' => $attendanceRisk > 60,
                    'performance_issue' => $performanceRisk > 60,
                    'engagement_issue' => $engagementRisk > 60,
                    'mastery_issues' => count($strugglingSubjects) > 0,
                ],
                $riskLevel
            ));
        }

        return $profile;
    }

    private function buildEmptyProfile(User $student): StudentRiskProfile
    {
        return StudentRiskProfile::updateOrCreate(
            ['user_id' => $student->id],
            [
                'attendance_risk' => 50,
                'performance_risk' => 50,
                'engagement_risk' => 50,
                'subject_mastery_risk' => 50,
                'overall_risk_score' => 50,
                'waspas_score' => null,
                'wsm_score' => null,
                'wpm_score' => null,
                'waspas_rank' => null,
                'risk_level' => 'medium',
                'struggling_subjects' => [],
                'recommendations' => [],
                'calculated_at' => now(),
            ]
        );
    }

    private function criterionToRisk(float $normalizedBenefit): float
    {
        return round((1 - $normalizedBenefit) * 100, 2);
    }

    private function determineRiskLevel(float $score): string
    {
        $levels = config('spk.risk_levels');

        return match (true) {
            $score >= $levels['critical'] => 'critical',
            $score >= $levels['high'] => 'high',
            $score >= $levels['medium'] => 'medium',
            default => 'low',
        };
    }

    private function getStrugglingSubjects(User $student): array
    {
        return SubjectMastery::forUser($student->id)
            ->needsHelp()
            ->get()
            ->pluck('mata_pelajaran_id')
            ->toArray();
    }

    private function generateRecommendations(User $student, array $strugglingSubjects, float $attendanceRisk, float $performanceRisk, float $engagementRisk): array
    {
        $recommendations = [];

        if ($attendanceRisk > 40) {
            $recommendations[] = "Tingkat kehadiran siswa di bawah standar. Pantau aktivitas login dan frekuensi akses platform siswa.";
        }

        if ($performanceRisk > 40) {
            $recommendations[] = "Performa ujian/tugas kurang memuaskan. Pertimbangkan pemberian latihan tambahan atau remedial.";
        }

        if ($engagementRisk > 40) {
            $recommendations[] = "Keterlibatan belajar minim. Ajak siswa agar lebih sering berinteraksi dengan materi pembelajaran.";
        }

        foreach ($strugglingSubjects as $subjectId) {
            $mastery = SubjectMastery::where('user_id', $student->id)
                ->where('mata_pelajaran_id', $subjectId)
                ->first();

            if ($mastery) {
                $recommendations[] = "Berikan bimbingan ekstra pada mata pelajaran {$mastery->mataPelajaran->name} (penguasaan masih rendah).";
            }
        }

        return $recommendations;
    }

    public function getRiskProfileSummary(User $student): ?array
    {
        $profile = StudentRiskProfile::where('user_id', $student->id)->first();

        if (!$profile) {
            return null;
        }

        $waspasMeta = $profile->recommendations['_waspas'] ?? null;

        return [
            'risk_level' => $profile->risk_level,
            'risk_level_label' => $profile->getRiskLevelLabel(),
            'overall_risk_score' => $profile->overall_risk_score,
            'risk_percentage' => $profile->getRiskPercentage(),
            'needs_immediate_attention' => $profile->needsImmediateAttention(),
            'waspas' => [
                'score' => $profile->waspas_score,
                'wsm' => $profile->wsm_score,
                'wpm' => $profile->wpm_score,
                'rank' => $profile->waspas_rank,
                'lambda' => config('spk.waspas.lambda'),
                'raw_criteria' => $waspasMeta['raw_criteria'] ?? null,
                'normalized_criteria' => $waspasMeta['normalized_criteria'] ?? null,
            ],
            'risk_factors' => [
                'attendance' => round(100 - $profile->attendance_risk, 2),
                'performance' => round(100 - $profile->performance_risk, 2),
                'engagement' => round(100 - $profile->engagement_risk, 2),
                'subject_mastery' => round(100 - $profile->subject_mastery_risk, 2),
            ],
            'struggling_subjects_count' => count($profile->struggling_subjects ?? []),
            'recommendations_count' => count(array_filter(
                array_keys($profile->recommendations ?? []),
                fn ($key) => $key !== '_waspas'
            )),
        ];
    }
}
