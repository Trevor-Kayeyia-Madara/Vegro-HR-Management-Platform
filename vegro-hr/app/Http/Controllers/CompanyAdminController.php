<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class CompanyAdminController extends Controller
{
    #[OA\Get(
        path: "/api/company/admin/dashboard",
        operationId: "companyAdminDashboard",
        description: "Company admin dashboard summary",
        summary: "Company admin dashboard",
        tags: ["Company Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dashboard data retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Dashboard retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function dashboard(Request $request)
    {
        $companyId = $request->attributes->get('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            return ApiResponse::notFound('Company not found');
        }

        $data = [
            'company' => $company,
            'stats' => [
                'users' => User::count(),
                'roles' => Role::count(),
                'employees' => Employee::count(),
                'departments' => Department::count(),
            ],
        ];

        return ApiResponse::success($data, 'Dashboard retrieved successfully');
    }

    #[OA\Get(
        path: "/api/company/admin/settings",
        operationId: "companyAdminSettings",
        description: "Get company settings",
        summary: "Get company settings",
        tags: ["Company Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Settings retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Settings retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function settings(Request $request)
    {
        $companyId = $request->attributes->get('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            return ApiResponse::notFound('Company not found');
        }

        $settings = CompanySetting::firstOrCreate(['company_id' => $company->id]);

        return ApiResponse::success([
            'company' => $company,
            'settings' => $settings,
        ], 'Settings retrieved successfully');
    }

    #[OA\Put(
        path: "/api/company/admin/settings",
        operationId: "companyAdminUpdateSettings",
        description: "Update company settings",
        summary: "Update company settings",
        tags: ["Company Admin"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Acme Ltd"),
                    new OA\Property(property: "domain", type: "string", example: "acme.local"),
                    new OA\Property(property: "plan", type: "string", example: "starter"),
                    new OA\Property(property: "status", type: "string", example: "active"),
                    new OA\Property(property: "environment", type: "string", example: "production")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Settings updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Settings updated successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function updateSettings(Request $request)
    {
        $companyId = $request->attributes->get('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            return ApiResponse::notFound('Company not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'domain' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('company_domains', 'domain')->where(fn ($q) => $q->where('company_id', '!=', $company->id)),
            ],
            'industry' => 'sometimes|nullable|string|max:255',
            'country' => 'sometimes|nullable|string|max:2',
            'plan' => 'sometimes|nullable|string|max:255',
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'environment' => ['sometimes', Rule::in(['demo', 'staging', 'production'])],
            'currency' => 'sometimes|nullable|string|max:10',
            'timezone' => 'sometimes|nullable|string|max:50',
            'locale' => 'sometimes|nullable|string|max:10',
            'date_format' => 'sometimes|nullable|string|max:32',
            'time_format' => 'sometimes|nullable|string|max:32',
            'tax_rules' => 'sometimes|array',
            'payroll_rules' => 'sometimes|array',
            'branding' => 'sometimes|array',
        ]);

        $companyFields = array_intersect_key($validated, array_flip([
            'name', 'domain', 'industry', 'country', 'plan', 'status', 'environment',
        ]));
        if (!empty($companyFields)) {
            $company->update($companyFields);
        }

        if (array_key_exists('domain', $validated) && !empty($validated['domain'])) {
            $company->domains()->update(['is_primary' => false]);
            $domain = $company->domains()->firstOrCreate(
                ['domain' => $validated['domain']],
                ['is_primary' => true]
            );
            $company->domains()->where('id', $domain->id)->update(['is_primary' => true]);
        }

        $settingsFields = array_intersect_key($validated, array_flip([
            'currency', 'timezone', 'locale', 'date_format', 'time_format',
            'tax_rules', 'payroll_rules', 'branding',
        ]));
        $settings = CompanySetting::firstOrCreate(['company_id' => $company->id]);
        if (!empty($settingsFields)) {
            $settings->update($settingsFields);
        }

        return ApiResponse::success([
            'company' => $company->fresh(),
            'settings' => $settings->fresh(),
        ], 'Settings updated successfully');
    }

    #[OA\Get(
        path: "/api/company/admin/index-data",
        operationId: "companyAdminIndexData",
        description: "Get users, roles, and permissions for company admin screens",
        summary: "Company admin index data",
        tags: ["Company Admin"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Index data retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "message", type: "string", example: "Index data retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function indexData(Request $request)
    {
        $perPage = max((int) $request->query('per_page', 50), 1);

        $users = User::with('role')->orderBy('created_at', 'desc')->paginate($perPage);
        $roles = Role::with('permissions')->orderBy('title')->get();
        $permissions = Permission::orderBy('group')->orderBy('label')->get();

        return ApiResponse::success([
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ], 'Index data retrieved successfully');
    }

    public function subscription(Request $request)
    {
        $company = $request->attributes->get('company');
        if (!$company) {
            return ApiResponse::notFound('Company not found');
        }

        $company->load(['activeSubscription.plan']);

        return ApiResponse::success([
            'company' => $company,
            'subscription' => $company->activeSubscription,
        ], 'Subscription retrieved successfully');
    }
}
