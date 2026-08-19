<?php

namespace App\Http\Controllers;

use App\DTOs\CreateUserDto;
use App\DTOs\UpdateUserDto;
use App\Enums\UserRole;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    /**
     * @OA\Get(
     *     path="/auth/user",
     *     summary="List users with pagination",
     *     description="Retrieve paginated list of users with optional search and sorting. Accessible to all authenticated users.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="Page size (default: 15)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="pagenumber",
     *         in="query",
     *         description="Page number (default: 1)",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         description="Search query (searches name, email, username)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sortby",
     *         in="query",
     *         description="Sort field (default: created_at)",
     *         @OA\Schema(type="string", enum={"created_at","name","email"}, default="created_at")
     *     ),
     *     @OA\Parameter(
     *         name="sorttype",
     *         in="query",
     *         description="Sort direction (default: desc)",
     *         @OA\Schema(type="string", enum={"asc","desc"}, default="desc")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
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
        try {
            $pageSize = (int)$request->get('pagesize', 15);
            $pageNumber = (int)$request->get('pagenumber', 1);
            $search = $request->get('q', null);
            $sortBy = $request->get('sortby', 'created_at');
            $sortType = $request->get('sorttype', 'desc');

            $users = $this->userService->getAllUsersWithPagination(
                $search,
                $sortBy,
                $sortType,
                $pageSize,
                $pageNumber
            );

            return $this->paginatedResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/user/all",
     *     summary="Get all users without pagination",
     *     description="Retrieve all users without pagination. Only accessible to admin and guru roles.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="All users retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="All users retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - only admin and guru")
     * )
     */
    public function getAllUsers(): JsonResponse
    {
        try {
            // Authorization check: Only admin and guru can view all users
            if (!in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::GURU])) {
                return $this->errorResponse('Forbidden: Only admin and guru can view all users', 403);
            }

            $users = $this->userService->getAllUsers();
            return $this->successResponse(
                UserResource::collection($users),
                'All users retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/user/{id}",
     *     summary="Get user by ID",
     *     description="Retrieve detailed information of a specific user by ID.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);
            return $this->successResponse(
                new UserResource($user),
                'User retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/user/",
     *     summary="Create new user",
     *     description="Create a new user account. Only accessible to admin and guru roles. Supports profile photo upload.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data with optional profile photo",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","email","username","password","role"},
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="jane@example.com"),
     *                 @OA\Property(property="username", type="string", example="janedoe"),
     *                 @OA\Property(property="password", type="string", format="password", example="password123"),
     *                 @OA\Property(property="role", type="string", enum={"siswa","guru","admin"}, example="siswa"),
     *                 @OA\Property(property="profile_photo", type="string", format="binary", description="Optional profile photo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="role", type="string"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - only admin and guru")
     * )
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            // Authorization check: Only admin and guru can create users
            if (!in_array($request->user()->role, [UserRole::ADMIN, UserRole::GURU])) {
                return $this->errorResponse('Forbidden: Only admin and guru can create users', 403);
            }

            $dto = CreateUserDto::fromArray($request->validated());

            // Handle profile photo upload
            $profilePhoto = $request->file('profile_photo');
            $user = $this->userService->createUser($dto, $profilePhoto);
            return $this->createdResponse(
                new UserResource($user),
                'User created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/user/{id}",
     *     summary="Update user",
     *     description="Update user information. Use POST when uploading files/form-data. Supports partial updates via PATCH/PUT with JSON body.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated user data",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", description="User full name"),
     *                 @OA\Property(property="email", type="string", format="email", description="User email"),
     *                 @OA\Property(property="username", type="string", description="Username"),
     *                 @OA\Property(property="role", type="string", enum={"siswa","guru","admin"}, description="User role"),
     *                 @OA\Property(property="is_active", type="boolean", description="Active status"),
     *                 @OA\Property(property="profile_photo", type="string", format="binary", description="Optional profile photo")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function update(UpdateUserRequest $request, $user): JsonResponse
    {
        try {
            $userId = is_object($user) ? $user->id : $user;
            $validated = $request->validated();

            \Log::info('[UserController] Update request - DETAILED', [
                'user_id' => $userId,
                'validated_data' => $validated,
                'all_input' => $request->all(),
                'all_files' => $request->allFiles(),
                'has_profile_photo' => $request->hasFile('profile_photo'),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'validated_keys' => array_keys($validated),
            ]);

            $dto = UpdateUserDto::fromArray($validated);

            // If validated is empty but we have input, use all() to get form data
            if (empty($validated) && !empty($request->all())) {
                \Log::warning('[UserController] Validated empty but has input, using all()', [
                    'all' => $request->all(),
                ]);
                $dto = UpdateUserDto::fromArray($request->all());
            }

            // Handle profile photo upload
            $profilePhoto = $request->file('profile_photo');
            if ($profilePhoto) {
                \Log::info('[UserController] Photo file detected', [
                    'filename' => $profilePhoto->getClientOriginalName(),
                    'size' => $profilePhoto->getSize(),
                ]);
            }

            $this->userService->updateUser($userId, $dto, $profilePhoto);
            $updatedUser = $this->userService->getUserById($userId);

            return $this->successResponse(
                new UserResource($updatedUser),
                'User updated successfully'
            );
        } catch (\Exception $e) {
            \Log::error('[UserController] Update failed: ' . $e->getMessage());
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * @OA\Delete(
     *     path="/auth/user/{id}",
     *     summary="Delete user",
     *     description="Delete a user account permanently. Only accessible to admin and guru roles.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - only admin and guru"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Authorization check: Only admin and guru can delete users
            if (!in_array(auth()->user()->role, [UserRole::ADMIN, UserRole::GURU])) {
                return $this->errorResponse('Forbidden: Only admin and guru can delete users', 403);
            }

            $this->userService->deleteUser($id);
            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->notFoundResponse('User not found');
        }
    }

    /**
     * @OA\Put(
     *     path="/auth/user/update-password/{id}",
     *     summary="Update user password",
     *     description="Update user password. Users can only change their own password, while admin can change any user's password.",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Current and new password",
     *         @OA\JsonContent(
     *             required={"old_password","password","password_confirmation"},
     *             @OA\Property(property="old_password", type="string", format="password", example="oldpassword123", description="Current password"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword456", description="New password (min 8 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword456", description="New password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Password updated successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error - old password incorrect or new password too short"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - only admin or the user themselves can change password")
     * )
     */
    public function updatePassword(Request $request, $id): JsonResponse
    {
        try {
            // Authorization check: Users can only change their own password, admin can change any
            if (auth()->user()->role !== UserRole::ADMIN && auth()->user()->id != $id) {
                return $this->errorResponse('Forbidden: You can only change your own password', 403);
            }

            $request->validate([
                'old_password' => 'required|string|min:8',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $this->userService->updatePassword(
                $id,
                $request->get('old_password'),
                $request->get('password')
            );

            return $this->successResponse(null, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Login user endpoint (documented in AuthController)
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        try {
            $result = $this->userService->loginUser(
                $request->validated()['email'],
                $request->validated()['password']
            );

            return $this->successResponse([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }
}
