<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('net_salary');
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('approver_signature_name', 255)->nullable()->after('approved_at');
            $table->timestamp('approver_signature_at')->nullable()->after('approver_signature_name');
            $table->string('approver_signature_ip', 45)->nullable()->after('approver_signature_at');
            $table->string('approver_signature_user_agent', 512)->nullable()->after('approver_signature_ip');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'status',
                'approved_by',
                'approved_at',
                'approver_signature_name',
                'approver_signature_at',
                'approver_signature_ip',
                'approver_signature_user_agent',
            ]);
        });
    }
};
