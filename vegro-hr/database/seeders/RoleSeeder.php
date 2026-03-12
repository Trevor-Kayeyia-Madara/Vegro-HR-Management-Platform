<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = app()->has('company_id')
            ? app('company_id')
            : \App\Models\Company::where('domain', 'default.local')->value('id');
        $roles = [
         ['title' => 'Super Admin', 'description' => 'Global administrator with full access'],
         ['title' => 'Company Admin', 'description' => 'Company-level administrator with full access'],
         ['title' => 'HR', 'description' => 'Human Resources with access to employee data'],
         ['title' => 'Finance', 'description' => 'Finance team with access to financial data'],
         ['title' => 'Manager', 'description' => 'Manager with limited access'],
         ['title' => 'Director', 'description' => 'Director with high-level approvals'],
         ['title' => 'MD', 'description' => 'Managing Director with executive visibility'],
         ['title' => 'Employee', 'description' => 'Employee with basic access'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::firstOrCreate(
                ['title' => $role['title'], 'company_id' => $companyId],
                array_merge($role, ['company_id' => $companyId]),
            );
        }
    }
}
