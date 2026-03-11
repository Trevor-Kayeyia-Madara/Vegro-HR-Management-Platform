<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('payroll_id');
            $table->string('employee_name')->nullable()->after('employee_id');
            $table->string('employee_email')->nullable()->after('employee_name');
            $table->string('employee_number')->nullable()->after('employee_email');
            $table->date('pay_period_start')->nullable()->after('employee_number');
            $table->date('pay_period_end')->nullable()->after('pay_period_start');
            $table->decimal('gross_pay', 10, 2)->default(0)->after('pay_period_end');
            $table->decimal('total_deductions', 10, 2)->default(0)->after('gross_pay');
            $table->decimal('net_pay', 10, 2)->default(0)->after('total_deductions');
            $table->json('earnings_breakdown')->nullable()->after('net_pay');
            $table->json('deductions_breakdown')->nullable()->after('earnings_breakdown');
            $table->enum('status', ['draft', 'approved', 'issued'])->default('draft')->after('deductions_breakdown');
            $table->unsignedBigInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->timestamp('issued_at')->nullable()->after('approved_at');

            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'employee_id',
                'employee_name',
                'employee_email',
                'employee_number',
                'pay_period_start',
                'pay_period_end',
                'gross_pay',
                'total_deductions',
                'net_pay',
                'earnings_breakdown',
                'deductions_breakdown',
                'status',
                'approved_by',
                'approved_at',
                'issued_at',
            ]);
        });
    }
};
