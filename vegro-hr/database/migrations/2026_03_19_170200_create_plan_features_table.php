<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->index();
            $table->unsignedBigInteger('feature_id')->index();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'feature_id']);
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->foreign('feature_id')->references('id')->on('features')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};

