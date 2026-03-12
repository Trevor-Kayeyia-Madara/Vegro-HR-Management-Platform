<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = app()->has('company_id')
            ? app('company_id')
            : \App\Models\Company::where('domain', 'default.local')->value('id');
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role_id' => \App\Models\Role::where('title', 'Super Admin')->first()?->id
                    ?? \App\Models\Role::where('title', 'Admin')->first()->id,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => bcrypt('P@ssw0rd'),
                'role_id' => \App\Models\Role::where('title', 'HR')->first()->id,
                'company_id' => $companyId,
            ]
        );
        \App\Models\User::updateOrCreate(
            ['email' => 'finance@example.com'],
            [
                'name' => 'Finance Manager',
                'password' => bcrypt('accounts123'),
                'role_id' => \App\Models\Role::where('title', 'Finance')->first()->id,
                'company_id' => $companyId,
            ]
        );

        $managerRoleId = \App\Models\Role::where('title', 'Manager')->first()?->id;

        \App\Models\User::updateOrCreate(
            ['email' => 'marketing.manager@example.com'],
            [
                'name' => 'Marketing Manager',
                'password' => bcrypt('marketing123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'operations.manager@example.com'],
            [
                'name' => 'Operations Manager',
                'password' => bcrypt('operations123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'engineering.manager@example.com'],
            [
                'name' => 'Engineering Manager',
                'password' => bcrypt('engineering123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'sales.manager@example.com'],
            [
                'name' => 'Sales Manager',
                'password' => bcrypt('sales123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'success.manager@example.com'],
            [
                'name' => 'Customer Success Manager',
                'password' => bcrypt('success123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'it.manager@example.com'],
            [
                'name' => 'IT Manager',
                'password' => bcrypt('itmanager123'),
                'role_id' => $managerRoleId,
                'company_id' => $companyId,
            ]
        );
    }
}
