<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('plan')->default('starter');
            $table->enum('environment', ['demo', 'staging', 'production'])->default('demo');
            $table->timestamps();
        });

        DB::table('companies')->insert([
            'name' => 'Default Company',
            'domain' => 'default.local',
            'status' => 'active',
            'plan' => 'starter',
            'environment' => 'demo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
