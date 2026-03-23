<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeManagerAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyAOrgChartDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('domain', 'default.local')->first();

        if (!$company) {
            $this->command?->error('Company A (default.local) not found.');
            return;
        }

        DB::transaction(function () use ($company) {
            $companyId = (int) $company->id;

            $roleIds = Role::where('company_id', $companyId)
                ->whereIn('title', ['companyadmin', 'HR', 'Finance', 'Manager', 'Director', 'MD', 'Employee'])
                ->pluck('id', 'title');

            $companyAdminRoleId = $roleIds->get('companyadmin');

            $companyAdmin = User::where('company_id', $companyId)
                ->where('email', 'companyadmin@example.com')
                ->first();

            if (!$companyAdmin) {
                $companyAdmin = User::create([
                    'name' => 'Company Admin',
                    'email' => 'companyadmin@example.com',
                    'password' => Hash::make('CompanyAdmin123'),
                    'role_id' => $companyAdminRoleId,
                    'company_id' => $companyId,
                ]);
            }

            $protectedUserId = (int) $companyAdmin->id;

            $companyUserIdsToReset = User::where('company_id', $companyId)
                ->where('id', '!=', $protectedUserId)
                ->pluck('id')
                ->all();

            $companyEmployeeIdsToReset = Employee::where('company_id', $companyId)
                ->where(function ($query) use ($protectedUserId) {
                    $query->whereNull('user_id')->orWhere('user_id', '!=', $protectedUserId);
                })
                ->pluck('id')
                ->all();

            if (!empty($companyEmployeeIdsToReset)) {
                EmployeeManagerAssignment::where('company_id', $companyId)
                    ->whereIn('employee_id', $companyEmployeeIdsToReset)
                    ->delete();

                DB::table('employee_role')
                    ->where('company_id', $companyId)
                    ->whereIn('employee_id', $companyEmployeeIdsToReset)
                    ->delete();

                Employee::whereIn('id', $companyEmployeeIdsToReset)->delete();
            }

            if (!empty($companyUserIdsToReset)) {
                User::whereIn('id', $companyUserIdsToReset)->delete();
            }

            $departmentSeed = [
                ['name' => 'Human Resources', 'description' => 'People, talent, and compliance management.'],
                ['name' => 'Finance', 'description' => 'Budgeting, accounting, and payroll operations.'],
                ['name' => 'Marketing', 'description' => 'Brand, campaigns, and growth.'],
                ['name' => 'Operations', 'description' => 'Execution, delivery, and process quality.'],
                ['name' => 'Engineering', 'description' => 'Product and platform development.'],
                ['name' => 'Sales', 'description' => 'Revenue and customer acquisition.'],
                ['name' => 'Customer Success', 'description' => 'Retention, onboarding, and support.'],
                ['name' => 'IT', 'description' => 'Internal systems and security support.'],
            ];

            foreach ($departmentSeed as $seed) {
                Department::updateOrCreate(
                    ['company_id' => $companyId, 'name' => $seed['name']],
                    ['description' => $seed['description']]
                );
            }

            $departmentsByName = Department::where('company_id', $companyId)
                ->pluck('id', 'name');

            $profiles = [
                ['name' => 'Naomi Kendi', 'email' => 'naomi.kendi@companya.test', 'role' => 'MD', 'department' => 'Operations', 'position' => 'Managing Director'],
                ['name' => 'Peter Kariuki', 'email' => 'peter.kariuki@companya.test', 'role' => 'Director', 'department' => 'Operations', 'position' => 'Executive Director'],
                ['name' => 'Amina Njoroge', 'email' => 'amina.njoroge@companya.test', 'role' => 'HR', 'department' => 'Human Resources', 'position' => 'Head of HR'],
                ['name' => 'Brian Otieno', 'email' => 'brian.otieno@companya.test', 'role' => 'Finance', 'department' => 'Finance', 'position' => 'Finance Lead'],
                ['name' => 'Esther Wanjiru', 'email' => 'esther.wanjiru@companya.test', 'role' => 'Manager', 'department' => 'Marketing', 'position' => 'Marketing Manager'],
                ['name' => 'Daniel Mwangi', 'email' => 'daniel.mwangi@companya.test', 'role' => 'Manager', 'department' => 'Operations', 'position' => 'Operations Manager'],
                ['name' => 'Felix Kiptoo', 'email' => 'felix.kiptoo@companya.test', 'role' => 'Manager', 'department' => 'Engineering', 'position' => 'Engineering Manager'],
                ['name' => 'Grace Atieno', 'email' => 'grace.atieno@companya.test', 'role' => 'Manager', 'department' => 'Sales', 'position' => 'Sales Manager'],
                ['name' => 'Henry Maina', 'email' => 'henry.maina@companya.test', 'role' => 'Manager', 'department' => 'Customer Success', 'position' => 'Customer Success Manager'],
                ['name' => 'Ivy Chelimo', 'email' => 'ivy.chelimo@companya.test', 'role' => 'Manager', 'department' => 'IT', 'position' => 'IT Manager'],
                ['name' => 'John Kamau', 'email' => 'john.kamau@companya.test', 'role' => 'Employee', 'department' => 'Engineering', 'position' => 'Software Engineer'],
                ['name' => 'Lorna Achieng', 'email' => 'lorna.achieng@companya.test', 'role' => 'Employee', 'department' => 'Sales', 'position' => 'Sales Executive'],
                ['name' => 'Mercy Jepkosgei', 'email' => 'mercy.jepkosgei@companya.test', 'role' => 'Employee', 'department' => 'Human Resources', 'position' => 'HR Officer'],
                ['name' => 'Kevin Mutua', 'email' => 'kevin.mutua@companya.test', 'role' => 'Employee', 'department' => 'Operations', 'position' => 'Operations Analyst'],
                ['name' => 'Ruth Nyambura', 'email' => 'ruth.nyambura@companya.test', 'role' => 'Employee', 'department' => 'Finance', 'position' => 'Accountant'],
            ];

            $createdUsersByEmail = [];
            $createdEmployeesByEmail = [];

            foreach ($profiles as $index => $profile) {
                $roleId = $roleIds->get($profile['role']) ?? $roleIds->get('Employee');
                $departmentId = $departmentsByName->get($profile['department']) ?? $departmentsByName->values()->first();

                $user = User::create([
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'password' => Hash::make('Welcome123!'),
                    'role_id' => $roleId,
                    'company_id' => $companyId,
                ]);

                $employee = Employee::create([
                    'company_id' => $companyId,
                    'employee_number' => sprintf('A%04d', $index + 1),
                    'user_id' => $user->id,
                    'name' => $profile['name'],
                    'email' => $profile['email'],
                    'department_id' => $departmentId,
                    'position' => $profile['position'],
                    'salary' => 85000 + ($index * 4500),
                    'hire_date' => now()->subMonths(($index % 9) + 1)->toDateString(),
                    'status' => 'active',
                ]);

                if ($roleId) {
                    $employee->roles()->sync([$roleId]);
                }

                $createdUsersByEmail[$profile['email']] = $user;
                $createdEmployeesByEmail[$profile['email']] = $employee;
            }

            $departmentHeads = [
                'Human Resources' => 'amina.njoroge@companya.test',
                'Finance' => 'brian.otieno@companya.test',
                'Marketing' => 'esther.wanjiru@companya.test',
                'Operations' => 'daniel.mwangi@companya.test',
                'Engineering' => 'felix.kiptoo@companya.test',
                'Sales' => 'grace.atieno@companya.test',
                'Customer Success' => 'henry.maina@companya.test',
                'IT' => 'ivy.chelimo@companya.test',
            ];

            foreach ($departmentHeads as $departmentName => $email) {
                $department = Department::where('company_id', $companyId)->where('name', $departmentName)->first();
                $headUser = $createdUsersByEmail[$email] ?? null;

                if ($department) {
                    $department->update(['manager_id' => $headUser?->id]);
                }
            }

            $mdUser = $createdUsersByEmail['naomi.kendi@companya.test'] ?? null;
            $directorUser = $createdUsersByEmail['peter.kariuki@companya.test'] ?? null;

            $createLine = function (string $employeeEmail, ?User $manager, string $type = 'functional') use ($createdEmployeesByEmail, $companyId): void {
                $employee = $createdEmployeesByEmail[$employeeEmail] ?? null;
                if (!$employee || !$manager) {
                    return;
                }

                EmployeeManagerAssignment::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'manager_user_id' => $manager->id,
                        'relationship_type' => $type,
                    ],
                    [
                        'start_date' => now()->toDateString(),
                        'end_date' => null,
                    ]
                );
            };

            $createLine('amina.njoroge@companya.test', $directorUser, 'functional');
            $createLine('brian.otieno@companya.test', $directorUser, 'functional');
            $createLine('esther.wanjiru@companya.test', $directorUser, 'functional');
            $createLine('daniel.mwangi@companya.test', $directorUser, 'functional');
            $createLine('felix.kiptoo@companya.test', $directorUser, 'functional');
            $createLine('grace.atieno@companya.test', $directorUser, 'functional');
            $createLine('henry.maina@companya.test', $directorUser, 'functional');
            $createLine('ivy.chelimo@companya.test', $directorUser, 'functional');

            $createLine('peter.kariuki@companya.test', $mdUser, 'functional');

            $createLine('john.kamau@companya.test', $createdUsersByEmail['felix.kiptoo@companya.test'] ?? null, 'functional');
            $createLine('lorna.achieng@companya.test', $createdUsersByEmail['grace.atieno@companya.test'] ?? null, 'functional');
            $createLine('mercy.jepkosgei@companya.test', $createdUsersByEmail['amina.njoroge@companya.test'] ?? null, 'functional');
            $createLine('kevin.mutua@companya.test', $createdUsersByEmail['daniel.mwangi@companya.test'] ?? null, 'functional');
            $createLine('ruth.nyambura@companya.test', $createdUsersByEmail['brian.otieno@companya.test'] ?? null, 'functional');

            $createLine('john.kamau@companya.test', $createdUsersByEmail['daniel.mwangi@companya.test'] ?? null, 'dotted');
            $createLine('lorna.achieng@companya.test', $createdUsersByEmail['henry.maina@companya.test'] ?? null, 'dotted');
            $createLine('kevin.mutua@companya.test', $createdUsersByEmail['ivy.chelimo@companya.test'] ?? null, 'dotted');
        });

        $this->command?->info('Company A users and employees reseeded. companyadmin@example.com preserved.');
    }
}
