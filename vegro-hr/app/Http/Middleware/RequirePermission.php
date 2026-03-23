<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        if (empty($permissions)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($permission && $user->hasPermission((string) $permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden',
            'data' => null,
        ], 403);
    }
}

