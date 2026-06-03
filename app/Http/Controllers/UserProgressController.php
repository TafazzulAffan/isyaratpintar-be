<?php

namespace App\Http\Controllers;

use App\Http\Requests\MarkLessonCompletedRequest;
use App\Http\Resources\MataPelajaranResource;
use App\Services\UserProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProgressController extends Controller
{
    public function __construct(
        private UserProgressService $userProgressService
    ) {}

    /**
     * @OA\Post(
     *     path="/lessons/{lesson}/mark-completed",
     *     summary="Mark lesson as completed",
     *     description="Mark a lesson as completed for the authenticated user.",
     *     tags={"User Progress"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="lesson",
     *         in="path",
     *         required=true,
     *         description="Lesson ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="lesson_id", type="integer", description="Lesson ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson marked as completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="lessonId", type="integer"),
     *             @OA\Property(property="completed", type="boolean", example=true),
     *             @OA\Property(property="completedAt", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function markCompleted(MarkLessonCompletedRequest $request, int $lesson): JsonResponse
    {
        $progress = $this->userProgressService->markLessonAsCompleted($request->user(), $lesson);

        return response()->json([
            'lessonId' => $progress->lesson_id,
            'completed' => (bool) $progress->completed,
            'completedAt' => $progress->completed_at,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/my-progress",
     *     summary="Get user's progress",
     *     description="Retrieve the authenticated user's overall progress including completed lessons and mata_pelajarans.",
     *     tags={"User Progress"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User progress retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="userId", type="integer"),
     *             @OA\Property(property="totalLessons", type="integer"),
     *             @OA\Property(property="completedLessons", type="integer"),
     *             @OA\Property(property="progressPercentage", type="number", format="float"),
     *             @OA\Property(property="mataPelajarans", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function myProgress(Request $request): JsonResponse
    {
        $progress = $this->userProgressService->getUserProgress($request->user());

        return response()->json([
            'userId' => $progress['userId'],
            'totalLessons' => $progress['totalLessons'],
            'completedLessons' => $progress['completedLessons'],
            'progressPercentage' => $progress['progressPercentage'],
            'mataPelajarans' => MataPelajaranResource::collection($progress['mataPelajarans'])->resolve(),
        ]);
    }
}
