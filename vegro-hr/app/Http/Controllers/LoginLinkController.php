<?php

namespace App\Http\Controllers;

use App\Services\LoginLinkService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginLinkController extends Controller
{
    protected $loginLinkService;

    public function __construct(LoginLinkService $loginLinkService)
    {
        $this->loginLinkService = $loginLinkService;
    }

    /**
     * Generate and send login link for a user
     */
    public function sendLoginLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Generate login link
        $loginUrl = $this->loginLinkService->getLoginUrl($user);
        
        // Send email (implement actual email sending)
        $sent = $this->loginLinkService->sendLoginLinkEmail($user);

        return response()->json([
            'message' => $sent ? 'Login link sent successfully' : 'Login link generated but email failed',
            'login_url' => $loginUrl, // Return URL for development/testing
            'expires_in_hours' => 24,
        ]);
    }

    /**
     * Login using a login link token
     */
    public function loginWithLink($token)
    {
        $loginLink = $this->loginLinkService->validateLoginLink($token);

        if (!$loginLink) {
            return response()->json([
                'message' => 'Invalid or expired login link'
            ], 400);
        }

        $user = $loginLink->user;

        // Mark link as used
        $this->loginLinkService->markLinkAsUsed($loginLink);

        // Log in the user
        Auth::login($user);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Generate login link for a specific user (admin function)
     */
    public function generateLoginLinkForUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $loginUrl = $this->loginLinkService->getLoginUrl($user);
        
        return response()->json([
            'message' => 'Login link generated',
            'login_url' => $loginUrl,
            'user' => $user,
            'expires_in_hours' => 24,
        ]);
    }
}