<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_onboarding_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('template_id')->index();
            $table->enum('status', ['pending', 'signed', 'rejected'])->default('pending')->index();
            $table->date('due_date')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_name', 255)->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->string('signed_user_agent', 512)->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('onboarding_document_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_onboarding_documents');
    }
};
