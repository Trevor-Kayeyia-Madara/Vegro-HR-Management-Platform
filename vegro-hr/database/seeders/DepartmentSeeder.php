<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = app()->has('company_id')
            ? app('company_id')
            : \App\Models\Company::where('domain', 'default.local')->value('id');
        $hrManagerId = \App\Models\User::where('email', 'hr@example.com')->value('id');
        $financeManagerId = \App\Models\User::where('email', 'finance@example.com')->value('id');
        $marketingManagerId = \App\Models\User::where('email', 'marketing.manager@example.com')->value('id');
        $operationsManagerId = \App\Models\User::where('email', 'operations.manager@example.com')->value('id');
        $engineeringManagerId = \App\Models\User::where('email', 'engineering.manager@example.com')->value('id');
        $salesManagerId = \App\Models\User::where('email', 'sales.manager@example.com')->value('id');
        $successManagerId = \App\Models\User::where('email', 'success.manager@example.com')->value('id');
        $itManagerId = \App\Models\User::where('email', 'it.manager@example.com')->value('id');

        $departments = [
            ['name' => 'Human Resources', 'description' => 'Handles recruitment, employee relations, and benefits.', 'manager_id' => $hrManagerId, 'company_id' => $companyId],
            ['name' => 'Finance', 'description' => 'Manages company finances, budgeting, and accounting.', 'manager_id' => $financeManagerId, 'company_id' => $companyId],
            ['name' => 'Marketing', 'description' => 'Responsible for market research, advertising, and promotions.', 'manager_id' => $marketingManagerId, 'company_id' => $companyId],
            ['name' => 'Operations', 'description' => 'Oversees daily operations and process efficiency.', 'manager_id' => $operationsManagerId, 'company_id' => $companyId],
            ['name' => 'Engineering', 'description' => 'Builds and maintains product and infrastructure.', 'manager_id' => $engineeringManagerId, 'company_id' => $companyId],
            ['name' => 'Sales', 'description' => 'Drives revenue growth and customer acquisition.', 'manager_id' => $salesManagerId, 'company_id' => $companyId],
            ['name' => 'Customer Success', 'description' => 'Ensures customer retention and satisfaction.', 'manager_id' => $successManagerId, 'company_id' => $companyId],
            ['name' => 'IT', 'description' => 'Manages internal systems, security, and support.', 'manager_id' => $itManagerId, 'company_id' => $companyId],
        ];

        foreach ($departments as $department) {
            \App\Models\Department::updateOrCreate(
                ['name' => $department['name'], 'company_id' => $companyId],
                $department,
            );
        }
    }
    public function down(): void
    {
        \App\Models\Department::truncate();
    }
}
