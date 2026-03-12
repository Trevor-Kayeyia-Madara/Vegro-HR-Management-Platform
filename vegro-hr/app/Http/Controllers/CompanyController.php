<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\Plan;
use App\Services\CompanyOnboardingService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class CompanyController extends Controller
{
    protected CompanyOnboardingService $onboarding;
    protected ActivityLogService $activity;

    public function __construct(CompanyOnboardingService $onboarding, ActivityLogService $activity)
    {
        $this->onboarding = $onboarding;
        $this->activity = $activity;
    }

    #[OA\Get(
        path: "/api/companies",
        operationId: "listCompanies",
        description: "Get all companies (super admin only)",
        summary: "List companies",
        tags: ["Companies"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Companies retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Companies retrieved successfully"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items())
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function index()
    {
        return ApiResponse::success(
            Company::with(['domains', 'settings', 'activeSubscription.plan'])
                ->orderBy('created_at', 'desc')
                ->get(),
            'Companies retrieved successfully'
        );
    }

    #[OA\Post(
        path: "/api/companies",
        operationId: "createCompany",
        description: "Create a company and optional demo data (super admin only)",
        summary: "Create company",
        tags: ["Companies"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name", "admin_name", "admin_email", "admin_password"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Acme Ltd"),
                    new OA\Property(property: "domain", type: "string", example: "acme.local", nullable: true),
                    new OA\Property(property: "plan", type: "string", example: "starter"),
                    new OA\Property(property: "status", type: "string", example: "active"),
                    new OA\Property(property: "environment", type: "string", example: "demo"),
                    new OA\Property(property: "seed_demo", type: "boolean", example: true),
                    new OA\Property(property: "admin_name", type: "string", example: "Company Admin"),
                    new OA\Property(property: "admin_email", type: "string", format: "email", example: "admin@acme.local"),
                    new OA\Property(property: "admin_password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Company created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Company created successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('company_domains', 'domain')],
            'industry' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:2',
            'plan' => 'nullable|string|max:255',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'environment' => ['nullable', Rule::in(['demo', 'staging', 'production'])],
            'seed_demo' => 'sometimes|boolean',
            'currency' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|max:10',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
        ]);

        $result = $this->onboarding->onboard($validated);
        $this->activity->log('company.created', $result['company']->id, Company::class, $result['company']->id);

        return ApiResponse::success($result, 'Company created successfully', 201);
    }

    public function suspend(Company $company)
    {
        $company->update(['status' => 'inactive']);
        $this->activity->log('company.suspended', $company->id, Company::class, $company->id);
        return ApiResponse::success(['company' => $company->fresh()], 'Company suspended');
    }

    public function resume(Company $company)
    {
        $company->update(['status' => 'active']);
        $this->activity->log('company.resumed', $company->id, Company::class, $company->id);
        return ApiResponse::success(['company' => $company->fresh()], 'Company resumed');
    }

    public function addDomain(Request $request, Company $company)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255|unique:company_domains,domain',
            'is_primary' => 'sometimes|boolean',
        ]);

        if (!empty($validated['is_primary'])) {
            $company->domains()->update(['is_primary' => false]);
        }

        $domain = CompanyDomain::create([
            'company_id' => $company->id,
            'domain' => $validated['domain'],
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        if ($domain->is_primary) {
            $company->update(['domain' => $domain->domain]);
        }

        $this->activity->log('company.domain.added', $company->id, CompanyDomain::class, $domain->id, [
            'domain' => $domain->domain,
        ]);

        return ApiResponse::success(['domain' => $domain], 'Domain added', 201);
    }

    public function listDomains(Company $company)
    {
        return ApiResponse::success(['domains' => $company->domains()->orderByDesc('is_primary')->get()], 'Domains retrieved');
    }

    public function removeDomain(Company $company, CompanyDomain $domain)
    {
        if ($domain->company_id !== $company->id) {
            return ApiResponse::notFound('Domain not found');
        }

        $wasPrimary = $domain->is_primary;
        $domainName = $domain->domain;
        $domain->delete();

        if ($wasPrimary) {
            $replacement = $company->domains()->orderByDesc('id')->first();
            $company->update(['domain' => $replacement?->domain]);
        }

        $this->activity->log('company.domain.removed', $company->id, CompanyDomain::class, $domain->id, [
            'domain' => $domainName,
        ]);

        return ApiResponse::success(null, 'Domain removed');
    }

    public function updatePlan(Request $request, Company $company)
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        $plan = Plan::find($validated['plan_id']);
        $company->update(['plan' => $plan->name]);

        $subscription = $company->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->activity->log('company.plan.updated', $company->id, Plan::class, $plan->id, [
            'subscription_id' => $subscription->id,
        ]);

        return ApiResponse::success([
            'company' => $company->fresh(),
            'subscription' => $subscription,
        ], 'Plan updated');
    }
}
