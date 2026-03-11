<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['key' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'Dashboard'],
            ['key' => 'users.view', 'label' => 'View Users', 'group' => 'Users'],
            ['key' => 'users.manage', 'label' => 'Manage Users', 'group' => 'Users'],
            ['key' => 'roles.view', 'label' => 'View Roles', 'group' => 'RBAC'],
            ['key' => 'roles.manage', 'label' => 'Manage Roles', 'group' => 'RBAC'],
            ['key' => 'settings.manage', 'label' => 'Manage Settings', 'group' => 'Platform'],
            ['key' => 'employees.view', 'label' => 'View Employees', 'group' => 'Employees'],
            ['key' => 'employees.manage', 'label' => 'Manage Employees', 'group' => 'Employees'],
            ['key' => 'departments.view', 'label' => 'View Departments', 'group' => 'Departments'],
            ['key' => 'departments.manage', 'label' => 'Manage Departments', 'group' => 'Departments'],
            ['key' => 'payroll.view', 'label' => 'View Payroll', 'group' => 'Payroll'],
            ['key' => 'payroll.manage', 'label' => 'Manage Payroll', 'group' => 'Payroll'],
            ['key' => 'attendance.view', 'label' => 'View Attendance', 'group' => 'Attendance'],
            ['key' => 'attendance.manage', 'label' => 'Manage Attendance', 'group' => 'Attendance'],
            ['key' => 'leaves.view', 'label' => 'View Leave Requests', 'group' => 'Leaves'],
            ['key' => 'leaves.manage', 'label' => 'Manage Leave Requests', 'group' => 'Leaves'],
            ['key' => 'leaves.approve', 'label' => 'Approve/Reject Leaves', 'group' => 'Leaves'],
            ['key' => 'payslips.view', 'label' => 'View Payslips', 'group' => 'Payslips'],
            ['key' => 'payslips.manage', 'label' => 'Manage Payslips', 'group' => 'Payslips'],
            ['key' => 'profile.view', 'label' => 'View Profile', 'group' => 'Profile'],
            ['key' => 'taxprofiles.view', 'label' => 'View Tax Profiles', 'group' => 'Tax Profiles'],
            ['key' => 'taxprofiles.manage', 'label' => 'Manage Tax Profiles', 'group' => 'Tax Profiles'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['key' => $permission['key']], $permission);
        }

        $adminRoles = Role::whereIn('title', ['Super Admin', 'Company Admin'])->get();
        $allPermissionIds = Permission::pluck('id')->all();

        foreach ($adminRoles as $role) {
            $role->permissions()->syncWithoutDetaching($allPermissionIds);
        }

        $permissionMap = Permission::pluck('id', 'key');

        $rolePermissionKeys = [
            'HR' => [
                'dashboard.view',
                'employees.view',
                'employees.manage',
                'departments.view',
                'departments.manage',
                'attendance.view',
                'attendance.manage',
                'leaves.view',
                'leaves.manage',
                'leaves.approve',
                'profile.view',
            ],
            'Finance' => [
                'dashboard.view',
                'payroll.view',
                'payroll.manage',
                'payslips.view',
                'payslips.manage',
                'taxprofiles.view',
                'taxprofiles.manage',
                'profile.view',
            ],
            'Manager' => [
                'dashboard.view',
                'employees.view',
                'attendance.view',
                'leaves.view',
                'leaves.approve',
                'profile.view',
            ],
            'Director' => [
                'dashboard.view',
                'employees.view',
                'departments.view',
                'leaves.view',
                'leaves.approve',
                'payroll.view',
                'payslips.view',
                'profile.view',
            ],
            'MD' => [
                'dashboard.view',
                'employees.view',
                'departments.view',
                'leaves.view',
                'leaves.approve',
                'payroll.view',
                'payslips.view',
                'profile.view',
            ],
            'Employee' => [
                'dashboard.view',
                'leaves.view',
                'profile.view',
                'payslips.view',
            ],
        ];

        foreach ($rolePermissionKeys as $roleTitle => $keys) {
            $role = Role::where('title', $roleTitle)->first();
            if (!$role) {
                continue;
            }
            $ids = collect($keys)
                ->map(fn ($key) => $permissionMap[$key] ?? null)
                ->filter()
                ->values()
                ->all();
            $role->permissions()->syncWithoutDetaching($ids);
        }
    }
}
