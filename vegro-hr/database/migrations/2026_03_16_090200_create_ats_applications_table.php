<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ats_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('job_posting_id')->index();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->string('stage')->default('applied'); // applied | screening | interview | offer | hired | rejected | withdrawn
            $table->timestamp('applied_at')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->timestamp('last_stage_changed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('job_posting_id')->references('id')->on('ats_job_postings')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('ats_candidates')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['company_id', 'job_posting_id', 'candidate_id'], 'ats_app_company_job_candidate_unique');
            $table->index(['company_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_applications');
    }
};

