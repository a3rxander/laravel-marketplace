<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        
        $users = $this->userService->getUsers($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $users,
            'message' => 'Users retrieved successfully'
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('view', $user);
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User retrieved successfully'
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        
        $user = $this->userService->createUser($request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'User created successfully'
        ], 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('update', $user);
        
        $updatedUser = $this->userService->updateUser($id, $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $updatedUser,
            'message' => 'User updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('delete', $user);
        
        $this->userService->deleteUser($id);
        
        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function profile(): JsonResponse
    {
        $user = $this->userService->getUserProfile(auth()->id());
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Profile retrieved successfully'
        ]);
    }

    public function updateProfile(UpdateUserRequest $request): JsonResponse
    {
        $user = $this->userService->updateUser(auth()->id(), $request->validated());
        
        return response()->json([
            'success' => true,
            'data' => $user,
            'message' => 'Profile updated successfully'
        ]);
    }
}