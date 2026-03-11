<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('reason')->nullable()->after('end_date');
            $table->unsignedInteger('leave_days')->default(0)->after('reason');
            $table->string('approved_role')->nullable()->after('approved_by');
            $table->timestamp('approved_at')->nullable()->after('approved_role');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['reason', 'leave_days', 'approved_role', 'approved_at']);
        });
    }
};
