<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\SessionDurationResource;
use App\Services\ActivityTrackingService;
use App\Services\StudentAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAnalyticsController extends Controller
{
    public function __construct(
        private ActivityTrackingService $activityService,
        private StudentAnalyticsService $analyticsService,
    ) {}

    /**
     * @OA\Get(
     *     path="/me/activity-log",
     *     summary="Get student activity log",
     *     tags={"Student Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="activity_type",
     *         in="query",
     *         description="Filter by activity type"
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (Y-m-d)"
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (Y-m-d)"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Activity log retrieved successfully"
     *     )
     * )
     */
    public function activityLog(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'activity_type' => 'nullable|string',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $logs = $this->activityService->getUserActivityLog(
            $user,
            $validated['activity_type'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['per_page'] ?? 25
        );

        return response()->json([
            'success' => true,
            'data' => ActivityLogResource::collection($logs->items()),
            'pagination' => [
                'per_page' => $logs->perPage(),
                'total' => $logs->total() ?? 'N/A',
                'has_more' => isset($logs->hasMorePages) && $logs->hasMorePages(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/learning-stats",
     *     summary="Get student learning statistics",
     *     tags={"Student Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to analyze (default 30)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Learning statistics retrieved successfully"
     *     )
     * )
     */
    public function learningStats(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $stats = $this->analyticsService->getLearningStatistics($user, $validated['days'] ?? 30);
        $engagementScore = $this->analyticsService->getEngagementScore($user, $validated['days'] ?? 30);

        return response()->json([
            'success' => true,
            'data' => array_merge($stats, [
                'engagement_score' => $engagementScore,
            ]),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/session-history",
     *     summary="Get student session history",
     *     tags={"Student Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="session_type",
     *         in="query",
     *         description="Filter by session type (learning, assessment)"
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date (Y-m-d)"
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date (Y-m-d)"
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session history retrieved successfully"
     *     )
     * )
     */
    public function sessionHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'session_type' => 'nullable|string|in:learning,assessment,general',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $sessions = $this->activityService->getUserSessionHistory(
            $user,
            $validated['session_type'] ?? null,
            $validated['start_date'] ?? null,
            $validated['end_date'] ?? null,
            $validated['per_page'] ?? 25
        );

        return response()->json([
            'success' => true,
            'data' => SessionDurationResource::collection($sessions->items()),
            'pagination' => [
                'per_page' => $sessions->perPage(),
                'total' => $sessions->total() ?? 'N/A',
                'has_more' => isset($sessions->hasMorePages) && $sessions->hasMorePages(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/me/daily-learning-breakdown",
     *     summary="Get daily learning time breakdown for charting",
     *     tags={"Student Analytics"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Number of days to analyze (default 30)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Daily learning breakdown retrieved successfully"
     *     )
     * )
     */
    public function dailyLearningBreakdown(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'days' => 'nullable|integer|min:7|max:365',
        ]);

        $breakdown = $this->activityService->getDailyLearningBreakdown(
            $user,
            $validated['days'] ?? 30
        );

        return response()->json([
            'success' => true,
            'data' => $breakdown,
        ]);
    }
}
