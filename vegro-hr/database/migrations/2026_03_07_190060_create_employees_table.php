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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('position')->nullable();
            $table->decimal('salary', 10, 2)->default(0);
            $table->date('hire_date')->nullable();
            $table->string('status')->default('active');
            
            // Overtime configuration for security guards
            $table->boolean('has_overtime')->default(false);
            $table->decimal('daily_overtime_hours', 5, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->decimal('overtime_rate_multiplier', 5, 2)->default(1.5);
            $table->decimal('monthly_overtime_hours', 5, 2)->default(0);
            
            // Leave balances
            $table->integer('annual_leave_days')->default(0);
            $table->integer('annual_leave_used')->default(0);
            $table->integer('annual_leave_balance')->default(0);
            
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
        Schema::dropIfExists('employees');
    }
};
