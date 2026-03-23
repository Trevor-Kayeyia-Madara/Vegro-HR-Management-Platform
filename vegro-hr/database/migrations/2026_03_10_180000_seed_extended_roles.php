<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $roles = [
            ['title' => 'superadmin', 'description' => 'Global administrator with full access'],
            ['title' => 'companyadmin', 'description' => 'Company-level administrator with full access'],
        ];

        foreach ($roles as $role) {
            $existing = DB::table('roles')->where('title', $role['title'])->first();
            if ($existing) {
                DB::table('roles')
                    ->where('id', $existing->id)
                    ->update([
                        'description' => $role['description'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('roles')->insert([
                    'title' => $role['title'],
                    'description' => $role['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Do not remove roles on rollback.
    }
};
