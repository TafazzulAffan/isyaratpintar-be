<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\UpdateAssessmentRequest;
use App\Http\Resources\AssessmentResource;
use App\Models\Assessment;
use App\Services\AssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(
        private AssessmentService $assessmentService,
    ) {}

    /**
     * @OA\Post(
     *     path="/assessments",
     *     summary="Create new assessment",
     *     description="Create a new assessment with title, description, time limit, and assessment level. This endpoint is used to add a new assessment to the system for admin and guru roles.",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"slug","mata_pelajaran_id","kelas_id","title","time_limit"},
     *             @OA\Property(property="slug", type="string", example="math-quiz-2024", description="Unique slug for the assessment"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer", example=1, description="Mata pelajaran ID - the assessment will be grouped under this subject"),
     *             @OA\Property(property="kelas_id", type="integer", example=1, description="Kelas ID - the assessment will be grouped under this class"),
     *             @OA\Property(property="title", type="string", example="Math Quiz 2024", description="Assessment title"),
     *             @OA\Property(property="description", type="string", nullable=true, example="A comprehensive mathematics assessment", description="Assessment description"),
     *             @OA\Property(property="time_limit", type="integer", example=60, description="Time limit in minutes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assessment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assessment created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="time_limit", type="integer"),
     *                 @OA\Property(property="total_questions", type="integer"),

     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error or slug already exists"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - only admin/guru can create")
     * )
     */
    public function store(StoreAssessmentRequest $request): JsonResponse
    {
        $this->authorize('create', Assessment::class);

        try {
            $assessment = $this->assessmentService->createAssessment($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Assessment created successfully',
                'data' => new AssessmentResource($assessment),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/assessments/{id}",
     *     summary="Update assessment",
     *     description="Update an existing assessment including slug, title, description, time limit, and assessment level. This endpoint modifies assessment details for admin and guru roles.",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assessment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="slug", type="string", nullable=true, example="math-quiz-2024-updated", description="Updated slug"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer", nullable=true, description="Mata pelajaran ID - the assessment will be grouped under this subject"),
     *             @OA\Property(property="kelas_id", type="integer", nullable=true, description="Kelas ID - the assessment will be grouped under this class"),
     *             @OA\Property(property="title", type="string", nullable=true, example="Math Quiz 2024 Updated", description="Updated title"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Updated description", description="Updated description"),
     *             @OA\Property(property="time_limit", type="integer", nullable=true, example=90, description="Updated time limit in minutes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assessment updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="time_limit", type="integer"),
     *                 @OA\Property(property="total_questions", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Assessment not found")
     * )
     */
    public function update(int $id, UpdateAssessmentRequest $request): JsonResponse
    {
        $assessment = Assessment::find($id);

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found',
            ], 404);
        }

        $this->authorize('update', $assessment);

        try {
            $assessment = $this->assessmentService->updateAssessment(
                $assessment,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Assessment updated successfully',
                'data' => new AssessmentResource($assessment),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/assessments/{id}",
     *     summary="Delete assessment",
     *     description="Delete an assessment permanently including all related questions and options. This endpoint removes the assessment from the system for admin and guru roles.",
     *     tags={"Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Assessment ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assessment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Assessment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Delete error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Assessment not found")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $assessment = Assessment::find($id);

        if (!$assessment) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found',
            ], 404);
        }

        $this->authorize('delete', $assessment);

        try {
            $this->assessmentService->deleteAssessment($assessment);

            return response()->json([
                'success' => true,
                'message' => 'Assessment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
