<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetLessonsByMataPelajaranRequest;
use App\Http\Requests\StoreLessonRequest;
use App\Http\Requests\UpdateLessonRequest;
use App\Http\Requests\UpdateLessonResumeRequest;
use App\Http\Requests\UploadFileRequest;
use App\Http\Resources\LessonResource;
use App\Models\Lesson;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{
    public function __construct(
        private LessonService $lessonService
    ) {}

    /**
     * @OA\Get(
     *     path="/lessons",
     *     summary="Get paginated lessons",
     *     description="Retrieve paginated list of lessons for authenticated user.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="Page size (default: 15)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated lessons",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lessons retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="level", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $pageSize = (int) $request->get('pagesize', 15);
        $pageSize = $pageSize > 0 ? $pageSize : 15;

        $lessons = $this->lessonService->getPaginatedLessons($request->user(), $pageSize);

        $lessons->setCollection(
            collect(LessonResource::collection($lessons->getCollection())->resolve())
        );

        return $this->paginatedResponse($lessons, 'Lessons retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/lessons/all",
     *     summary="Get all lessons without pagination",
     *     description="Retrieve all available lessons for authenticated user without pagination.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All lessons",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All lessons retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAll(Request $request): JsonResponse
    {
        $lessons = $this->lessonService->getAllLessons($request->user());

        return $this->successResponse(
            LessonResource::collection($lessons),
            'All lessons retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/lessons/{slug}",
     *     summary="Get lesson by slug",
     *     description="Retrieve detailed lesson information by slug.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Lesson slug"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $lesson = $this->lessonService->getLessonBySlug($slug, $request->user());

        return $this->successResponse(
            new LessonResource($lesson),
            'Lesson retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/lessons/by-mata-pelajaran/{mataPelajaran}",
     *     summary="Get lessons by mata pelajaran",
     *     description="Retrieve all lessons for a specific mata pelajaran (subject).",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="mataPelajaran",
     *         in="path",
     *         required=true,
     *         description="Mata pelajaran number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lessons by mata pelajaran",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lessons by mata pelajaran retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Mata pelajaran not found")
     * )
     */
    public function byMataPelajaran(GetLessonsByMataPelajaranRequest $request, int $mataPelajaran): JsonResponse
    {
        $lessons = $this->lessonService->getLessonsByMataPelajaranId($mataPelajaran, $request->user());

        return $this->successResponse(
            LessonResource::collection($lessons),
            'Lessons by mata pelajaran retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/lessons/completed",
     *     summary="Get completed lessons",
     *     description="Retrieve all lessons completed by the authenticated user.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Completed lessons",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Completed lessons retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getCompleted(Request $request): JsonResponse
    {
        $lessons = $this->lessonService->getCompletedLessons($request->user());

        return $this->successResponse(
            LessonResource::collection($lessons),
            'Completed lessons retrieved successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/lessons",
     *     summary="Create new lesson",
     *     description="Create a new lesson. Only accessible to admin and guru roles. Use POST /lessons/{slug}/upload-file to upload lesson materials after creation.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","mata_pelajaran_id","kelas_id"},
     *             @OA\Property(property="title", type="string", description="Lesson title"),
     *             @OA\Property(property="description", type="string", description="Lesson description"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer", description="Mata Pelajaran ID"),
     *             @OA\Property(property="kelas_id", type="integer", description="Kelas ID"),
     *             @OA\Property(property="duration", type="string", description="Lesson duration (minutes)"),
     *             @OA\Property(property="resume", type="string", description="Lesson resume/summary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lesson created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StoreLessonRequest $request): JsonResponse
    {
        $this->authorize('create', Lesson::class);

        $lesson = $this->lessonService->createLesson($request->validated());

        return $this->createdResponse(
            new LessonResource($lesson),
            'Lesson created successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/lessons/{slug}",
     *     summary="Update lesson",
     *     description="Update an existing lesson. Only accessible to admin and guru roles. Use POST /lessons/{slug}/upload-file to upload/update lesson materials.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Lesson slug"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", description="Updated title"),
     *             @OA\Property(property="description", type="string", description="Updated description"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer", description="Mata Pelajaran ID"),
     *             @OA\Property(property="kelas_id", type="integer", description="Kelas ID"),
     *             @OA\Property(property="duration", type="string", description="Duration in minutes"),
     *             @OA\Property(property="resume", type="string", description="Updated resume/summary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function update(UpdateLessonRequest $request, string $slug): JsonResponse
    {
        $lesson = Lesson::query()->where('slug', $slug)->firstOrFail();
        $this->authorize('update', $lesson);

        $lesson = $this->lessonService->updateLessonBySlug($slug, $request->validated(), $request->user());

        return $this->successResponse(
            new LessonResource($lesson),
            'Lesson updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/lessons/{slug}",
     *     summary="Delete lesson",
     *     description="Delete a lesson permanently. Only accessible to admin and guru roles.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Lesson slug"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lesson deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Lesson deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function destroy(string $slug): JsonResponse
    {
        $lesson = Lesson::query()->where('slug', $slug)->firstOrFail();
        $this->authorize('delete', $lesson);

        $this->lessonService->deleteLessonBySlug($slug);

        return $this->successResponse(null, 'Lesson deleted successfully');
    }

    /**
     * @OA\Put(
     *     path="/lessons/{slug}/resume",
     *     operationId="updateLessonResume",
     *     summary="Update or add resume for a lesson",
     *     description="Update or add resume text for a lesson. Resume must contain at least 300 words. Only accessible to admin and guru roles.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Lesson slug"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Resume text",
     *         @OA\JsonContent(
     *             required={"resume"},
     *             @OA\Property(
     *                 property="resume",
     *                 type="string",
     *                 description="Resume text (minimum 300 words)",
     *                 example="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum."
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resume updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Resume updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="lesson_id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="resume", type="string"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error - Resume must be at least 300 words"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function updateResume(UpdateLessonResumeRequest $request, string $slug): JsonResponse
    {
        try {
            $lesson = $this->lessonService->getLessonBySlug($slug, $request->user());
            
            if (!$lesson) {
                return $this->notFoundResponse('Lesson not found');
            }

            // Update lesson resume
            $lesson->update([
                'resume' => $request->validated('resume')
            ]);

            return $this->successResponse([
                'lesson_id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'resume' => $lesson->resume,
                'updated_at' => $lesson->updated_at,
            ], 'Resume updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update resume: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/lessons/{slug}/upload-file",
     *     operationId="uploadLessonFile",
     *     summary="Upload or update file for a lesson",
     *     description="Upload or update a file for a lesson. Accepts any file format (PDF, DOC, DOCX, PPT, XLS, ZIP, etc). Max file size: 100MB. Only accessible to admin and guru roles.",
     *     tags={"Lessons"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Lesson slug"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="File to upload",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     description="File to upload (any format - PDF, DOC, DOCX, PPT, XLS, ZIP, etc)",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File uploaded successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="lesson_id", type="integer"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="file_url", type="string", example="http://localhost:8000/file/filename.pdf"),
     *                 @OA\Property(property="file_size", type="integer", example=1048576),
     *                 @OA\Property(property="file_type", type="string", example="pdf"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error - File required or too large"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Lesson not found")
     * )
     */
    public function uploadFile(UploadFileRequest $request, string $slug): JsonResponse
    {
        try {
            $lesson = $this->lessonService->getLessonBySlug($slug, $request->user());
            
            if (!$lesson) {
                return $this->notFoundResponse('Lesson not found');
            }

            $file = $request->file('file');
            
            // Generate unique filename with original extension
            $filename = time() . '_' . \Str::random(8) . '.' . $file->getClientOriginalExtension();
            
            // Store file
            $path = Storage::disk('public')
                ->putFileAs('lessons', $file, $filename);

            // Update lesson file_url
            $lesson->update([
                'file_url' => '/storage/' . $path
            ]);

            return $this->successResponse([
                'lesson_id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'file_url' => $lesson->file_url,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientOriginalExtension(),
                'updated_at' => $lesson->updated_at,
            ], 'File uploaded successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload file: ' . $e->getMessage(), 500);
        }
    }
}
