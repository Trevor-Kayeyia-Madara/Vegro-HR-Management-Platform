<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\CompanyDomain;
use Closure;
use Illuminate\Http\Request;

class ResolveCompanyFromDomain
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        if (!$host) {
            return $next($request);
        }

        $domain = CompanyDomain::withoutGlobalScope('company')
            ->where('domain', $host)
            ->first();

        $company = $domain?->company ?? Company::where('domain', $host)->first();

        if ($company) {
            app()->instance('company_id', $company->id);
            app()->instance('currentCompany', $company);
            $request->attributes->set('company_domain', $host);
            $request->attributes->set('company_id', $company->id);
            $request->attributes->set('company', $company);

            if (!$request->has('company_id')) {
                $request->merge(['company_id' => $company->id]);
            }
            if (!$request->has('company_domain')) {
                $request->merge(['company_domain' => $host]);
            }

            $user = $request->user();
            if ($user && $user->company_id && $user->company_id !== $company->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Company domain mismatch',
                    'data' => null,
                ], 403);
            }
        }

        return $next($request);
    }
}
