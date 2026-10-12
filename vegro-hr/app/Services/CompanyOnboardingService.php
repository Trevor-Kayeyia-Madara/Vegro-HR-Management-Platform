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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Database\Seeders\RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\EmployeeSeeder;

class CompanyOnboardingService
{
    public function __construct(protected LoginLinkService $loginLinks)
    {
    }

    public function onboard(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            return $this->createCompanyAndUsers($payload);
        });
    }

    private function createCompanyAndUsers(array $payload): array
    {
        $company = Company::create([
            'name' => $payload['name'],
            'industry' => $payload['industry'] ?? null,
            'country' => $payload['country'] ?? null,
            'email' => $payload['email'] ?? null,
            'phone' => $payload['phone'] ?? null,
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

        $this->withCompanyContext($company->id, function () use ($payload, $company, &$adminUser, &$hrUser) {
            (new RoleSeeder())->run();
            (new PermissionSeeder())->run();

            $adminUser = $this->createCompanyAdmin($company->id, $payload);
            $hrUser = $this->createHrUser($company->id, $payload);

            if (!empty($payload['seed_demo'])) {
                (new DepartmentSeeder())->run();
                (new UserSeeder())->run();
                (new EmployeeSeeder())->run();
            }
        });

        $adminLoginUrl = $this->loginLinks->getLoginUrl($adminUser);
        $hrLoginUrl = $this->loginLinks->getLoginUrl($hrUser);

        DB::afterCommit(function () use ($adminUser, $hrUser, $company, $adminLoginUrl, $hrLoginUrl): void {
            $this->loginLinks->sendLoginLinkEmail($adminUser, 24, $adminLoginUrl, $company, 'Company Admin');
            $this->loginLinks->sendLoginLinkEmail($hrUser, 24, $hrLoginUrl, $company, 'HR');
        });

        return [
            'company' => $company,
            'admin_user' => $adminUser,
            'hr_user' => $hrUser,
            'invitations' => [
                ['email' => $adminUser->email, 'role' => 'companyadmin', 'expires_in_hours' => 24],
                ['email' => $hrUser->email, 'role' => 'HR', 'expires_in_hours' => 24],
            ],
        ];
    }

    protected function createCompanyAdmin(int $companyId, array $payload): User
    {
        return $this->createUserForRole($companyId, $payload['admin_name'], $payload['admin_email'], 'companyadmin', $payload['admin_password'] ?? null);
    }

    protected function createHrUser(int $companyId, array $payload): User
    {
        return $this->createUserForRole($companyId, $payload['hr_name'], $payload['hr_email'], 'HR', $payload['hr_password'] ?? null);
    }

    protected function createUserForRole(int $companyId, string $name, string $email, string $roleTitle, ?string $password): User
    {
        $role = Role::firstOrCreate(
            ['title' => $roleTitle, 'company_id' => $companyId],
            ['description' => $roleTitle === 'HR' ? 'Human Resources with access to employee data' : 'Company-level administrator with full access'],
        );

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password ?? Str::random(32)),
            'role_id' => $role->id,
            'company_id' => $companyId,
            'is_super_admin' => false,
        ]);
    }

    protected function withCompanyContext(int $companyId, callable $callback): void
    {
        app()->instance('company_id', $companyId);
        $callback();
        app()->forgetInstance('company_id');
    }
}
