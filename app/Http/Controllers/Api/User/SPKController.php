<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\SubjectMastery;
use App\Models\StudentRiskProfile;
use App\Services\SubjectMasteryService;
use App\Services\StudentRiskProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SPKController extends Controller
{
    public function __construct(
        private SubjectMasteryService $masteryService,
        private StudentRiskProfileService $riskService,
    ) {}

    /**
     * @OA\Get(
     *     path="/me/spk/subject-mastery",
     *     summary="Get student's subject mastery levels",
     *     tags={"SPK - Student"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subject mastery data"
     *     )
     * )
     */
    public function subjectMastery(Request $request): JsonResponse
    {
        $user = $request->user();

        $masteries = SubjectMastery::forUser($user->id)
            ->with('mataPelajaran')
            ->get();

        $overallMastery = $this->masteryService->getOverallMastery($user);

        return response()->json([
            'success' => true,
            'data' => [
                'overall_mastery' => round($overallMastery, 2),
                'subjects' => $masteries->map(function ($mastery) {
                    return [
                        'id' => $mastery->mata_pelajaran_id,
                        'name' => $mastery->mataPelajaran->name,
                        'mastery_percentage' => round($mastery->mastery_percentage, 2),
                        'status' => $mastery->status,
                        'status_label' => $mastery->getStatusLabel(),
                        'average_score' => $mastery->average_score,
                        'assessments_completed' => $mastery->assessments_completed,
                        'last_assessed_at' => $mastery->last_assessed_at?->format('Y-m-d H:i:s'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/spk/risk-profile",
     *     summary="Get student's risk profile",
     *     tags={"SPK - Student"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Student risk profile"
     *     )
     * )
     */
    public function riskProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = StudentRiskProfile::where('user_id', $user->id)
            ->first();

        if (!$profile) {
            return response()->json([
                'success' => true,
                'data' => [
                    'risk_level' => 'unknown',
                    'message' => 'Risk profile belum dihitung. Mulai belajar dan ikuti ujian untuk mendapatkan profil risiko.',
                ],
            ]);
        }

        $strugglingSubjects = SubjectMastery::where('user_id', $user->id)
            ->whereIn('mata_pelajaran_id', $profile->struggling_subjects ?? [])
            ->with('mataPelajaran')
            ->get();

        $actionRec = $profile->recommendations['_action_recommendation'] ?? null;
        $recommended = $actionRec['recommended'] ?? null;

        return response()->json([
            'success' => true,
            'data' => [
                'risk_level' => $profile->risk_level,
                'risk_level_label' => $profile->getRiskLevelLabel(),
                'overall_risk_score' => round($profile->overall_risk_score, 2),
                'needs_immediate_attention' => $profile->needsImmediateAttention(),
                'recommended_action' => $recommended ? [
                    'label' => $recommended['label'],
                    'description' => $recommended['description'],
                    'icon' => $recommended['icon'],
                    'color' => $recommended['color'],
                    'reasoning' => $recommended['reasoning'] ?? null,
                ] : null,
                'waspas' => [
                    'method' => 'WASPAS',
                    'score' => $profile->waspas_score,
                    'wsm' => $profile->wsm_score,
                    'wpm' => $profile->wpm_score,
                    'rank' => $profile->waspas_rank,
                    'lambda' => config('spk.waspas.lambda'),
                ],
                'risk_factors' => [
                    'attendance' => [
                        'score' => round($profile->attendance_risk, 2),
                        'label' => 'Kehadiran/Aktivitas',
                    ],
                    'performance' => [
                        'score' => round($profile->performance_risk, 2),
                        'label' => 'Performa Akademik',
                    ],
                    'engagement' => [
                        'score' => round($profile->engagement_risk, 2),
                        'label' => 'Keterlibatan Belajar',
                    ],
                    'subject_mastery' => [
                        'score' => round($profile->subject_mastery_risk, 2),
                        'label' => 'Penguasaan Mata Pelajaran',
                    ],
                ],
                'struggling_subjects' => $strugglingSubjects->map(fn($m) => [
                    'id' => $m->mata_pelajaran_id,
                    'name' => $m->mataPelajaran->name,
                    'mastery_percentage' => round($m->mastery_percentage, 2),
                    'status' => $m->status,
                ]),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/spk/recommendations",
     *     summary="Get personalized learning recommendations",
     *     tags={"SPK - Student"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Personalized recommendations"
     *     )
     * )
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();

        $profile = StudentRiskProfile::where('user_id', $user->id)->first();

        // Get text-based recommendations (existing)
        $textRecommendations = collect($profile?->recommendations ?? [])
            ->except('_waspas', '_action_recommendation')
            ->values()
            ->all();

        // Get action-based recommendation (new)
        $actionRec = $profile?->recommendations['_action_recommendation'] ?? null;
        $recommended = $actionRec['recommended'] ?? null;

        if (!$profile || (empty($textRecommendations) && !$recommended)) {
            return response()->json([
                'success' => true,
                'data' => [],
                'recommended_action' => null,
                'message' => 'Belum ada rekomendasi. Terus ikuti ujian untuk mendapatkan rekomendasi personal.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $textRecommendations,
            'recommended_action' => $recommended ? [
                'label' => $recommended['label'],
                'description' => $recommended['description'],
                'reasoning' => $recommended['reasoning'] ?? null,
            ] : null,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/spk/strengths-and-weaknesses",
     *     summary="Get summary of strengths and weaknesses",
     *     tags={"SPK - Student"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Strengths and weaknesses summary"
     *     )
     * )
     */
    public function strengthsAndWeaknesses(Request $request): JsonResponse
    {
        $user = $request->user();

        $masteredSubjects = $this->masteryService->getMasteredSubjects($user);
        $strugglingSubjects = $this->masteryService->getStrugglingSubjects($user);

        return response()->json([
            'success' => true,
            'data' => [
                'strengths' => [
                    'count' => count($masteredSubjects),
                    'subjects' => $masteredSubjects,
                    'message' => count($masteredSubjects) > 0 
                        ? "Anda menunjukkan penguasaan yang sangat baik dalam " . count($masteredSubjects) . " mata pelajaran"
                        : "Terus belajar untuk mencapai penguasaan mata pelajaran",
                ],
                'weaknesses' => [
                    'count' => count($strugglingSubjects),
                    'subjects' => $strugglingSubjects,
                    'message' => count($strugglingSubjects) > 0 
                        ? "Anda perlu fokus pada " . count($strugglingSubjects) . " mata pelajaran"
                        : "Selamat! Tidak ada mata pelajaran yang memerlukan perbaikan urgency",
                ],
                'overall_assessment' => $this->generateOverallAssessment($user, $masteredSubjects, $strugglingSubjects),
            ],
        ]);
    }

    /**
     * Generate overall assessment message
     */
    private function generateOverallAssessment(
        $user,
        array $masteredSubjects,
        array $strugglingSubjects
    ): array {
        $overallMastery = $this->masteryService->getOverallMastery($user);

        return [
            'overall_mastery_percentage' => round($overallMastery, 2),
            'assessment' => match (true) {
                $overallMastery >= 80 => 'Excellent! Anda menunjukkan pemahaman yang sangat baik di sebagian besar mata pelajaran.',
                $overallMastery >= 70 => 'Good! Performa Anda sudah baik. Terus pertahankan dan perbaiki area yang masih lemah.',
                $overallMastery >= 60 => 'Fair. Ada beberapa area yang perlu ditingkatkan. Fokus pada mata pelajaran yang masih lemah.',
                $overallMastery >= 50 => 'Needs improvement. Anda perlu lebih banyak belajar dan praktik untuk meningkatkan pemahaman.',
                default => 'Needs urgent attention. Mulai belajar lebih serius dan ikuti lebih banyak ujian untuk meningkatkan score.'
            },
            'next_steps' => $this->generateNextSteps($overallMastery, $strugglingSubjects),
        ];
    }

    /**
     * Generate next steps
     */
    private function generateNextSteps(float $overallMastery, array $strugglingSubjects): array
    {
        $steps = [];

        if (count($strugglingSubjects) > 0) {
            $steps[] = "Fokuskan belajar pada mata pelajaran yang masih lemah";
        }

        $steps[] = "Ikuti ujian latihan secara teratur untuk meningkatkan score";
        $steps[] = "Tingkatkan waktu belajar dan aktifitas pembelajaran";

        if ($overallMastery < 50) {
            $steps[] = "Hubungi guru untuk mendapatkan bantuan tambahan";
        }

        return $steps;
    }
}
