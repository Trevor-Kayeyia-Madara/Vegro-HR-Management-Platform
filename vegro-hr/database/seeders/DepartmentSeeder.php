<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'description' => 'Handles recruitment, employee relations, and benefits.'],
            ['name' => 'Finance', 'description' => 'Manages company finances, budgeting, and accounting.'],
            ['name' => 'Marketing', 'description' => 'Responsible for market research, advertising, and promotions.'],
            ['name' => 'Sales', 'description' => 'Focuses on selling products or services to customers.'],
            ['name' => 'IT', 'description' => 'Maintains technology infrastructure and supports digital initiatives.'],
        ];

        foreach ($departments as $department) {
            \App\Models\Department::create($department);
        }
    }
    public function down(): void
    {
        \App\Models\Department::truncate();
    }
}
