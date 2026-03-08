<?php

namespace App\Http\Controllers;
use App\Services\AuthService;
use App\Helpers\ApiResponse;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());
        return ApiResponse::success(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);
        $token = $this->authService->login($credentials);
        if (!$token) {
            return ApiResponse::error('Unauthorized', 401);
        }
        return ApiResponse::success(['message' => 'Login successful', 'token' => $token]);
    }   

    public function logout()
    {
        $user = auth()->user();
        $this->authService->logout($user);
        return ApiResponse::success(['message' => 'Logout successful']);
    }

    public function me()
    {
        return ApiResponse::success(['user' => auth()->user()]);
    }   

    public function authCheck()
    {
        return ApiResponse::success(['authenticated' => auth()->check()]);
    }
}