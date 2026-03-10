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

        $employeeRoleId = DB::table('roles')->where('title', 'Employee')->value('id');
        if ($employeeRoleId && Schema::hasTable('users')) {
            $roleIds = DB::table('roles')->whereIn('title', [
                'IT',
                'Sales',
                'Marketing',
                'Support',
                'Intern',
            ])->pluck('id');

            if ($roleIds->isNotEmpty()) {
                DB::table('users')
                    ->whereIn('role_id', $roleIds->all())
                    ->update(['role_id' => $employeeRoleId]);
            }
        }

        DB::table('roles')->whereIn('title', [
            'IT',
            'Sales',
            'Marketing',
            'Support',
            'Intern',
        ])->delete();
    }

    public function down(): void
    {
        // Do not re-insert removed roles on rollback.
    }
};
