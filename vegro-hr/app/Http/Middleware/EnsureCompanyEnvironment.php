<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class EnsureCompanyEnvironment
{
    public function handle(Request $request, Closure $next)
    {
        $expected = env('TENANT_ENVIRONMENT');
        if (!$expected) {
            return $next($request);
        }

        $user = $request->user();
        if (!$user || !$user->company_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not set for user',
                'data' => null,
            ], 403);
        }

        $company = Company::find($user->company_id);
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found',
                'data' => null,
            ], 404);
        }

        $expected = strtolower(trim((string) $expected));
        $actual = strtolower(trim((string) $company->environment));

        if ($expected !== $actual) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company environment mismatch',
                'data' => null,
            ], 403);
        }

        $request->attributes->set('company_environment', $company->environment);

        return $next($request);
    }
}
