<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = app()->has('company_id')
            ? app('company_id')
            : \App\Models\Company::where('domain', 'default.local')->value('id');
        $departments = \App\Models\Department::pluck('id', 'name');
        $defaultDepartmentId = $departments->values()->first();
        $hrDepartmentId = $departments['Human Resources'] ?? $defaultDepartmentId;
        $financeDepartmentId = $departments['Finance'] ?? $defaultDepartmentId;
        $operationsDepartmentId = $departments['Operations'] ?? $defaultDepartmentId;

        $roleDepartments = [
            'hr' => $hrDepartmentId,
            'finance' => $financeDepartmentId,
            'manager' => $operationsDepartmentId,
            'employee' => $defaultDepartmentId,
            'superadmin' => $defaultDepartmentId,
            'companyadmin' => $defaultDepartmentId,
        ];

        $roleSalaries = [
            'superadmin' => 200000,
            'companyadmin' => 180000,
            'hr' => 140000,
            'finance' => 130000,
            'manager' => 120000,
            'employee' => 80000,
        ];

        $now = now();
        $users = \App\Models\User::with('role')->get();

        foreach ($users as $user) {
            $roleTitle = strtolower(trim((string) ($user->role?->title ?? 'employee')));
            $roleTitle = str_replace([' ', '-', '_'], '', $roleTitle);
            if (in_array($roleTitle, ['superadmin', 'companyadmin'], true)) {
                continue;
            }
            $departmentId = $roleDepartments[$roleTitle] ?? $defaultDepartmentId;
            if (!$departmentId) {
                continue;
            }

            $employeeData = [
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $departmentId,
                'user_id' => $user->id,
                'position' => $user->role?->title ?? 'Employee',
                'status' => 'active',
                'salary' => $roleSalaries[$roleTitle] ?? 80000,
                'company_id' => $companyId,
            ];

            if (empty($employeeData['employee_number'])) {
                $employeeData['employee_number'] = 'EMP' . $now->format('Ymd') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            if (empty($employeeData['hire_date'])) {
                $employeeData['hire_date'] = $now->toDateString();
            }

            $employee = \App\Models\Employee::updateOrCreate(
                ['email' => $user->email],
                $employeeData,
            );

            if ($user->role_id) {
                $employee->roles()->syncWithoutDetaching([$user->role_id]);
            }
        }
    }
    public function down(): void
    {
        \App\Models\Employee::truncate();
    }
}
