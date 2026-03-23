<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_type_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('type', 50);
            $table->string('label');
            $table->boolean('enabled')->default(true);
            $table->enum('unit', ['working_days', 'calendar_days'])->default('working_days');
            $table->decimal('days_per_year', 8, 2)->nullable();
            $table->decimal('full_pay_days', 8, 2)->nullable();
            $table->decimal('half_pay_days', 8, 2)->nullable();
            $table->decimal('accrual_per_month', 8, 2)->nullable();
            $table->unsignedInteger('min_months_of_service')->default(0);
            $table->unsignedInteger('notice_days')->default(0);
            $table->boolean('requires_documentation')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'type']);
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_type_settings');
    }
};

