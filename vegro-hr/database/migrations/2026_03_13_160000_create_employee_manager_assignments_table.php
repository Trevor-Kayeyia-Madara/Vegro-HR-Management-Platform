<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('manager_user_id')->index();
            $table->string('relationship_type')->default('functional'); // functional | dotted
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('manager_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->unique(
                ['company_id', 'employee_id', 'manager_user_id', 'relationship_type'],
                'ema_company_employee_manager_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_manager_assignments');
    }
};

