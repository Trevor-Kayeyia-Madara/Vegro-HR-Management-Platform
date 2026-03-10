<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;

class CheckApiToken
{
    public function handle(Request $request, Closure $next)
    {
        // Get token from Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ], 401);
        }

        // Check if token exists in database and is not expired
        $apiToken = ApiToken::with('user')
            ->where('token', $token)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        if (!$apiToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ], 401);
        }

        // Authenticate the user
        auth()->login($apiToken->user);

        return $next($request);
    }
}
