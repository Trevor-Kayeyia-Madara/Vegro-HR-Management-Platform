<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('plans', 'price_monthly')) {
                $table->decimal('price_monthly', 12, 2)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('plans', 'price_annual')) {
                $table->decimal('price_annual', 12, 2)->nullable()->after('price_monthly');
            }
            if (!Schema::hasColumn('plans', 'employee_limit')) {
                $table->unsignedInteger('employee_limit')->nullable()->after('price_annual');
            }
            if (!Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable()->after('employee_limit');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('plans', 'employee_limit')) {
                $table->dropColumn('employee_limit');
            }
            if (Schema::hasColumn('plans', 'price_annual')) {
                $table->dropColumn('price_annual');
            }
            if (Schema::hasColumn('plans', 'price_monthly')) {
                $table->dropColumn('price_monthly');
            }
            if (Schema::hasColumn('plans', 'slug')) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            }
        });
    }
};

