<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SuperAdminController extends Controller
{
    #[OA\Get(
        path: "/api/super/dashboard",
        operationId: "superAdminDashboard",
        description: "Super admin dashboard summary",
        summary: "Super admin dashboard",
        tags: ["Super Admin"],
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
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function dashboard(Request $request)
    {
        $companyCount = Company::count();
        $userCount = User::count();
        $environmentBreakdown = Company::selectRaw('environment, COUNT(*) as total')
            ->groupBy('environment')
            ->pluck('total', 'environment');

        $recentCompanies = Company::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $statusBreakdown = Company::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $planBreakdown = Company::selectRaw('plan, COUNT(*) as total')
            ->groupBy('plan')
            ->pluck('total', 'plan');

        $roleBreakdown = Role::leftJoin('users', 'roles.id', '=', 'users.role_id')
            ->selectRaw('roles.title as role, COUNT(users.id) as total')
            ->groupBy('roles.title')
            ->pluck('total', 'role');

        $recentUsers = User::with('role')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topCompaniesByUsers = Company::leftJoin('users', 'companies.id', '=', 'users.company_id')
            ->selectRaw('companies.id, companies.name, companies.environment, companies.status, COUNT(users.id) as users_count')
            ->groupBy('companies.id', 'companies.name', 'companies.environment', 'companies.status')
            ->orderByDesc('users_count')
            ->limit(10)
            ->get();

        return ApiResponse::success([
            'stats' => [
                'companies' => $companyCount,
                'users' => $userCount,
                'environments' => $environmentBreakdown,
                'status' => $statusBreakdown,
                'plans' => $planBreakdown,
                'roles' => $roleBreakdown,
            ],
            'recent_companies' => $recentCompanies,
            'recent_users' => $recentUsers,
            'top_companies_by_users' => $topCompaniesByUsers,
        ], 'Dashboard retrieved successfully');
    }
}
