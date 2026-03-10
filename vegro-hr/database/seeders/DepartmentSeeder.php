<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hrManagerId = \App\Models\User::where('email', 'hr@example.com')->value('id');

        $departments = [
            ['name' => 'Human Resources', 'description' => 'Handles recruitment, employee relations, and benefits.', 'manager_id' => $hrManagerId],
            ['name' => 'Finance', 'description' => 'Manages company finances, budgeting, and accounting.'],
            ['name' => 'Marketing', 'description' => 'Responsible for market research, advertising, and promotions.'],
        ];

        foreach ($departments as $department) {
            \App\Models\Department::updateOrCreate(
                ['name' => $department['name']],
                $department,
            );
        }
    }
    public function down(): void
    {
        \App\Models\Department::truncate();
    }
}
