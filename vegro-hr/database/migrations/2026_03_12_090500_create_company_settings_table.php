<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->unique();
            $table->string('currency', 10)->default('USD');
            $table->string('timezone', 50)->default('UTC');
            $table->string('locale', 10)->default('en');
            $table->string('date_format', 32)->default('Y-m-d');
            $table->string('time_format', 32)->default('H:i');
            $table->json('tax_rules')->nullable();
            $table->json('payroll_rules')->nullable();
            $table->json('branding')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
