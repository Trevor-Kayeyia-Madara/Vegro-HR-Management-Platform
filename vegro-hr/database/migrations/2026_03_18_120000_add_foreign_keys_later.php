<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $relationships = [
            ['table' => 'users', 'column' => 'role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'cascade'],
            ['table' => 'departments', 'column' => 'manager_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'employees', 'column' => 'user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'employees', 'column' => 'department_id', 'references' => 'id', 'on' => 'departments', 'onDelete' => 'set null'],
            ['table' => 'payrolls', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'payrolls', 'column' => 'tax_profile_id', 'references' => 'id', 'on' => 'tax_profiles', 'onDelete' => 'set null'],
            ['table' => 'attendances', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'leave_requests', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'leave_requests', 'column' => 'approved_by', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'payslips', 'column' => 'payroll_id', 'references' => 'id', 'on' => 'payrolls', 'onDelete' => 'cascade'],
            ['table' => 'payslips', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'set null'],
            ['table' => 'payslips', 'column' => 'approved_by', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'api_tokens', 'column' => 'user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'cascade'],
            ['table' => 'employee_role', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'employee_role', 'column' => 'role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'cascade'],
            ['table' => 'role_assignment_audits', 'column' => 'user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'cascade'],
            ['table' => 'role_assignment_audits', 'column' => 'role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'cascade'],
            ['table' => 'role_assignment_audits', 'column' => 'previous_role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'set null'],
            ['table' => 'role_assignment_audits', 'column' => 'assigned_by', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'permission_role', 'column' => 'role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'cascade'],
            ['table' => 'permission_role', 'column' => 'permission_id', 'references' => 'id', 'on' => 'permissions', 'onDelete' => 'cascade'],
            ['table' => 'permission_assignment_audits', 'column' => 'role_id', 'references' => 'id', 'on' => 'roles', 'onDelete' => 'cascade'],
            ['table' => 'permission_assignment_audits', 'column' => 'permission_id', 'references' => 'id', 'on' => 'permissions', 'onDelete' => 'cascade'],
            ['table' => 'permission_assignment_audits', 'column' => 'assigned_by', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'company_domains', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'subscriptions', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'subscriptions', 'column' => 'plan_id', 'references' => 'id', 'on' => 'plans', 'onDelete' => 'restrict'],
            ['table' => 'company_settings', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'activity_logs', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'set null'],
            ['table' => 'activity_logs', 'column' => 'actor_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'report_definitions', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'report_definitions', 'column' => 'created_by', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'dashboard_definitions', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'dashboard_definitions', 'column' => 'user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'cascade'],
            ['table' => 'dashboard_widgets', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'dashboard_widgets', 'column' => 'dashboard_id', 'references' => 'id', 'on' => 'dashboard_definitions', 'onDelete' => 'cascade'],
            ['table' => 'employee_manager_assignments', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'employee_manager_assignments', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'employee_manager_assignments', 'column' => 'manager_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'cascade'],
            ['table' => 'projects', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'project_memberships', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'project_memberships', 'column' => 'project_id', 'references' => 'id', 'on' => 'projects', 'onDelete' => 'cascade'],
            ['table' => 'project_memberships', 'column' => 'employee_id', 'references' => 'id', 'on' => 'employees', 'onDelete' => 'cascade'],
            ['table' => 'project_memberships', 'column' => 'reports_to_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_job_postings', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'ats_job_postings', 'column' => 'department_id', 'references' => 'id', 'on' => 'departments', 'onDelete' => 'set null'],
            ['table' => 'ats_job_postings', 'column' => 'hiring_manager_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_job_postings', 'column' => 'created_by_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_candidates', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'ats_candidates', 'column' => 'created_by_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_applications', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'ats_applications', 'column' => 'job_posting_id', 'references' => 'id', 'on' => 'ats_job_postings', 'onDelete' => 'cascade'],
            ['table' => 'ats_applications', 'column' => 'candidate_id', 'references' => 'id', 'on' => 'ats_candidates', 'onDelete' => 'cascade'],
            ['table' => 'ats_applications', 'column' => 'created_by_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_application_notes', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'ats_application_notes', 'column' => 'application_id', 'references' => 'id', 'on' => 'ats_applications', 'onDelete' => 'cascade'],
            ['table' => 'ats_application_notes', 'column' => 'author_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'ats_application_stage_events', 'column' => 'company_id', 'references' => 'id', 'on' => 'companies', 'onDelete' => 'cascade'],
            ['table' => 'ats_application_stage_events', 'column' => 'application_id', 'references' => 'id', 'on' => 'ats_applications', 'onDelete' => 'cascade'],
            ['table' => 'ats_application_stage_events', 'column' => 'changed_by_user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
            ['table' => 'sessions', 'column' => 'user_id', 'references' => 'id', 'on' => 'users', 'onDelete' => 'set null'],
        ];

        foreach ($relationships as $relationship) {
            $this->attachForeignKeyIfMissing($relationship);
        }

        $companyTables = [
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

        foreach ($companyTables as $tableName) {
            $this->attachForeignKeyIfMissing([
                'table' => $tableName,
                'column' => 'company_id',
                'references' => 'id',
                'on' => 'companies',
                'onDelete' => 'set null',
            ]);
        }
    }

    public function down(): void
    {
        // Intentionally empty: this migration is a safe repair step for hosted environments.
    }

    private function attachForeignKeyIfMissing(array $relationship): void
    {
        $table = $relationship['table'];
        $column = $relationship['column'];
        $referencedTable = $relationship['on'];
        $referencedColumn = $relationship['references'];
        $onDelete = $relationship['onDelete'];

        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable)) {
            return;
        }

        if (!Schema::hasColumn($table, $column) || !Schema::hasColumn($referencedTable, $referencedColumn)) {
            return;
        }

        if ($this->hasForeignKey($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $foreign = $blueprint->foreign($column)->references($referencedColumn)->on($referencedTable);

            if ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } elseif ($onDelete === 'set null') {
                $foreign->nullOnDelete();
            } elseif ($onDelete === 'restrict') {
                $foreign->restrictOnDelete();
            }
        });
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        $databaseName = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT 1
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$databaseName, $table, $column]
        );

        return $result !== null;
    }
};
