<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'roles',
            'employees',
            'departments',
            'payrolls',
            'payslips',
            'attendances',
            'leave_requests',
            'tax_profiles',
            'api_tokens',
            'employee_role',
            'role_assignment_audits',
            'permission_assignment_audits',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('company_id')->nullable()->index();
            });
        }

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        $companyId = DB::table('companies')->value('id');
        if ($companyId) {
            foreach ($tables as $table) {
                DB::table($table)->whereNull('company_id')->update(['company_id' => $companyId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'users',
            'roles',
            'employees',
            'departments',
            'payrolls',
            'payslips',
            'attendances',
            'leave_requests',
            'tax_profiles',
            'api_tokens',
            'employee_role',
            'role_assignment_audits',
            'permission_assignment_audits',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
