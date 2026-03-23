<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_leave_balances', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_leave_balances', 'carry_forward_days')) {
                $table->decimal('carry_forward_days', 8, 2)->default(0)->after('balance_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_leave_balances', function (Blueprint $table) {
            if (Schema::hasColumn('employee_leave_balances', 'carry_forward_days')) {
                $table->dropColumn('carry_forward_days');
            }
        });
    }
};

