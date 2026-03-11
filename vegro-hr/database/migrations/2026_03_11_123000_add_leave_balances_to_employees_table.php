<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('annual_leave_days')->default(21)->after('status');
            $table->unsignedInteger('annual_leave_used')->default(0)->after('annual_leave_days');
            $table->unsignedInteger('annual_leave_balance')->default(21)->after('annual_leave_used');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['annual_leave_days', 'annual_leave_used', 'annual_leave_balance']);
        });
    }
};
