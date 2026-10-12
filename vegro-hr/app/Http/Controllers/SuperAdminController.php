<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use App\Models\Company;
use App\Services\CompanyOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SuperAdminController extends Controller
{
    public function __construct(protected CompanyOnboardingService $onboarding)
    {
    }

    /**
     * Return platform-wide data for the super-admin dashboard.
     *
     * User is tenant-scoped by a global scope, so every platform-wide user
     * query explicitly removes that scope. Super-admins must see all tenants.
     */
    public function dashboard()
    {
        $companies = Company::query();
        $allUsers = User::withoutGlobalScopes();

        $groupCounts = static function ($query, string $column): array {
            return $query
                ->selectRaw("COALESCE({$column}, 'unassigned') as label, COUNT(*) as total")
                ->groupBy($column)
                ->pluck('total', 'label')
                ->map(static fn ($total) => (int) $total)
                ->all();
        };

        return ApiResponse::success([
            'stats' => [
                'companies' => $companies->count(),
                'users' => $allUsers->count(),
                'environments' => $groupCounts(Company::query(), 'environment'),
                'status' => $groupCounts(Company::query(), 'status'),
                'plans' => $groupCounts(Company::query(), 'plan'),
            ],
            'recent_companies' => Company::query()
                ->select(['id', 'name', 'environment', 'status', 'plan', 'created_at'])
                ->latest()
                ->limit(10)
                ->get(),
            'recent_users' => User::withoutGlobalScopes()
                ->with('company:id,name')
                ->select(['id', 'company_id', 'name', 'email', 'created_at'])
                ->latest()
                ->limit(10)
                ->get(),
            'top_companies_by_users' => Company::query()
                ->withCount('users')
                ->orderByDesc('users_count')
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'environment', 'status']),
        ], 'Super admin dashboard retrieved successfully');
    }

    /**
     * Create a new company with admin and HR accounts
     */
    public function onboardCompany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:30',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'hr_name' => 'required|string|max:255',
            'hr_email' => 'required|email|different:admin_email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $result = $this->onboarding->onboard([
            'name' => $request->company_name,
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'admin_name' => $request->admin_name,
            'admin_email' => $request->admin_email,
            'hr_name' => $request->hr_name,
            'hr_email' => $request->hr_email,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Company onboarded successfully',
            'company' => $result['company'],
            'admin' => $result['admin_user'],
            'hr' => $result['hr_user'],
            'invitations' => $result['invitations'],
        ], 201);
    }

    /**
     * Get all companies (super admin only)
     */
    public function getAllCompanies()
    {
        $companies = Company::with(['users' => function ($query) {
            $query->with('roles');
        }])->get();

        return response()->json([
            'companies' => $companies,
        ]);
    }

    /**
     * Create super admin account
     */
    public function createSuperAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $superAdmin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Assign super admin role if it exists
        $superAdminRole = \App\Models\Role::where('title', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdmin->roles()->attach($superAdminRole->id);
        }

        return response()->json([
            'message' => 'Super admin created successfully',
            'user' => $superAdmin,
        ], 201);
    }
}
