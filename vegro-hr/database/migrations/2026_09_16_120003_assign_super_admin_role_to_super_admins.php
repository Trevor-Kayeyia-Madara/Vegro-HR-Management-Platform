<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure roles table exists and has super admin role
        if (Schema::hasTable('roles')) {
            $superAdminRole = DB::table('roles')->where('title', 'super_admin')->first();
            
            if ($superAdminRole) {
                // Assign super admin role to all users with is_super_admin flag
                DB::table('users')
                    ->where('is_super_admin', true)
                    ->whereNull('role_id')
                    ->update(['role_id' => $superAdminRole->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove role_id from super admins (they don't need it since they have is_super_admin flag)
        DB::table('users')
            ->where('is_super_admin', true)
            ->update(['role_id' => null]);
    }
};
