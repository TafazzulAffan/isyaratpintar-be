<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseSubmissionRequest;
use App\Http\Requests\UpdateCaseSubmissionRequest;
use App\Http\Resources\CaseSubmissionResource;
use App\Models\CaseSubmission;
use App\Models\PblCase;
use App\Models\UserCaseProgress;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="PBL Submissions - Student",
 *     description="Student endpoints for submitting and viewing personal submissions"
 * )
 * @OA\Tag(
 *     name="PBL Submissions - Admin/Guru",
 *     description="Admin/Guru endpoints for viewing all student submissions and grading"
 * )
 * @OA\Tag(
 *     name="PBL Submissions - Debug",
 *     description="Debug endpoints for troubleshooting submission data issues"
 * )
 */
class CaseSubmissionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/pbl-submissions",
     *     operationId="submitCaseAnswer",
     *     tags={"PBL Submissions - Student"},
     *     summary="Submit answer for a PBL case",
     *     description="Submit an answer to a PBL case with optional text and/or file upload. Students can only submit once per case. Both text answer and file submission are optional but at least one must be provided.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Case submission with answer text and/or file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"case_id"},
     *                 @OA\Property(property="case_id", type="integer", example=1, description="The PBL case ID to submit answer for"),
     *                 @OA\Property(property="answer", type="string", example="This is my detailed answer to the case...", description="Answer text (minimum 10 characters, optional)"),
     *                 @OA\Property(
     *                     property="submission_file",
     *                     description="Submission file (any format accepted - PDF, DOC, images, etc.)",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Answer submitted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Answer submitted successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="case_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="answer", type="string", nullable=true),
     *                 @OA\Property(property="submission_file", type="string", nullable=true),
     *                 @OA\Property(property="submission_file_path", type="string", nullable=true),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                 @OA\Property(property="score", type="number", nullable=true),
     *                 @OA\Property(property="feedback", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation failed - already submitted or case not available"
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     * )
     */
    public function store(StoreCaseSubmissionRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $validated = $request->validated();
            
            $case = PblCase::findOrFail($validated['case_id']);
            $this->authorize('view', $case);

            // Check if already submitted
            $existingSubmission = CaseSubmission::where('user_id', $user->id)
                ->where('case_id', $case->id)
                ->first();

            if ($existingSubmission) {
                return response()->json([
                    'message' => 'You have already submitted an answer for this case',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Create user progress if not exists
            $progress = UserCaseProgress::firstOrCreate(
                ['user_id' => $user->id, 'case_id' => $case->id],
                ['started_at' => Carbon::now()]
            );

            // Create submission
            $submissionData = [
                'user_id' => $user->id,
                'case_id' => $case->id,
                'submitted_at' => Carbon::now(),
            ];

            // Add answer if provided
            if (!empty($validated['answer'])) {
                $submissionData['answer'] = $validated['answer'];
            }

            // Handle file upload
            if ($request->hasFile('submission_file')) {
                $file = $request->file('submission_file');
                $filename = time() . '_' . $user->id . '_' . \Str::random(8) . '.' . $file->getClientOriginalExtension();
                
                $filePath = Storage::disk('public')
                    ->putFileAs('submissions/pbl', $file, $filename);
                
                $submissionData['submission_file'] = $filePath;
            }

            $submission = CaseSubmission::create($submissionData);

            // Update progress as completed
            $progress->update(['completed_at' => Carbon::now()]);

            return response()->json([
                'message' => 'Answer submitted successfully',
                'data' => new CaseSubmissionResource($submission),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Store submission error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to submit answer: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/pbl-submissions",
     *     operationId="getAllSubmissions",
     *     tags={"PBL Submissions - Admin/Guru"},
     *     summary="Get all student submissions (admin/guru only)",
     *     description="Retrieve all PBL case submissions from all students. Only administrators and teachers (guru) can access this endpoint. Results are paginated (15 items per page) and sorted by submission date (newest first).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all submissions successfully retrieved (paginated, 15 per page, sorted by submitted_at DESC)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 items=@OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="case_id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=5, description="Student ID"),
     *                     @OA\Property(property="answer", type="string", nullable=true, example="My detailed answer..."),
     *                     @OA\Property(property="submission_file", type="string", nullable=true, format="uri"),
     *                     @OA\Property(property="submission_file_path", type="string", nullable=true),
     *                     @OA\Property(property="score", type="number", nullable=true, example=85.50),
     *                     @OA\Property(property="feedback", type="string", nullable=true),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", format="uri"),
     *                 @OA\Property(property="last", type="string", format="uri"),
     *                 @OA\Property(property="prev", type="string", format="uri", nullable=true),
     *                 @OA\Property(property="next", type="string", format="uri", nullable=true),
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer", nullable=true),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer", nullable=true),
     *                 @OA\Property(property="total", type="integer"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - only admin and guru can view all submissions"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated - no valid bearer token provided"),
     * )
     */
    public function getUserSubmissions(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            // Check authorization - only admin and guru can view all submissions
            if (!$user || !$user->isAdminOrGuru()) {
                return response()->json([
                    'message' => 'Only admin and guru can view all submissions',
                ], Response::HTTP_FORBIDDEN);
            }

            // Get all submissions (not filtered by user_id) with proper eager loading
            $query = CaseSubmission::with('pblCase');

            if (request()->has('pbl_case_id')) {
                $query->where('case_id', request('pbl_case_id'));
            }

            $submissions = $query->orderBy('submitted_at', 'desc')
                ->paginate(15);

            return response()->json(CaseSubmissionResource::collection($submissions));
        } catch (\Exception $e) {
            Log::error('Get submissions error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve submissions',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

/**
 * @OA\Get(
 *     path="/pbl-submissions/me",
 *     operationId="getMySubmissions",
 *     tags={"PBL Submissions - Student"},
 *     summary="Get my submissions",
 *     description="Retrieve all PBL case submissions belonging to the currently authenticated user.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Submissions retrieved successfully",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="case_id", type="integer"),
 *                     @OA\Property(property="user_id", type="integer"),
 *                     @OA\Property(property="answer", type="string", nullable=true),
 *                     @OA\Property(property="submission_file", type="string", nullable=true),
 *                     @OA\Property(property="submitted_at", type="string", format="date-time"),
 *                     @OA\Property(property="score", type="number", nullable=true),
 *                     @OA\Property(property="feedback", type="string", nullable=true),
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, description="Unauthenticated"),
 * )
 */
    public function mySubmissions(): JsonResponse
    {
        try {
            $submissions = CaseSubmission::with('pblCase')
                ->where('user_id', auth()->id())
                ->orderBy('submitted_at', 'desc')
                ->get();

            return response()->json(CaseSubmissionResource::collection($submissions));
        } catch (\Exception $e) {
            Log::error('Get my submissions error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve submissions',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/pbl-submissions/{id}/grade",
     *     operationId="gradeSubmission",
     *     tags={"PBL Submissions - Grading"},
     *     summary="Grade a student's PBL submission",
     *     description="Assign a score (0-100) and provide feedback to a student's PBL case submission. Only administrators and teachers (guru) can perform this action.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Submission ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Grading data with score and feedback",
     *         @OA\JsonContent(
     *             required={"score", "feedback"},
     *             @OA\Property(property="score", type="number", format="float", example=85.50, description="Numerical score from 0 to 100 (with decimals supported)"),
     *             @OA\Property(property="feedback", type="string", example="Good analysis of the case. Consider discussing alternative approaches.", description="Feedback message for the student (minimum 5 characters)"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission graded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Submission graded successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="case_id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="answer", type="string", nullable=true),
     *                 @OA\Property(property="submission_file", type="string", nullable=true, format="uri"),
     *                 @OA\Property(property="submission_file_path", type="string", nullable=true),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                 @OA\Property(property="score", type="number", nullable=true),
     *                 @OA\Property(property="feedback", type="string", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Submission not found"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - only administrators and teachers can grade submissions"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - invalid score or feedback format"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     * )
     */
    public function grade(UpdateCaseSubmissionRequest $request, CaseSubmission $caseSubmission): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check authorization - only admin and guru can grade
            if (!$user || !$user->isAdminOrGuru()) {
                return response()->json([
                    'message' => 'Only admin and guru can grade submissions',
                ], Response::HTTP_FORBIDDEN);
            }

            $validated = $request->validated();

            // Ensure at least one field is provided
            if (!isset($validated['score']) && !isset($validated['feedback'])) {
                return response()->json([
                    'message' => 'At least score or feedback must be provided',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Update directly (bypass mass assignment issues)
            if (array_key_exists('score', $validated)) {
                $caseSubmission->score = $validated['score'];
            }
            if (array_key_exists('feedback', $validated)) {
                $caseSubmission->feedback = $validated['feedback'];
            }

            $saved = $caseSubmission->save();

            if (!$saved) {
                Log::error('Grade save failed', [
                    'submission_id' => $caseSubmission->id,
                    'validated' => $validated
                ]);
                return response()->json([
                    'message' => 'Failed to save grading. Please try again.',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            // If a score was given/updated, trigger SPK recalculation for this subject
            if (array_key_exists('score', $validated)) {
                dispatch(new \App\Jobs\CalculateSubjectMasteryFromTaskJob($caseSubmission->id))
                    ->onQueue('default');
            }

            $caseSubmission->refresh();

            return response()->json([
                'message' => 'Submission graded successfully',
                'data' => new CaseSubmissionResource($caseSubmission->load('pblCase')),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Grade error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/pbl-submissions/debug/list",
     *     operationId="debugSubmissionsList",
     *     tags={"PBL Submissions - Debug"},
     *     summary="[DEBUG] Get all my submissions without pagination",
     *     description="DEBUG ENDPOINT ONLY - Retrieve raw unfiltered list of all submissions for the current user without pagination. Useful for debugging data availability issues. Shows submission count stats.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All submissions for current user (unfiltered, no pagination)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Debug Info"),
     *             @OA\Property(property="user_id", type="integer", example=1, description="Current authenticated user ID"),
     *             @OA\Property(property="user_name", type="string", example="John Doe", description="Current authenticated user name"),
     *             @OA\Property(property="total_submissions_in_db", type="integer", example=42, description="Total submissions across all users"),
     *             @OA\Property(property="total_user_submissions", type="integer", example=5, description="Total submissions for current user"),
     *             @OA\Property(
     *                 property="all_submissions",
     *                 type="array",
     *                 items=@OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="case_id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="answer", type="string", nullable=true),
     *                     @OA\Property(property="submission_file", type="string", nullable=true),
     *                     @OA\Property(property="score", type="number", nullable=true),
     *                     @OA\Property(property="feedback", type="string", nullable=true),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 ),
     *                 description="All submissions belonging to the current user (unfiltered)"
     *             ),
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     * )
     */
    public function debugList(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            return response()->json([
                'message' => 'Debug Info',
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'total_submissions_in_db' => CaseSubmission::count(),
                'total_user_submissions' => CaseSubmission::where('user_id', $user?->id)->count(),
                'all_submissions' => CaseSubmissionResource::collection(
                    CaseSubmission::where('user_id', $user?->id)
                        ->with('pblCase')
                        ->get()
                ),
            ]);
        } catch (\Exception $e) {
            Log::error('Debug list error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Debug error',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}