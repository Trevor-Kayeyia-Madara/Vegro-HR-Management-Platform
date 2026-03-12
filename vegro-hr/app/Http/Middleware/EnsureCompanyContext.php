<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class EnsureCompanyContext
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not set for user',
                'data' => null,
            ], 403);
        }

        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Company not found',
                'data' => null,
            ], 404);
        }

        if ($company->status !== 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'Company is suspended',
                'data' => null,
            ], 403);
        }

        app()->instance('company_id', $companyId);
        $request->attributes->set('company', $company);
        $request->attributes->set('company_id', $companyId);

        return $next($request);
    }
}
