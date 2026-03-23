<?php

namespace App\Http\Controllers;
use App\Services\AuthService;
use App\Helpers\ApiResponse;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use App\Models\ApiToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    protected function emailVerificationRequired(): bool
    {
        return !app()->environment('local');
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
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", description: "Password confirmation", example: "password123"),
                    new OA\Property(property: "company_id", type: "integer", description: "Company ID (optional)", example: 1),
                    new OA\Property(property: "company_domain", type: "string", description: "Company domain (optional)", example: "acme.local")
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
        if ($this->emailVerificationRequired()) {
            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
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
                    new OA\Property(property: "password", type: "string", format: "password", description: "Password", example: "password123"),
                    new OA\Property(property: "company_id", type: "integer", description: "Company ID (optional)", example: 1),
                    new OA\Property(property: "company_domain", type: "string", description: "Company domain (optional)", example: "acme.local")
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
        $credentials = request(['email', 'password', 'company_id', 'company_domain']);
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
        $user = auth()->user();
        if ($user) {
            $user->load('role.permissions', 'employee.department');
        }
        return ApiResponse::success(['user' => $user]);
    }   

    public function updateMe(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        $companyId = $user->company_id;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')
                    ->where('company_id', $companyId)
                    ->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $userPayload = array_intersect_key($validated, [
            'name' => true,
            'email' => true,
            'password' => true,
        ]);

        if (!empty($userPayload['password'])) {
            $userPayload['password'] = Hash::make($userPayload['password']);
        } else {
            unset($userPayload['password']);
        }

        $emailChanged = array_key_exists('email', $userPayload) && $userPayload['email'] !== $user->email;

        if (!empty($userPayload)) {
            $user->update($userPayload);
        }

        if ($emailChanged) {
            if ($this->emailVerificationRequired()) {
                $user->forceFill(['email_verified_at' => null])->save();
                $user->sendEmailVerificationNotification();
            } else {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
        }

        $employeePayload = array_intersect_key($validated, ['phone' => true]);

        $employee = $user->employee;
        if ($employee && !empty($employeePayload)) {
            $employee->update($employeePayload);
        }

        $user = $user->fresh();
        $user?->load('role.permissions', 'employee.department');

        return ApiResponse::success(['user' => $user], 'Profile updated');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return ApiResponse::error('Invalid verification link.', 403);
        }

        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new Verified($user));
        }

        $frontendBase = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
        return redirect()->away($frontendBase . '/login?verified=1');
    }

    public function resendVerification(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        if (!$this->emailVerificationRequired()) {
            if (is_null($user->email_verified_at)) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }
            return ApiResponse::success(['verified' => true], 'Email verification is disabled in local environment.');
        }

        if (!is_null($user->email_verified_at)) {
            return ApiResponse::success(['verified' => true], 'Email is already verified.');
        }

        $user->sendEmailVerificationNotification();
        return ApiResponse::success(['verified' => false], 'Verification email sent.');
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

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::broker()->sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success([], 'Password reset link sent to your email.');
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                ApiToken::where('user_id', $user->id)->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return ApiResponse::error(__($status), 422);
        }

        return ApiResponse::success([], 'Password reset successfully. You can now log in.');
    }
}
