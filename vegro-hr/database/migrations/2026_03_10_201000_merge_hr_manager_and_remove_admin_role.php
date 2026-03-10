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

        $hrId = DB::table('roles')->where('title', 'HR')->value('id');
        $employeeId = DB::table('roles')->where('title', 'Employee')->value('id');

        $hrManagerId = DB::table('roles')->where('title', 'HR Manager')->value('id');
        $adminId = DB::table('roles')->where('title', 'Admin')->value('id');

        if ($hrId && $hrManagerId && Schema::hasTable('users')) {
            DB::table('users')->where('role_id', $hrManagerId)->update(['role_id' => $hrId]);
        }

        if ($employeeId && $adminId && Schema::hasTable('users')) {
            DB::table('users')->where('role_id', $adminId)->update(['role_id' => $employeeId]);
        }

        if ($hrManagerId) {
            DB::table('roles')->where('id', $hrManagerId)->delete();
        }

        if ($adminId) {
            DB::table('roles')->where('id', $adminId)->delete();
        }
    }

    public function down(): void
    {
        // Do not re-insert removed roles on rollback.
    }
};
