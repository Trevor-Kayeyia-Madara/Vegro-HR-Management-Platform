<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanySetting;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDemoData extends Command
{
    protected $signature = 'vegro:reset-demo {--company_id= : Reset a specific demo company by ID}';
    protected $description = 'Reset demo companies data and reseed defaults';

    public function handle(): int
    {
        $companyId = $this->option('company_id');

        $companies = Company::where('environment', 'demo')
            ->when($companyId, fn ($q) => $q->where('id', $companyId))
            ->get();

        if ($companies->isEmpty()) {
            $this->info('No demo companies found.');
            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            $this->info("Resetting demo data for company #{$company->id} ({$company->name})...");
            $this->resetCompanyData($company->id);
            $this->seedCompanyData($company->id);
        }

        $this->info('Demo reset complete.');
        return self::SUCCESS;
    }

    protected function resetCompanyData(int $companyId): void
    {
        $tables = [
            'api_tokens',
            'permission_assignment_audits',
            'role_assignment_audits',
            'employee_role',
            'payslips',
            'payrolls',
            'attendances',
            'leave_requests',
            'tax_profiles',
            'employees',
            'departments',
            'roles',
            'users',
        ];

        DB::transaction(function () use ($tables, $companyId) {
            foreach ($tables as $table) {
                DB::table($table)->where('company_id', $companyId)->delete();
            }
        });
    }

    protected function seedCompanyData(int $companyId): void
    {
        app()->instance('company_id', $companyId);

        CompanySetting::firstOrCreate(['company_id' => $companyId], [
            'currency' => 'USD',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        (new RoleSeeder())->run();
        (new PermissionSeeder())->run();
        (new UserSeeder())->run();
        (new DepartmentSeeder())->run();
        (new EmployeeSeeder())->run();

        app()->forgetInstance('company_id');
    }
}
