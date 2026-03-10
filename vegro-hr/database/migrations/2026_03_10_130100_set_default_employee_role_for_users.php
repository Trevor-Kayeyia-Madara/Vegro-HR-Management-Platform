<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('users')) {
            return;
        }

        $roleId = DB::table('roles')->where('title', 'Employee')->value('id');

        if (!$roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'title' => 'Employee',
                'description' => 'Default employee role',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->where('name', '!=', 'Admin User')
            ->update([
                'role_id' => $roleId,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No rollback for data update to avoid overwriting user roles.
    }
};