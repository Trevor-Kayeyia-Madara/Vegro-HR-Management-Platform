<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    protected function isProtectedAdmin(User $user): bool
    {
        $roleTitle = strtolower(trim((string) ($user->role?->title ?? '')));
        $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
        return in_array($roleTitle, ['superadmin', 'companyadmin'], true);
    }

    #[OA\Get(
        path: "/api/users",
        operationId: "listUsers",
        description: "Get all users",
        summary: "List all users",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Users retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Users retrieved successfully"),
                        new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: "id", type: "integer"),
                                    new OA\Property(property: "name", type: "string"),
                                    new OA\Property(property: "email", type: "string", format: "email"),
                                    new OA\Property(property: "role_id", type: "integer"),
                                    new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                    new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index()
    {
        $perPage = max((int) request()->query('per_page', 10), 1);
        return ApiResponse::success(User::with('role')->paginate($perPage), "Users retrieved successfully");
    }

    #[OA\Post(
        path: "/api/users",
        operationId: "createUser",
        description: "Create a new user",
        summary: "Create user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            description: "User data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "email", "password", "role_id"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Jane Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "jane.doe@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "role_id", type: "integer", example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "User created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);
        $user->load('role');

        return ApiResponse::success($user, "User created successfully", 201);
    }

    #[OA\Get(
        path: "/api/users/{user}",
        operationId: "showUser",
        description: "Get a specific user",
        summary: "Get user by ID",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                description: "User ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "User retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function show(User $user)
    {
        return ApiResponse::success($user->load('role'), "User retrieved successfully");
    }

    #[OA\Put(
        path: "/api/users/{user}",
        operationId: "updateUser",
        description: "Update a user",
        summary: "Update user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                description: "User ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            description: "User data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Jane Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "jane.doe@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "newpassword123", nullable: true),
                    new OA\Property(property: "role_id", type: "integer", example: 2)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "User updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function update(Request $request, User $user)
    {
        $user->loadMissing('role');
        if ($this->isProtectedAdmin($user)) {
            $payload = $request->only(['name', 'email', 'role_id']);
            if (!empty($payload)) {
                return ApiResponse::forbidden('Protected admin user cannot be modified');
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role_id' => 'sometimes|required|exists:roles,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        $user->load('role');

        return ApiResponse::success($user, "User updated successfully");
    }

    #[OA\Delete(
        path: "/api/users/{user}",
        operationId: "deleteUser",
        description: "Delete a user",
        summary: "Delete user",
        tags: ["Users"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                description: "User ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "User deleted successfully"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function destroy(User $user)
    {
        $user->loadMissing('role');
        if ($this->isProtectedAdmin($user)) {
            return ApiResponse::forbidden('Protected admin user cannot be deleted');
        }

        $user->delete();
        return ApiResponse::success(null, "User deleted successfully");
    }
}
