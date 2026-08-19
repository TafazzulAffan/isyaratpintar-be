<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddKelasSiswaRequest;
use App\Http\Requests\StoreKelasRequest;
use App\Http\Requests\UpdateKelasRequest;
use App\Http\Resources\AssessmentResource;
use App\Http\Resources\KelasResource;
use App\Http\Resources\LessonResource;
use App\Http\Resources\PblCaseResource;
use App\Models\Kelas;
use App\Services\KelasService;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Kelas",
 *     description="Manage Kelas (Class) resources"
 * )
 */
class KelasController extends Controller
{
    public function __construct(
        private KelasService $kelasService,
        private LessonService $lessonService,
    ) {}

    /**
     * @OA\Get(
     *     path="/kelas",
     *     operationId="getKelasList",
     *     tags={"Kelas"},
     *     summary="Get list of Kelas",
     *     description="Retrieve paginated list of Kelas for the authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="Number of items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kelas retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Kelas")),
     *             @OA\Property(property="pagination", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Kelas::class);

        $pageSize = max(1, (int) $request->get('pagesize', 15));
        $kelas = $this->kelasService->getKelasForUser($request->user(), $pageSize);

        $kelas->setCollection(
            collect(KelasResource::collection($kelas->getCollection())->resolve())
        );

        return $this->paginatedResponse($kelas, 'Kelas retrieved successfully');
    }

    /**
     * @OA\Post(
     *     path="/kelas",
     *     operationId="storeKelas",
     *     tags={"Kelas"},
     *     summary="Create a new Kelas",
     *     description="Create a new Kelas (Admin/Guru only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama"},
     *             @OA\Property(property="nama", type="string", example="Kelas 10 A")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Kelas created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kelas created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Kelas")
     *         )
     *     )
     * )
     */
    public function store(StoreKelasRequest $request): JsonResponse
    {
        $this->authorize('create', Kelas::class);

        $kelas = $this->kelasService->createKelas($request->user(), $request->validated());

        return $this->successResponse(
            new KelasResource($kelas),
            'Kelas created successfully',
            201
        );
    }

    /**
     * @OA\Get(
     *     path="/kelas/{kelas}",
     *     operationId="getKelasById",
     *     tags={"Kelas"},
     *     summary="Get Kelas details",
     *     description="Retrieve details of a specific Kelas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kelas retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Kelas")
     *         )
     *     )
     * )
     */
    public function show(Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        return $this->successResponse(
            new KelasResource($this->kelasService->getKelasById($kelas)),
            'Kelas retrieved successfully'
        );
    }

    /**
     * @OA\Put(
     *     path="/kelas/{kelas}",
     *     operationId="updateKelas",
     *     tags={"Kelas"},
     *     summary="Update a Kelas",
     *     description="Update details of an existing Kelas (Admin/Guru only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama", type="string", example="Kelas 10 A Updated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kelas updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kelas updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Kelas")
     *         )
     *     )
     * )
     */
    public function update(UpdateKelasRequest $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('update', $kelas);

        $updated = $this->kelasService->updateKelas($kelas, $request->user(), $request->validated());

        return $this->successResponse(
            new KelasResource($updated),
            'Kelas updated successfully'
        );
    }

    /**
     * @OA\Delete(
     *     path="/kelas/{kelas}",
     *     operationId="deleteKelas",
     *     tags={"Kelas"},
     *     summary="Delete a Kelas",
     *     description="Delete an existing Kelas (Admin/Guru only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kelas deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kelas deleted successfully")
     *         )
     *     )
     * )
     */
    public function destroy(Kelas $kelas): JsonResponse
    {
        $this->authorize('delete', $kelas);

        $this->kelasService->deleteKelas($kelas);

        return $this->successResponse(null, 'Kelas deleted successfully');
    }

    /**
     * @OA\Post(
     *     path="/kelas/{kelas}/siswa",
     *     operationId="addStudentToKelas",
     *     tags={"Kelas"},
     *     summary="Add one or more students to Kelas",
     *     description="Add multiple students at once to a specific Kelas (Admin/Guru only). Students already enrolled will be silently skipped.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_ids"},
     *             @OA\Property(
     *                 property="user_ids",
     *                 type="array",
     *                 description="Array of student user IDs to enroll",
     *                 @OA\Items(type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Students added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="3 siswa berhasil ditambahkan, 1 siswa sudah terdaftar (dilewati)."),
     *             @OA\Property(property="data", ref="#/components/schemas/Kelas")
     *         )
     *     )
     * )
     */
    public function addStudent(AddKelasSiswaRequest $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('manageStudents', $kelas);

        $result = $this->kelasService->addStudents($kelas, $request->validated('user_ids'));

        $message = "{$result['added']} siswa berhasil ditambahkan";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} siswa sudah terdaftar (dilewati)";
        }
        $message .= '.';

        return $this->successResponse(
            new KelasResource($result['kelas']),
            $message
        );
    }

    /**
     * @OA\Delete(
     *     path="/kelas/{kelas}/siswa/{user}",
     *     operationId="removeStudentFromKelas",
     *     tags={"Kelas"},
     *     summary="Remove a student from Kelas",
     *     description="Remove a student from a specific Kelas (Admin/Guru only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="user",
     *         in="path",
     *         required=true,
     *         description="ID of the User (Siswa)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Siswa berhasil dihapus dari kelas"),
     *             @OA\Property(property="data", ref="#/components/schemas/Kelas")
     *         )
     *     )
     * )
     */
    public function removeStudent(Kelas $kelas, int $user): JsonResponse
    {
        $this->authorize('manageStudents', $kelas);

        $updated = $this->kelasService->removeStudent($kelas, $user);

        return $this->successResponse(
            new KelasResource($updated),
            'Siswa berhasil dihapus dari kelas'
        );
    }

    /**
     * @OA\Get(
     *     path="/kelas/{kelas}/materi",
     *     operationId="getKelasMateri",
     *     tags={"Kelas"},
     *     summary="Get Materi for Kelas",
     *     description="Retrieve all Lesson/Materi assigned to a specific Kelas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Materi kelas retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Lesson"))
     *         )
     *     )
     * )
     */
    public function materi(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $lessons = $this->lessonService->getLessonsByKelasId($kelas->id, $request->user());

        return $this->successResponse(
            LessonResource::collection($lessons)->resolve(),
            'Materi kelas retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/kelas/{kelas}/tugas",
     *     operationId="getKelasTugas",
     *     tags={"Kelas"},
     *     summary="Get Tugas/PblCases for Kelas",
     *     description="Retrieve all Tugas/PBL Cases assigned to a specific Kelas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tugas kelas retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PblCase"))
     *         )
     *     )
     * )
     */
    public function tugas(Request $request, Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $cases = $kelas->pblCases()
            ->with('mataPelajaran')
            ->orderBy('start_date', 'asc')
            ->get();

        $user = $request->user();

        return $this->successResponse(
            $cases->map(fn ($case) => (new PblCaseResource($case))->setUser($user))->values()->all(),
            'Tugas kelas retrieved successfully'
        );
    }

    /**
     * @OA\Get(
     *     path="/kelas/{kelas}/ujian",
     *     operationId="getKelasUjian",
     *     tags={"Kelas"},
     *     summary="Get Ujian/Assessments for Kelas",
     *     description="Retrieve all Ujian/Assessments assigned to a specific Kelas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="kelas",
     *         in="path",
     *         required=true,
     *         description="ID of the Kelas",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ujian kelas retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Assessment"))
     *         )
     *     )
     * )
     */
    public function ujian(Kelas $kelas): JsonResponse
    {
        $this->authorize('view', $kelas);

        $assessments = $kelas->assessments()
            ->withCount('questions')
            ->latest()
            ->get();

        return $this->successResponse(
            AssessmentResource::collection($assessments)->resolve(),
            'Ujian kelas retrieved successfully'
        );
    }
}
