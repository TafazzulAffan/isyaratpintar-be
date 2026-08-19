<?php

namespace App\Http\Controllers\Api\User;

use App\Events\Session\{SessionStarted, SessionEnded, PageViewed};
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityTrackingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/tracking/session/start",
     *     summary="Start a learning session",
     *     tags={"Activity Tracking"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="entity_type", type="string", example="Lesson"),
     *             @OA\Property(property="entity_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Session started successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="session_started_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function startSession(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
        ]);

        // 🔥 DISPATCH SESSION STARTED EVENT
        event(new SessionStarted(
            $user,
            $validated['entity_type'] ?? null,
            $validated['entity_id'] ?? null
        ));

        return response()->json([
            'success' => true,
            'message' => 'Session started',
            'data' => [
                'session_started_at' => now(),
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/tracking/session/end",
     *     summary="End a learning session",
     *     tags={"Activity Tracking"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="duration_seconds", type="integer", description="Session duration in seconds", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Session ended successfully"
     *     )
     * )
     */
    public function endSession(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'duration_seconds' => 'required|integer|min:0',
        ]);

        // 🔥 DISPATCH SESSION ENDED EVENT
        event(new SessionEnded(
            $user,
            $validated['duration_seconds']
        ));

        return response()->json([
            'success' => true,
            'message' => 'Session ended',
            'data' => [
                'session_ended_at' => now(),
                'duration_seconds' => $validated['duration_seconds'],
                'duration_minutes' => round($validated['duration_seconds'] / 60, 2),
            ],
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/tracking/page-viewed",
     *     summary="Track page view",
     *     tags={"Activity Tracking"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="page_url", type="string", example="/lessons/1"),
     *             @OA\Property(property="entity_type", type="string", nullable=true, example="Lesson"),
     *             @OA\Property(property="entity_id", type="integer", nullable=true, example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Page view tracked successfully"
     *     )
     * )
     */
    public function trackPageView(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'page_url' => 'required|string',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|integer',
        ]);

        // 🔥 DISPATCH PAGE VIEWED EVENT
        event(new PageViewed(
            $user,
            $validated['page_url'],
            $validated['entity_type'] ?? null,
            $validated['entity_id'] ?? null
        ));

        return response()->json([
            'success' => true,
            'message' => 'Page view tracked',
            'data' => [
                'page_url' => $validated['page_url'],
            ],
        ], 201);
    }
}
