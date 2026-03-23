<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ats_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('source')->nullable(); // referral, linkedin, website, etc
            $table->string('linkedin_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['company_id', 'email'], 'ats_candidates_company_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ats_candidates');
    }
};

