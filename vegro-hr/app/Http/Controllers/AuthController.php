<?php

namespace App\Http\Controllers;
use App\Services\AuthService;
use App\Helpers\ApiResponse;
use App\Http\Requests\RegisterRequest;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[OA\Post(
        path: "/api/auth/register",
        operationId: "register",
        description: "Register a new user",
        summary: "User registration",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            description: "User registration data",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "name", type: "string", description: "Full name", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", description: "Email address", example: "john.doe@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", description: "Password (min 8 chars)", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", description: "Password confirmation", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Registration successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Registration successful"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Registration successful"),
                                new OA\Property(property: "user", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
   public function store(RegisterRequest $request)
    {
        $user = $this->authService->store($request->validated());
        return ApiResponse::success(['message' => 'Registration successful', 'user' => $user]);
    }

    #[OA\Post(
        path: "/api/auth/login",
        operationId: "login",
        description: "User login",
        summary: "User login",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            description: "Login credentials",
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", description: "Email address", example: "john.doe@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", description: "Password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Login successful"),
                                new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGc...")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized - Invalid credentials")
        ]
    )]
    public function login()
    {
        $credentials = request(['email', 'password']);
        $result = $this->authService->login($credentials);
        
        if (!$result) {
            return ApiResponse::error('User not found', 401);
        }
        
        if (isset($result['error'])) {
            return ApiResponse::error($result['error'], 401);
        }
        
        return ApiResponse::success(['message' => 'Login successful', 'token' => $result['token']]);
    }   

    #[OA\Post(
        path: "/api/auth/logout",
        operationId: "logout",
        description: "User logout",
        summary: "User logout",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Logout successful"),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Logout successful")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function logout()
    {
        $user = auth()->user();
        $this->authService->logout($user);
        return ApiResponse::success(['message' => 'Logout successful']);
    }

    #[OA\Get(
        path: "/api/auth/me",
        operationId: "getCurrentUser",
        description: "Get current authenticated user",
        summary: "Get current user",
        tags: ["Authentication"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "User retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "user", type: "object")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function me()
    {
        return ApiResponse::success(['user' => auth()->user()]);
    }   

    #[OA\Get(
        path: "/api/auth/check",
        operationId: "authCheck",
        description: "Check if user is authenticated",
        summary: "Check authentication status",
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Authentication status retrieved",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: ""),
                        new OA\Property(
                            property: "data",
                            properties: [
                                new OA\Property(property: "authenticated", type: "boolean", example: true)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function authCheck()
    {
        return ApiResponse::success(['authenticated' => auth()->check()]);
    }
}