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

        $this->renameRoleTitle('Super Admin', 'superadmin');
        $this->renameRoleTitle('Company Admin', 'companyadmin');
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $this->renameRoleTitle('superadmin', 'Super Admin');
        $this->renameRoleTitle('companyadmin', 'Company Admin');
    }

    protected function renameRoleTitle(string $from, string $to): void
    {
        DB::transaction(function () use ($from, $to): void {
            $oldRoles = DB::table('roles')->where('title', $from)->get();

            foreach ($oldRoles as $oldRole) {
                $targetRole = DB::table('roles')
                    ->where('title', $to)
                    ->where('company_id', $oldRole->company_id)
                    ->first();

                if (!$targetRole) {
                    DB::table('roles')->where('id', $oldRole->id)->update(['title' => $to]);
                    continue;
                }

                $this->reassignRoleReferences((int) $oldRole->id, (int) $targetRole->id);
                DB::table('roles')->where('id', $oldRole->id)->delete();
            }
        });
    }

    protected function reassignRoleReferences(int $oldRoleId, int $newRoleId): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role_id')) {
            DB::table('users')->where('role_id', $oldRoleId)->update(['role_id' => $newRoleId]);
        }

        if (Schema::hasTable('employee_role') && Schema::hasColumn('employee_role', 'role_id')) {
            DB::table('employee_role')->where('role_id', $oldRoleId)->update(['role_id' => $newRoleId]);
        }

        if (Schema::hasTable('permission_role') && Schema::hasColumn('permission_role', 'role_id')) {
            DB::table('permission_role')->where('role_id', $oldRoleId)->update(['role_id' => $newRoleId]);
        }

        if (Schema::hasTable('role_assignment_audits')) {
            if (Schema::hasColumn('role_assignment_audits', 'role_id')) {
                DB::table('role_assignment_audits')->where('role_id', $oldRoleId)->update(['role_id' => $newRoleId]);
            }
            if (Schema::hasColumn('role_assignment_audits', 'previous_role_id')) {
                DB::table('role_assignment_audits')->where('previous_role_id', $oldRoleId)->update(['previous_role_id' => $newRoleId]);
            }
        }

        if (Schema::hasTable('permission_assignment_audits') && Schema::hasColumn('permission_assignment_audits', 'role_id')) {
            DB::table('permission_assignment_audits')->where('role_id', $oldRoleId)->update(['role_id' => $newRoleId]);
        }
    }
};
