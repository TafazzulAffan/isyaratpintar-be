<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\AssessmentAttemptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitAnswerRequest;
use App\Http\Resources\AssessmentDetailResource;
use App\Http\Resources\AssessmentResource;
use App\Services\AnswerService;
use App\Services\AssessmentService;
use App\Services\AttemptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(
        private AssessmentService $assessmentService,
        private AttemptService $attemptService,
        private AnswerService $answerService,
    ) {}

    /**
     * @OA\Get(
     *     path="/assessments",
     *     summary="Get all assessments",
     *     description="Retrieve a paginated list of all available assessments",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of assessments",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="time_limit", type="integer", description="in minutes"),
     *                 @OA\Property(property="total_questions", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $assessments = $this->assessmentService->getAllAssessments($request->user());
        return response()->json([
            'success' => true,
            'data' => AssessmentResource::collection($assessments->items()),
            'pagination' => [
                'total' => $assessments->total(),
                'count' => $assessments->count(),
                'per_page' => $assessments->perPage(),
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/assessments/mata-pelajaran/{id}",
     *     summary="Get assessments filtered by mata pelajaran",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Mata pelajaran ID"
     *     ),
     *     @OA\Response(response=200, description="List of assessments filtered by mata pelajaran")
     * )
     */
    public function byMataPelajaran(Request $request, int $id): JsonResponse
    {
        $assessments = $this->assessmentService->getAssessmentsByMataPelajaranId($id, $request->user());

        return response()->json([
            'success' => true,
            'data' => AssessmentResource::collection($assessments->items()),
            'pagination' => [
                'total' => $assessments->total(),
                'count' => $assessments->count(),
                'per_page' => $assessments->perPage(),
                'current_page' => $assessments->currentPage(),
                'last_page' => $assessments->lastPage(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/assessments/{slug}",
     *     summary="Get assessment detail with questions",
     *     description="Retrieve detailed assessment with all questions and options. Note: is_correct field is NOT included to prevent answer leakage.",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Assessment slug"
     *     ),
    *         @OA\Response(
    *         response=200,
    *         description="Assessment details with questions",
    *         @OA\JsonContent(
    *             @OA\Property(property="success", type="boolean", example=true),
    *             @OA\Property(property="data", type="object",
    *                 @OA\Property(property="id", type="string"),
    *                 @OA\Property(property="title", type="string"),
    *                 @OA\Property(property="slug", type="string"),
    *                 @OA\Property(property="description", type="string"),
    *                 @OA\Property(property="total_questions", type="integer"),
    *                 @OA\Property(property="time_limit", type="integer"),
    *                 @OA\Property(property="questions", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string"),
     *                     @OA\Property(property="question", type="string"),
     *                     @OA\Property(property="options", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="string"),
     *                         @OA\Property(property="label", type="string"),
     *                         @OA\Property(property="text", type="string")
     *                     ))
     *                 ))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assessment not found")
     * )
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $assessment = $this->assessmentService->getAssessmentBySlug($slug, $request->user());

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new AssessmentDetailResource($assessment),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/assessments/{id}/start",
     *     summary="Start a new assessment attempt",
     *     description="Begin taking an assessment. Only one active attempt allowed per assessment per user.",
     *     tags={"Assessment Attempts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assessment ID"
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assessment attempt created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assessment attempt started"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="attempt_id", type="integer"),
     *                 @OA\Property(property="assessment_id", type="integer"),
     *                 @OA\Property(property="status", type="string", example="IN_PROGRESS"),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assessment not found"),
     *     @OA\Response(response=400, description="User already has active attempt")
     * )
     */
    public function start(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $assessment = $this->assessmentService->getAssessmentById($id, $user);

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found',
            ], 404);
        }

        try {
            $attempt = $this->attemptService->startAttempt($user, $assessment);

            return response()->json([
                'success' => true,
                'message' => 'Assessment attempt started',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $attempt->assessment_id,
                    'status' => $attempt->status->value,
                    'started_at' => $attempt->started_at,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/assessments/{attemptId}/answers",
     *     summary="Submit answer(s) to question(s)",
     *     description="Submit user's answer(s). Supports both single and bulk submissions. Each question can only be answered once. Cannot answer after completion or timeout.",
     *     tags={"Assessment Attempts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="attemptId",
     *         in="path",
     *         required=true,
     *         description="Assessment Attempt ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     title="Single Answer",
     *                     @OA\Property(property="question_id", type="integer", example=1),
     *                     @OA\Property(property="selected_option_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                 ),
     *                 @OA\Schema(
     *                     title="Bulk Answers",
     *                     @OA\Property(property="answers", type="array", @OA\Items(
     *                         @OA\Property(property="question_id", type="integer", example=1),
     *                         @OA\Property(property="selected_option_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *                     ))
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Answer(s) submitted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Answer submitted"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Question already answered or attempt not in progress"),
     *     @OA\Response(response=404, description="Attempt not found")
     * )
     */
    public function submitAnswer(int $attemptId, SubmitAnswerRequest $request): JsonResponse
    {
        $user = $request->user();
        $attempt = $this->attemptService->getAttempt($attemptId, $user);

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found',
            ], 404);
        }

        try {
            // Check if bulk submission or single submission
            if ($request->has('answers')) {
                // Bulk submission
                $result = $this->answerService->submitAnswersBulk(
                    $attempt,
                    $request->answers
                );

                return response()->json([
                    'success' => true,
                    'message' => "Submitted {$result['success_count']} answers",
                    'data' => $result,
                ], 201);
            } else {
                // Single submission
                $answer = $this->answerService->submitAnswer(
                    $attempt,
                    $request->question_id,
                    $request->selected_option_id
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Answer submitted',
                    'data' => [
                        'answer_id' => $answer->id,
                        'is_correct' => $answer->is_correct,
                    ],
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/assessments/{attemptId}/finish",
     *     summary="Complete assessment and get final score",
     *     description="Finalize the assessment attempt. Calculates score automatically.",
     *     tags={"Assessment Attempts"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="attemptId",
     *         in="path",
     *         required=true,
     *         description="Assessment Attempt ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assessment completed"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="score", type="number", format="float", example=80.0),
     *                 @OA\Property(property="correct_answers", type="integer", example=8),
     *                 @OA\Property(property="total_questions", type="integer", example=10),
     *                 @OA\Property(property="status", type="string", example="COMPLETED")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Attempt timed out or not in progress"),
     *     @OA\Response(response=404, description="Attempt not found")
     * )
     */
    public function finishAttempt(int $attemptId, Request $request): JsonResponse
    {
        $user = $request->user();
        $attempt = $this->attemptService->getAttempt($attemptId, $user);

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found',
            ], 404);
        }

        try {
            $attempt = $this->attemptService->completeAttempt($attempt);

            return response()->json([
                'success' => true,
                'message' => 'Assessment completed',
                'data' => [
                    'score' => $attempt->score,
                    'correct_answers' => $attempt->getCorrectAnswersCount(),
                    'total_questions' => $attempt->assessment->questions()->count(),
                    'status' => $attempt->status->value,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
