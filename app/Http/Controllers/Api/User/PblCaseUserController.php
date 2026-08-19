<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\PblCaseDetailResource;
use App\Http\Resources\PblCaseResource;
use App\Models\PblCase;
use App\Services\KelasAccessService;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="PBL Cases",
 *     description="PBL case management (GET for all users, CRUD for Admin/Guru only)"
 * )
 */
class PblCaseUserController extends Controller
{
    public function __construct(
        private KelasAccessService $kelasAccessService
    ) {}
    /**
     * @OA\Get(
     *     path="/pbl-cases",
     *     operationId="listPblCases",
     *     tags={"PBL Cases"},
     *     summary="List all PBL cases",
     *     description="Retrieve all available PBL cases with current user's status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="mata_pelajaran_id",
     *         in="query",
     *         description="Filter by mata pelajaran ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of PBL cases",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", items=@OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="case_number", type="integer"),
     *                 @OA\Property(property="image_url", type="string"),
     *                 @OA\Property(property="time_limit", type="integer"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="deadline", type="string", format="date-time"),
     *                 @OA\Property(property="status", type="string", enum={"not-started","in-progress","completed","late"}),
     *                 @OA\Property(property="mata_pelajaran", type="object"),
     *             )),
     *             @OA\Property(property="pagination", type="object"),
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();
        
        $query = $this->kelasAccessService->scopeContentQuery(
            PblCase::with('mataPelajaran')->orderBy('start_date', 'asc'),
            $user
        );

        if (request()->has('mata_pelajaran_id')) {
            $query->where('mata_pelajaran_id', request()->input('mata_pelajaran_id'));
        }

        $cases = $query->paginate(15);

        // Add status to each case
        $cases->getCollection()->transform(function ($case) use ($user) {
            return (new PblCaseResource($case))->setUser($user);
        });

        return response()->json($cases);
    }

    /**
     * @OA\Get(
     *     path="/pbl-cases/{pblCase}",
     *     operationId="getPblCase",
     *     tags={"PBL Cases"},
     *     summary="Get PBL case details",
     *     description="Retrieve full details of a specific PBL case including all sections and items",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pblCase",
     *         in="path",
     *         required=true,
     *         description="Case ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detailed PBL case information",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="case_number", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="image_url", type="string"),
     *             @OA\Property(property="time_limit", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date-time"),
     *             @OA\Property(property="deadline", type="string", format="date-time"),
     *             @OA\Property(property="status", type="string", enum={"not-started","in-progress","completed","late"}),
     *             @OA\Property(property="sections", type="array", items=@OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="order", type="integer"),
     *                 @OA\Property(property="items", type="array", items=@OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="type", type="string", enum={"heading","text","list","image"}),
     *                     @OA\Property(property="content", type="string"),
     *                     @OA\Property(property="image_url", type="string"),
     *                     @OA\Property(property="order", type="integer"),
     *                 )),
     *             )),
     *         )
     *     ),
     *     @OA\Response(response=404, description="Case not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     */
    public function show(PblCase $pblCase): JsonResponse
    {
        $this->authorize('view', $pblCase);

        $pblCase->load('mataPelajaran', 'sections.items');
        return response()->json(new PblCaseDetailResource($pblCase));
    }

    /**
     * @OA\Get(
     *     path="/pbl-cases/mata-pelajaran/{id}",
     *     operationId="getPblCasesByMataPelajaran",
     *     tags={"PBL Cases"},
     *     summary="List PBL cases by mata pelajaran",
     *     description="Retrieve paginated PBL cases filtered by mata pelajaran ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Mata pelajaran ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of PBL cases filtered by mata pelajaran")
     * )
     */
    public function byMataPelajaran(int $id): JsonResponse
    {
        $user = auth()->user();

        $cases = $this->kelasAccessService
            ->scopeContentQuery(
                PblCase::with('mataPelajaran')->where('mata_pelajaran_id', $id)->orderBy('start_date', 'asc'),
                $user
            )
            ->paginate(15);

        $cases->getCollection()->transform(function ($case) use ($user) {
            return (new PblCaseResource($case))->setUser($user);
        });

        return response()->json($cases);
    }
}
