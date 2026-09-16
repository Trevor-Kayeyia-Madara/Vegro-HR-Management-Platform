<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RequireSuperAdmin
{
    protected function normalize(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));
        return str_replace([' ', '-', '_'], '', $normalized);
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        // Check is_super_admin flag first (primary check)
        if ($user->is_super_admin === true || $user->is_super_admin === 1) {
            return $next($request);
        }

        // Fallback to role title check
        $userRoleTitle = $user->role?->title;
        if (!$userRoleTitle && $user->role_id) {
            $userRoleTitle = Role::where('id', $user->role_id)->value('title');
        }

        if ($this->normalize($userRoleTitle ?? '') !== 'superadmin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
