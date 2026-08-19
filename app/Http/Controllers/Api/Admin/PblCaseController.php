<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePblCaseRequest;
use App\Http\Requests\UpdatePblCaseRequest;
use App\Http\Resources\PblCaseDetailResource;
use App\Http\Resources\PblCaseResource;
use App\Models\PblCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="PBL Cases",
 *     description="Manage PBL cases (GET for all users, CRUD for Admin/Guru only)"
 * )
 */
class PblCaseController extends Controller
{
    /**
     * @OA\Post(
     *     path="/pbl-cases",
     *     operationId="createPblCase",
     *     tags={"PBL Cases"},
     *     summary="Create a new PBL case (Admin/Guru only)",
     *     description="Create a new PBL case with all details. Only accessible to Admin and Guru roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="PBL case data",
     *         @OA\JsonContent(
     *             required={"case_number","title","mata_pelajaran_id","kelas_id","description","start_date","deadline"},
     *             @OA\Property(property="case_number", type="integer", example=1),
     *             @OA\Property(property="title", type="string", example="System Login Bermasalah"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer", example=1),
     *             @OA\Property(property="kelas_id", type="integer", example=1),
     *             @OA\Property(property="description", type="string", example="Anda diminta untuk menyelesaikan..."),
     *             @OA\Property(property="time_limit", type="integer", example=120),
     *             @OA\Property(property="start_date", type="string", format="date-time"),
     *             @OA\Property(property="deadline", type="string", format="date-time"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="PBL case created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="slug", type="string"),
     *             @OA\Property(property="message", type="string"),
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Only Admin and Guru can create cases"),
     * )
     */
    public function store(StorePblCaseRequest $request): JsonResponse
    {
        $this->authorize('create', PblCase::class);

        $validated = $request->validated();
        
        // Generate slug from title
        $validated['slug'] = \Str::slug($validated['title']) . '-' . \Str::random(8);

        $case = PblCase::create($validated);

        return response()->json([
            'message' => 'PBL case created successfully',
            'data' => new PblCaseDetailResource($case->load('mataPelajaran', 'sections')),
        ], Response::HTTP_CREATED);
    }

    /**
     * @OA\Put(
     *     path="/pbl-cases/{pblCase}",
     *     operationId="updatePblCase",
     *     tags={"PBL Cases"},
     *     summary="Update a PBL case (Admin/Guru only)",
     *     description="Update an existing PBL case. Only accessible to Admin and Guru roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pblCase",
     *         in="path",
     *         required=true,
     *         description="PBL case ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="mata_pelajaran_id", type="integer"),
     *             @OA\Property(property="kelas_id", type="integer"),
     *             @OA\Property(property="deadline", type="string", format="date-time"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PBL case updated successfully"
     *     ),
     *     @OA\Response(response=404, description="PBL case not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Only Admin and Guru can update cases"),
     * )
     */
    public function update(UpdatePblCaseRequest $request, PblCase $pblCase): JsonResponse
    {
        $this->authorize('update', $pblCase);

        $pblCase->update($request->validated());

        return response()->json([
            'message' => 'PBL case updated successfully',
            'data' => new PblCaseDetailResource($pblCase->load('mataPelajaran', 'sections')),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/pbl-cases/{pblCase}",
     *     operationId="deletePblCase",
     *     tags={"PBL Cases"},
     *     summary="Delete a PBL case (Admin/Guru only)",
     *     description="Delete an existing PBL case. Only accessible to Admin and Guru roles.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pblCase",
     *         in="path",
     *         required=true,
     *         description="PBL case ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PBL case deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="PBL case not found"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Only Admin and Guru can delete cases"),
     * )
     */
    public function destroy(PblCase $pblCase): JsonResponse
    {
        $this->authorize('delete', $pblCase);

        $pblCase->delete();

        return response()->json([
            'message' => 'PBL case deleted successfully',
        ]);
    }
}
