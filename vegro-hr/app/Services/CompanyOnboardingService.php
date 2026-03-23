<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\CompanySetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\EmployeeSeeder;

class CompanyOnboardingService
{
    public function onboard(array $payload): array
    {
        $company = Company::create([
            'name' => $payload['name'],
            'industry' => $payload['industry'] ?? null,
            'country' => $payload['country'] ?? null,
            'domain' => $payload['domain'] ?? null,
            'plan' => $payload['plan'] ?? 'starter',
            'status' => $payload['status'] ?? 'active',
            'environment' => $payload['environment'] ?? 'demo',
        ]);

        if (!empty($payload['domain'])) {
            CompanyDomain::create([
                'company_id' => $company->id,
                'domain' => $payload['domain'],
                'is_primary' => true,
            ]);
        }

        CompanySetting::firstOrCreate(
            ['company_id' => $company->id],
            [
                'currency' => $payload['currency'] ?? 'USD',
                'timezone' => $payload['timezone'] ?? 'UTC',
                'locale' => $payload['locale'] ?? 'en',
            ]
        );

        $planId = $payload['plan_id'] ?? null;
        if (!$planId && !empty($payload['plan'])) {
            $planId = Plan::whereRaw('LOWER(name) = ?', [strtolower($payload['plan'])])->value('id');
        }

        if ($planId) {
            Subscription::create([
                'company_id' => $company->id,
                'plan_id' => $planId,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'starts_at' => now(),
            ]);
        }

        $this->withCompanyContext($company->id, function () use ($payload, $company, &$adminUser) {
            (new RoleSeeder())->run();
            (new PermissionSeeder())->run();

            $adminUser = $this->createCompanyAdmin($company->id, $payload);

            if (!empty($payload['seed_demo'])) {
                (new DepartmentSeeder())->run();
                (new UserSeeder())->run();
                (new EmployeeSeeder())->run();
            }
        });

        return [
            'company' => $company,
            'admin_user' => $adminUser ?? null,
        ];
    }

    protected function createCompanyAdmin(int $companyId, array $payload): User
    {
        $roleId = Role::where('title', 'companyadmin')->value('id');
        if (!$roleId) {
            $roleId = Role::create([
                'title' => 'companyadmin',
                'description' => 'Company-level administrator with full access',
                'company_id' => $companyId,
            ])->id;
        }

        return User::updateOrCreate(
            ['email' => $payload['admin_email']],
            [
                'name' => $payload['admin_name'],
                'password' => Hash::make($payload['admin_password']),
                'role_id' => $roleId,
                'company_id' => $companyId,
            ]
        );
    }

    protected function withCompanyContext(int $companyId, callable $callback): void
    {
        app()->instance('company_id', $companyId);
        $callback();
        app()->forgetInstance('company_id');
    }
}
