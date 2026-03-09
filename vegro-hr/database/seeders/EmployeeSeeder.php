<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Alice Johnson', 'email' => 'alice.johnson@example.com', 'department_id' => 1],
            ['name' => 'Bob Smith', 'email' => 'bob.smith@example.com', 'department_id' => 2],
            ['name' => 'Charlie Brown', 'email' => 'charlie.brown@example.com', 'department_id' => 3],
            ['name' => 'Diana Prince', 'email' => 'diana.prince@example.com', 'department_id' => 4],
            ['name' => 'Ethan Hunt', 'email' => 'ethan.hunt@example.com', 'department_id' => 5],
        ];
        foreach ($employees as $employee) {
            \App\Models\Employee::create($employee);
        }
    }
    public function down(): void
    {
        \App\Models\Employee::truncate();
    }
}
