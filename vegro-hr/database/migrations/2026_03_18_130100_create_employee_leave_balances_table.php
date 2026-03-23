<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('employee_id');
            $table->string('leave_type', 50);
            $table->decimal('entitled_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('balance_days', 8, 2)->default(0);
            $table->date('last_reset_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'leave_type'], 'emp_leave_balances_unique');
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};

