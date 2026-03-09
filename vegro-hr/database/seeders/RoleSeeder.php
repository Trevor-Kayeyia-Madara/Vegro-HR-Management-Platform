<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
         ['title' => 'Admin', 'description' => 'Administrator with full access'],
         ['title' => 'Manager', 'description' => 'Manager with limited access'],
         ['title' => 'Employee', 'description' => 'Employee with basic access'],
         ['title' => 'HR', 'description' => 'Human Resources with access to employee data'],
         ['title' => 'Finance', 'description' => 'Finance team with access to financial data'],
         ['title' => 'IT', 'description' => 'IT team with access to technical resources'],
         ['title' => 'Sales', 'description' => 'Sales team with access to sales data'],
         ['title' => 'Marketing', 'description' => 'Marketing team with access to marketing data'],
         ['title' => 'Support', 'description' => 'Support team with access to customer support data'],
         ['title' => 'Intern', 'description' => 'Intern with limited access for learning purposes'],
        ];

        foreach ($roles as $role) {
            \App\Models\Role::create($role);
        }
    }
}

