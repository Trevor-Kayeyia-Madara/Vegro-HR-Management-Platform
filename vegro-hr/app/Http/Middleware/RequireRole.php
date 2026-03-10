<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    protected function normalize(?string $role): string
    {
        $normalized = strtolower(trim((string) $role));
        $normalized = str_replace([' ', '-', '_'], '', $normalized);
        $aliases = [
            'hrmanager' => 'hr',
            'humanresourcesmanager' => 'hr',
        ];
        return $aliases[$normalized] ?? $normalized;
    }

    protected function isAdmin(string $role): bool
    {
        return in_array($role, ['admin', 'administrator', 'superadmin', 'companyadmin', 'companyadministrator'], true);
    }

    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        $userRole = $this->normalize($user->role?->title ?? '');

        if ($this->isAdmin($userRole)) {
            return $next($request);
        }

        $allowed = array_map(function ($role) {
            return $this->normalize($role);
        }, $roles);

        if (in_array($userRole, $allowed, true)) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Forbidden',
            'data' => null,
        ], 403);
    }
}
