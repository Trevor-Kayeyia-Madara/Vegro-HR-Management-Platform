<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use App\Services\LoginLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Create a new company with admin and HR accounts
     */
    public function onboardCompany(Request $request)
    {
        $this->middleware('check.api.token');

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_email' => 'required|email',
            'company_phone' => 'required|string|max:20',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'hr_name' => 'required|string|max:255',
            'hr_email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create company
        $company = Company::create([
            'name' => $request->company_name,
            'email' => $request->company_email,
            'phone' => $request->company_phone,
            'status' => 'active',
        ]);

        // Create Company Admin
        $adminPassword = Hash::make(Str::random(16));
        $admin = User::create([
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'password' => $adminPassword,
            'company_id' => $company->id,
            'is_super_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Assign admin role to user
        $adminRole = \App\Models\Role::where('title', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id);
        }

        // Create HR
        $hrPassword = Hash::make(Str::random(16));
        $hr = User::create([
            'name' => $request->hr_name,
            'email' => $request->hr_email,
            'password' => $hrPassword,
            'company_id' => $company->id,
            'is_super_admin' => false,
            'email_verified_at' => now(),
        ]);

        // Assign HR role to user
        $hrRole = \App\Models\Role::where('title', 'hr')->first();
        if ($hrRole) {
            $hr->roles()->attach($hrRole->id);
        }

        // Generate login links
        $loginLinkService = app(\App\Services\LoginLinkService::class);
        $adminLoginUrl = $loginLinkService->getLoginUrl($admin);
        $hrLoginUrl = $loginLinkService->getLoginUrl($hr);

        // Send login link emails (implement actual email sending)
        $loginLinkService->sendLoginLinkEmail($admin);
        $loginLinkService->sendLoginLinkEmail($hr);

        return response()->json([
            'message' => 'Company onboarded successfully',
            'company' => $company,
            'admin' => [
                'user' => $admin,
                'login_url' => $adminLoginUrl,
                'expires_in_hours' => 24,
            ],
            'hr' => [
                'user' => $hr,
                'login_url' => $hrLoginUrl,
                'expires_in_hours' => 24,
            ],
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