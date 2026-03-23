<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('code', 80)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->timestamp('detected_at')->nullable()->index();
            $table->unsignedBigInteger('acknowledged_by')->nullable()->index();
            $table->timestamp('acknowledged_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('acknowledged_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_alerts');
    }
};
