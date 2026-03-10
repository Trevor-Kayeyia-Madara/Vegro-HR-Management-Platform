<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role_id' => \App\Models\Role::where('title', 'Super Admin')->first()?->id
                    ?? \App\Models\Role::where('title', 'Admin')->first()->id,
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'hr@example.com'],
            [
                'name' => 'HR Manager',
                'password' => bcrypt('P@ssw0rd'),
                'role_id' => \App\Models\Role::where('title', 'HR')->first()->id,
            ]
        );
    }
}
