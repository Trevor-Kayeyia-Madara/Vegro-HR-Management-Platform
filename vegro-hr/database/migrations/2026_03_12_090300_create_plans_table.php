<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->enum('interval', ['monthly', 'yearly', 'one_time'])->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
