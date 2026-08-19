<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentRiskProfile;
use App\Models\SubjectMastery;
use App\Models\TeacherInsight;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SPKController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/spk/at-risk-students",
     *     summary="Get students at risk that need attention",
     *     tags={"SPK - Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="risk_level",
     *         in="query",
     *         description="Filter by risk level (critical, high, medium, low)"
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Sort by (risk_score, name, last_activity)"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of at-risk students"
     *     )
     * )
     */
    private function getGuruStudentIds(User $guru)
    {
        return \App\Models\User::whereHas('kelasDiikuti', function ($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })->pluck('id');
    }

    public function atRiskStudents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'risk_level' => 'nullable|string|in:critical,high,medium,low',
            'sort_by' => 'nullable|string|in:risk_score,name,last_activity',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = StudentRiskProfile::with('user');

        $user = $request->user();
        if ($user && $user->isGuru()) {
            $studentIds = $this->getGuruStudentIds($user);
            $query->whereIn('user_id', $studentIds);
        }

        if ($validated['risk_level'] ?? null) {
            $query->where('risk_level', $validated['risk_level']);
        }

        $sortBy = $validated['sort_by'] ?? 'overall_risk_score';
        if ($sortBy === 'last_activity') {
            $query->join('users', 'student_risk_profiles.user_id', '=', 'users.id')
                ->orderBy('users.last_activity_at', 'desc')
                ->select('student_risk_profiles.*');
        } elseif ($sortBy === 'name') {
            $query->join('users', 'student_risk_profiles.user_id', '=', 'users.id')
                ->orderBy('users.name', 'asc')
                ->select('student_risk_profiles.*');
        } elseif ($sortBy === 'risk_score') {
            $query->orderByDesc('overall_risk_score');
        } else {
            $query->orderByDesc($sortBy);
        }

        $students = $query->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'success' => true,
            'data' => $students->map(function ($profile) {
                $actionRec = $profile->recommendations['_action_recommendation'] ?? null;
                $recommended = $actionRec['recommended'] ?? null;

                return [
                    'id' => $profile->user->id,
                    'name' => $profile->user->name,
                    'email' => $profile->user->email,
                    'risk_level' => $profile->risk_level,
                    'risk_level_label' => $profile->getRiskLevelLabel(),
                    'overall_risk_score' => round($profile->overall_risk_score, 2),
                    'waspas' => [
                        'score' => $profile->waspas_score,
                        'wsm' => $profile->wsm_score,
                        'wpm' => $profile->wpm_score,
                        'rank' => $profile->waspas_rank,
                    ],
                    'recommended_action' => $recommended ? [
                        'code' => $recommended['code'],
                        'label' => $recommended['label'],
                        'description' => $recommended['description'],
                        'icon' => $recommended['icon'],
                        'color' => $recommended['color'],
                        'q_score' => $recommended['q_score'],
                        'reasoning' => $recommended['reasoning'] ?? null,
                    ] : null,
                    'risk_factors' => [
                        'attendance' => round(100 - $profile->attendance_risk, 2),
                        'performance' => round(100 - $profile->performance_risk, 2),
                        'engagement' => round(100 - $profile->engagement_risk, 2),
                        'subject_mastery' => round(100 - $profile->subject_mastery_risk, 2),
                    ],
                    'struggling_subjects_count' => count($profile->struggling_subjects ?? []),
                    'needs_immediate_attention' => $profile->needsImmediateAttention(),
                    'last_activity_at' => $profile->user->last_activity_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'total' => $students->total(),
                'per_page' => $students->perPage(),
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/spk/subject-performance",
     *     summary="Get subject mastery statistics across all students",
     *     tags={"SPK - Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Subject performance statistics"
     *     )
     * )
     */
    public function subjectPerformance(Request $request): JsonResponse
    {
        $query = SubjectMastery::with('mataPelajaran');

        $user = $request->user();
        if ($user && $user->isGuru()) {
            $studentIds = $this->getGuruStudentIds($user);
            $query->whereIn('user_id', $studentIds);
        }

        $masteries = $query->get()->groupBy('mata_pelajaran_id');

        $stats = $masteries->map(function ($subjects) {
            $subject = $subjects->first();
            return [
                'mata_pelajaran_id' => $subject->mata_pelajaran_id,
                'mata_pelajaran_name' => $subject->mataPelajaran->name,
                'average_mastery' => round($subjects->avg('mastery_percentage'), 2),
                'students_excellent' => $subjects->where('status', 'excellent')->count(),
                'students_good' => $subjects->where('status', 'good')->count(),
                'students_needs_help' => $subjects->where('status', 'needs_help')->count(),
                'students_poor' => $subjects->where('status', 'poor')->count(),
                'total_students' => $subjects->count(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/spk/teacher-insights",
     *     summary="Get teacher insights for monitoring",
     *     tags={"SPK - Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="insight_type",
     *         in="query",
     *         description="Filter by insight type"
     *     ),
     *     @OA\Parameter(
     *         name="acknowledged",
     *         in="query",
     *         description="Filter by acknowledgement status"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher insights"
     *     )
     * )
     */
    public function teacherInsights(Request $request): JsonResponse
    {
        if ($request->has('acknowledged')) {
            $request->merge([
                'acknowledged' => filter_var($request->input('acknowledged'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        $validated = $request->validate([
            'insight_type' => 'nullable|string',
            'acknowledged' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TeacherInsight::with('student');

        $user = $request->user();
        if ($user && $user->isGuru()) {
            $studentIds = $this->getGuruStudentIds($user);
            $query->whereIn('student_id', $studentIds);
        }

        if ($validated['insight_type'] ?? null) {
            $query->byType($validated['insight_type']);
        }

        if (isset($validated['acknowledged'])) {
            $query->where('teacher_acknowledged', $validated['acknowledged']);
        }

        $insights = $query->latest('created_at')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'success' => true,
            'data' => $insights->map(function ($insight) {
                return [
                    'id' => $insight->id,
                    'student_name' => $insight->student->name,
                    'student_id' => $insight->student_id,
                    'insight_type' => $insight->insight_type,
                    'insight_type_label' => $insight->getTypeLabel(),
                    'message' => $insight->message,
                    'supporting_data' => $insight->supporting_data,
                    'teacher_acknowledged' => $insight->teacher_acknowledged,
                    'acknowledged_at' => $insight->acknowledged_at?->format('Y-m-d H:i:s'),
                    'created_at' => $insight->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'pagination' => [
                'total' => $insights->total(),
                'per_page' => $insights->perPage(),
                'current_page' => $insights->currentPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/admin/spk/student/{studentId}/risk-profile",
     *     summary="Get detailed risk profile for a specific student",
     *     tags={"SPK - Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="studentId",
     *         in="path",
     *         required=true,
     *         description="Student ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student risk profile details"
     *     )
     * )
     */
    public function studentRiskProfile(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->isGuru()) {
            $studentIds = $this->getGuruStudentIds($user);
            if (!$studentIds->contains($studentId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to student risk profile',
                ], 403);
            }
        }
        $profile = StudentRiskProfile::where('user_id', $studentId)
            ->with('user')
            ->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Risk profile not found',
            ], 404);
        }

        $strugglingSubjects = SubjectMastery::where('user_id', $studentId)
            ->whereIn('mata_pelajaran_id', $profile->struggling_subjects ?? [])
            ->with('mataPelajaran')
            ->get();

        $actionRec = $profile->recommendations['_action_recommendation'] ?? null;

        return response()->json([
            'success' => true,
            'data' => [
                'student_id' => $profile->user_id,
                'student_name' => $profile->user->name,
                'risk_level' => $profile->risk_level,
                'risk_level_label' => $profile->getRiskLevelLabel(),
                'overall_risk_score' => round($profile->overall_risk_score, 2),
                'recommended_action' => $actionRec['recommended'] ?? null,
                'all_action_scores' => $actionRec['all_actions'] ?? [],
                'risk_factors' => [
                    'attendance' => round(100 - $profile->attendance_risk, 2),
                    'performance' => round(100 - $profile->performance_risk, 2),
                    'engagement' => round(100 - $profile->engagement_risk, 2),
                    'subject_mastery' => round(100 - $profile->subject_mastery_risk, 2),
                ],
                'struggling_subjects' => $strugglingSubjects->map(fn($m) => [
                    'id' => $m->mata_pelajaran_id,
                    'name' => $m->mataPelajaran->name,
                    'mastery_percentage' => $m->mastery_percentage,
                    'status' => $m->status,
                ]),
                'recommendations' => collect($profile->recommendations ?? [])
                    ->filter(fn($value, $key) => $key !== '_waspas' && $key !== '_action_recommendation')
                    ->values()
                    ->all(),
                'calculated_at' => $profile->calculated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}
