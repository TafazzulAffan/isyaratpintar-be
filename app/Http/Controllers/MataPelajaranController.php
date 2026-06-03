<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMataPelajaranRequest;
use App\Http\Requests\UpdateMataPelajaranRequest;
use App\Http\Resources\MataPelajaranResource;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    public function __construct(
        private LessonService $lessonService
    ) {}

    /**
     * @OA\Get(
     *     path="/mata-pelajarans",
     *     summary="Get paginated mata pelajarans",
     *     description="Retrieve paginated list of learning subjects (mata pelajaran).",
     *     tags={"MataPelajaran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="Page size (default: 15)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated mata pelajarans",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mata pelajarans retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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
        $mataPelajarans = $this->lessonService->getPaginatedMataPelajarans($request->user(), $pageSize);

        $mataPelajarans->setCollection(
            collect(MataPelajaranResource::collection($mataPelajarans->getCollection())->resolve())
        );

        return $this->paginatedResponse($mataPelajarans, 'Mata pelajarans retrieved successfully');
    }

    /**
     * @OA\Get(
     *     path="/mata-pelajarans/all",
     *     summary="Get all mata pelajarans with lessons",
     *     description="Retrieve all learning subjects with their associated lessons.",
     *     tags={"MataPelajaran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All mata pelajarans with lessons",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All mata pelajarans retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function getAll(Request $request): JsonResponse
    {
        $mataPelajarans = $this->lessonService->getMataPelajaransWithLessons($request->user());

        return $this->successResponse(
            MataPelajaranResource::collection($mataPelajarans),
            'All mata pelajarans retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/mata-pelajarans/{mataPelajaran}",
     *     summary="Get mata pelajaran by number",
     *     description="Retrieve detailed information of a specific mata pelajaran by level number.",
     *     tags={"MataPelajaran"},
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
     *         description="Mata pelajaran retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mata pelajaran retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Mata pelajaran not found")
     * )
     */
    public function show(Request $request, int $mataPelajaran): JsonResponse
    {
        $mataPelajaranData = $this->lessonService->getMataPelajaranById($mataPelajaran, $request->user());

        return $this->successResponse(
            new MataPelajaranResource($mataPelajaranData),
            'Mata pelajaran retrieved successfully'
        );
    }

    /**
     * @OA\Post(
     *     path="/mata-pelajarans",
     *     summary="Create new mata pelajaran",
     *     description="Create a new learning subject. Only accessible to admin and guru roles.",
     *     tags={"MataPelajaran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","level_number"},
     *             @OA\Property(property="name", type="string", description="Mata pelajaran name"),
     *             @OA\Property(property="level_number", type="integer", description="Mata pelajaran number"),
     *             @OA\Property(property="description", type="string", description="Mata pelajaran description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mata pelajaran created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mata pelajaran created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function store(StoreMataPelajaranRequest $request): JsonResponse
    {
        $mataPelajaran = $this->lessonService->createMataPelajaran($request->validated());

        return $this->createdResponse(
            new MataPelajaranResource($mataPelajaran),
            'Mata pelajaran created successfully'
        );
    }

    /**
     * @OA\Put(
     *     path="/mata-pelajarans/{mataPelajaran}",
     *     summary="Update mata pelajaran",
     *     description="Update an existing learning subject. Only accessible to admin and guru roles.",
     *     tags={"MataPelajaran"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="mataPelajaran",
     *         in="path",
     *         required=true,
     *         description="Mata pelajaran number",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", description="Updated name"),
     *             @OA\Property(property="description", type="string", description="Updated description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mata pelajaran updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mata pelajaran updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Mata pelajaran not found")
     * )
     */
    public function update(UpdateMataPelajaranRequest $request, int $mataPelajaran): JsonResponse
    {
        $updated = $this->lessonService->updateMataPelajaranById($mataPelajaran, $request->validated());

        return $this->successResponse(
            new MataPelajaranResource($updated),
            'Mata pelajaran updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/mata-pelajarans/{mataPelajaran}",
     *     summary="Delete mata pelajaran",
     *     description="Delete a learning subject permanently. Only accessible to admin and guru roles.",
     *     tags={"MataPelajaran"},
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
     *         description="Mata pelajaran deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mata pelajaran deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Mata pelajaran not found")
     * )
     */
    public function destroy(int $mataPelajaran): JsonResponse
    {
        $this->lessonService->deleteMataPelajaranById($mataPelajaran);

        return $this->successResponse(null, 'Mata pelajaran deleted successfully');
    }
}
