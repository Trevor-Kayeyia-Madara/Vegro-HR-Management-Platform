<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('tax_profile_id')->nullable();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            
            // Basic salary and allowances
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);
            
            // Overtime calculations
            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_hours_worked', 5, 2)->default(0);
            $table->decimal('overtime_allowance', 10, 2)->default(0);
            
            // Deductions (Kenya statutory)
            $table->decimal('nssf', 10, 2)->default(0);
            $table->decimal('shif', 10, 2)->default(0);
            $table->decimal('housing_levy', 10, 2)->default(0);
            $table->decimal('taxable_income', 10, 2)->default(0);
            $table->decimal('paye', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 4)->default(0);
            $table->decimal('personal_relief', 10, 2)->default(0);
            $table->decimal('insurance_premium', 10, 2)->default(0);
            $table->decimal('insurance_relief', 10, 2)->default(0);
            $table->decimal('pension_contribution', 10, 2)->default(0);
            $table->decimal('mortgage_interest', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            
            // Final calculations
            $table->decimal('net_salary', 10, 2)->default(0);
            
            // Approval fields
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approver_signature_name')->nullable();
            $table->timestamp('approver_signature_at')->nullable();
            $table->string('approver_signature_ip')->nullable();
            $table->string('approver_signature_user_agent')->nullable();
            
            $table->unsignedBigInteger('company_id')->nullable();
            $table->timestamps();

            // Foreign keys will be added after all tables are created
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
