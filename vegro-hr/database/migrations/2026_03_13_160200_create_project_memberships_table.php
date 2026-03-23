<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('project_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('reports_to_user_id')->nullable()->index();
            $table->string('role_title')->nullable();
            $table->unsignedTinyInteger('allocation_percent')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('reports_to_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['company_id', 'project_id', 'employee_id'], 'pm_company_project_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_memberships');
    }
};

