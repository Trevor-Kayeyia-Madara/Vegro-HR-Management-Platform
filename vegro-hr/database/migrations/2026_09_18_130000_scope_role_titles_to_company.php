<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasColumn('roles', 'company_id')) {
            return;
        }

        $globalIndex = DB::select(
            "SHOW INDEX FROM roles WHERE Key_name = 'roles_title_unique'"
        );
        if ($globalIndex !== []) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_title_unique');
            });
        }

        $scopedIndex = DB::select(
            "SHOW INDEX FROM roles WHERE Key_name = 'roles_company_id_title_unique'"
        );
        if ($scopedIndex === []) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['company_id', 'title'], 'roles_company_id_title_unique');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $scopedIndex = DB::select(
            "SHOW INDEX FROM roles WHERE Key_name = 'roles_company_id_title_unique'"
        );
        if ($scopedIndex !== []) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_company_id_title_unique');
            });
        }

        // This rollback is only safe when role titles are globally unique.
        $globalIndex = DB::select(
            "SHOW INDEX FROM roles WHERE Key_name = 'roles_title_unique'"
        );
        if ($globalIndex === []) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique('title', 'roles_title_unique');
            });
        }
    }
};
